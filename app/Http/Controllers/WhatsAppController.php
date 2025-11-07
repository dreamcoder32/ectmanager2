<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Parcel;
use App\Models\ParcelPriceChange;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

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

        // Add price_modified flag to each parcel
        $parcels->getCollection()->transform(function ($parcel) {
            $parcel->price_modified = $parcel->priceChanges()->exists();
            return $parcel;
        });

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

    /**
     * Update the price of a parcel and track the change.
     */
    public function updateParcelPrice(Request $request, Parcel $parcel)
    {
        $request->validate([
            "new_price" => "required|numeric|min:0",
            "reason" => "nullable|string|max:500",
        ]);

        $oldPrice = $parcel->cod_amount;
        $newPrice = $request->input("new_price");

        // Check if price actually changed
        if ($oldPrice == $newPrice) {
            return response()->json(
                [
                    "success" => false,
                    "error" => "New price is the same as the current price.",
                ],
                422,
            );
        }

        try {
            DB::beginTransaction();

            // Update parcel price
            $parcel->update([
                "cod_amount" => $newPrice,
            ]);

            // Record the price change
            ParcelPriceChange::create([
                "parcel_id" => $parcel->id,
                "old_price" => $oldPrice,
                "new_price" => $newPrice,
                "changed_by" => Auth::id(),
                "reason" => $request->input("reason"),
            ]);

            DB::commit();

            return response()->json([
                "success" => true,
                "message" => "Price updated successfully.",
                "data" => [
                    "old_price" => $oldPrice,
                    "new_price" => $newPrice,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(
                [
                    "success" => false,
                    "error" => "Failed to update price: " . $e->getMessage(),
                ],
                500,
            );
        }
    }

    /**
     * Get the price change history for a parcel.
     */
    public function getPriceChangeHistory(Parcel $parcel)
    {
        $priceChanges = $parcel
            ->priceChanges()
            ->with("changedBy:id,name")
            ->orderBy("created_at", "desc")
            ->get()
            ->map(function ($change) {
                return [
                    "id" => $change->id,
                    "old_price" => $change->old_price,
                    "new_price" => $change->new_price,
                    "reason" => $change->reason,
                    "changed_by" => $change->changedBy->name,
                    "changed_at" => $change->created_at->format("Y-m-d H:i:s"),
                ];
            });

        return response()->json([
            "success" => true,
            "data" => $priceChanges,
        ]);
    }
}
