<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\Company;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class WhatsAppController extends Controller
{
    protected $whatsappService;

    public function __construct(WhatsAppService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send a WhatsApp message to a specific parcel
     */
    public function sendMessage(Request $request, Parcel $parcel)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$this->whatsappService->isCompanyConfigured($parcel->company)) {
            return response()->json([
                'success' => false,
                'error' => 'WhatsApp integration not configured for this company'
            ], 400);
        }

        $result = $this->whatsappService->sendMessageToParcel(
            $parcel, 
            $request->message, 
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Send bulk WhatsApp messages
     */
    public function sendBulkMessages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parcel_ids' => 'required|array|min:1',
            'parcel_ids.*' => 'exists:parcels,id',
            'message' => 'required|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->whatsappService->sendBulkMessages(
            $request->parcel_ids,
            $request->message,
            Auth::id()
        );

        return response()->json($result);
    }

    /**
     * Send delivery notification
     */
    public function sendDeliveryNotification(Parcel $parcel)
    {
        if (!$this->whatsappService->isCompanyConfigured($parcel->company)) {
            return response()->json([
                'success' => false,
                'error' => 'WhatsApp integration not configured for this company'
            ], 400);
        }

        $result = $this->whatsappService->sendDeliveryNotification($parcel, Auth::id());

        return response()->json($result);
    }

    /**
     * Send collection notification
     */
    public function sendCollectionNotification(Parcel $parcel)
    {
        if (!$this->whatsappService->isCompanyConfigured($parcel->company)) {
            return response()->json([
                'success' => false,
                'error' => 'WhatsApp integration not configured for this company'
            ], 400);
        }

        $result = $this->whatsappService->sendCollectionNotification($parcel, Auth::id());

        return response()->json($result);
    }

    /**
     * Get message history for a parcel
     */
    public function getMessageHistory(Parcel $parcel)
    {
        $messages = $this->whatsappService->getParcelMessageHistory($parcel);

        return response()->json([
            'success' => true,
            'messages' => $messages
        ]);
    }

    /**
     * Test WhatsApp API connection for a company
     */
    public function testConnection(Company $company)
    {
        $result = $this->whatsappService->testConnection($company);

        return response()->json($result);
    }

    /**
     * Show WhatsApp management interface
     */
    public function index(Request $request)
    {
        $query = Parcel::with(['company', 'assignedDriver', 'state', 'city']);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 15), 100);
        $parcels = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return Inertia::render('WhatsApp/Index', [
            'parcels' => $parcels,
            'filters' => $request->only(['status', 'company_id', 'search']),
            'companies' => Company::active()->get(),
        ]);
    }

    /**
     * Show parcel messaging interface
     */
    public function showParcelMessages(Parcel $parcel)
    {
        $parcel->load(['company', 'assignedDriver', 'state', 'city']);
        $messages = $this->whatsappService->getParcelMessageHistory($parcel);

        return Inertia::render('WhatsApp/ParcelMessages', [
            'parcel' => $parcel,
            'messages' => $messages,
            'isConfigured' => $this->whatsappService->isCompanyConfigured($parcel->company)
        ]);
    }

    /**
     * Mark parcel as having WhatsApp tag
     */
    public function toggleWhatsAppTag(Parcel $parcel)
    {
        $parcel->update([
            'has_whatsapp_tag' => !$parcel->has_whatsapp_tag
        ]);

        return response()->json([
            'success' => true,
            'has_whatsapp_tag' => $parcel->has_whatsapp_tag
        ]);
    }

    /**
     * Get companies with WhatsApp configuration status
     */
    public function getCompaniesStatus()
    {
        $companies = Company::active()->get()->map(function ($company) {
            return [
                'id' => $company->id,
                'name' => $company->name,
                'code' => $company->code,
                'is_configured' => $this->whatsappService->isCompanyConfigured($company),
                'has_api_key' => !empty($company->whatsapp_api_key)
            ];
        });

        return response()->json([
            'success' => true,
            'companies' => $companies
        ]);
    }

    /**
     * Send desk pickup notification to parcel recipient
     */
    public function sendDeskPickupNotification(Parcel $parcel)
    {
        $result = $this->whatsappService->sendDeskPickupNotification($parcel);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'message' => 'Desk pickup notification sent successfully'
            ]);
        } else {
            return response()->json([
                'success' => false,
                'error' => $result['error'] ?? 'Failed to send notification'
            ], 500);
        }
    }

    /**
     * Verify WhatsApp phone numbers for a specific parcel
     */
    public function verifyParcelPhoneNumbers(Parcel $parcel)
    {
        if (!$this->whatsappService->isCompanyConfigured($parcel->company)) {
            return response()->json([
                'success' => false,
                'error' => 'WhatsApp integration not configured for this company'
            ], 400);
        }

        $result = $this->whatsappService->verifyParcelPhoneNumbers($parcel);

        return response()->json([
            'success' => true,
            'message' => 'Phone numbers verified successfully',
            'data' => $result
        ]);
    }

    /**
     * Bulk verify WhatsApp phone numbers for multiple parcels
     */
    public function bulkVerifyPhoneNumbers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parcel_ids' => 'required|array|min:1',
            'parcel_ids.*' => 'exists:parcels,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $result = $this->whatsappService->bulkVerifyPhoneNumbers($request->parcel_ids);
        return $result;
        return response()->json([
            'success' => true,
            'message' => 'Bulk phone verification completed',
            'data' => $result
        ]);
    }

    /**
     * Check if a specific phone number is on WhatsApp
     */
    public function checkPhoneOnWhatsApp(Request $request, Company $company)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (!$this->whatsappService->isCompanyConfigured($company)) {
            return response()->json([
                'success' => false,
                'error' => 'WhatsApp integration not configured for this company'
            ], 400);
        }

        $result = $this->whatsappService->checkPhoneOnWhatsApp($request->phone_number, $company);

        return response()->json($result);
    }
}