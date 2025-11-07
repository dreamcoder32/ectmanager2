<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Parcel;
use Inertia\Inertia;

class WhatsAppController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    public function index(Request $request)
    {
        $query = Parcel::query()->with(["company"]);

        // Apply filters
        if ($request->filled("status")) {
            $query->where("status", $request->input("status"));
        }
        if ($request->filled("company_id")) {
            $query->where("company_id", $request->input("company_id"));
        }
        if ($request->filled("search")) {
            $search = $request->input("search");
            $query->where(function ($q) use ($search) {
                $q->where("tracking_number", "like", "%{$search}%")
                    ->orWhere("recipient_name", "like", "%{$search}%")
                    ->orWhere("recipient_phone", "like", "%{$search}%");
            });
        }

        $parcels = $query->latest()->paginate(15);

        $companies = Company::all();

        return Inertia::render("WhatsApp/Index", [
            "parcels" => $parcels,
            "filters" => $request->all(["status", "company_id", "search"]),
            "companies" => $companies,
        ]);
    }

    public function sendMessage(Request $request, Parcel $parcel)
    {
        $request->validate(["message" => "required|string"]);
        $result = $this->whatsappService->sendMessageToParcel(
            $parcel,
            $request->input("message"),
            Auth::id(),
        );
        return response()->json($result);
    }

    public function sendBulkMessages(Request $request)
    {
        $request->validate([
            "parcel_ids" => "required|array",
            "parcel_ids.*" => "exists:parcels,id",
            "message" => "required|string",
        ]);

        $result = $this->whatsappService->sendBulkMessages(
            $request->input("parcel_ids"),
            $request->input("message"),
            Auth::id(),
        );

        return response()->json($result);
    }

    public function sendDeskPickupNotification(Parcel $parcel)
    {
        $result = $this->whatsappService->sendDeskPickupNotification($parcel);
        return response()->json($result);
    }

    public function showParcelMessages(Parcel $parcel)
    {
        $parcel->load(["company", "assignedDriver", "state", "city"]);
        $messages = $this->whatsappService->getParcelMessageHistory($parcel);

        return Inertia::render("WhatsApp/ParcelMessages", [
            "parcel" => $parcel,
            "messages" => $messages,
            "isConfigured" => $this->whatsappService->isCompanyConfigured(
                $parcel->company,
            ),
        ]);
    }

    /**
     * Get the WhatsApp session status for a company.
     */
    public function getCompanySessionStatus(Request $request, Company $company)
    {
        try {
            $statusResult = $this->whatsappService->getSessionStatus($company);

            if (!$statusResult["success"]) {
                return response()->json(
                    [
                        "success" => false,
                        "error" =>
                            $statusResult["error"] ??
                            "Failed to fetch session status",
                    ],
                    500,
                );
            }

            $status = $statusResult["status"] ?? "UNKNOWN";
            $needsQr = !in_array($status, ["CONNECTED", "WORKING"]);
            $qrCode = null;
            $qrError = null;

            // If session is disconnected, try to fetch QR code
            if ($needsQr) {
                $qrResult = $this->whatsappService->fetchSessionQrCode(
                    $company,
                );

                if ($qrResult["success"]) {
                    $qrCode = $qrResult["qr_code"];
                } else {
                    $qrError = $qrResult["error"] ?? "Unable to fetch QR code";
                }
            }

            return response()->json([
                "success" => true,
                "data" => [
                    "status" => $status,
                    "needs_qr" => $needsQr,
                    "qr_code" => $qrCode,
                    "qr_error" => $qrError,
                    "checked_at" => now()->toISOString(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(
                [
                    "success" => false,
                    "error" => $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get the WhatsApp session QR code for the user's company.
     */
    public function getSessionQrCode(Request $request, Company $company)
    {
        $result = $this->whatsappService->fetchSessionQrCode($company);

        if ($result["success"]) {
            return response()->json([
                "success" => true,
                "qr_code" => $result["qr_code"],
                "status" => $result["status"],
            ]);
        }

        return response()->json(
            [
                "success" => false,
                "error" => $result["error"] ?? "Failed to fetch QR code.",
            ],
            500,
        );
    }
}
