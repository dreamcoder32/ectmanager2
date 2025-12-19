<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use App\Models\Collection;
use App\Models\MoneyCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ParcelController extends Controller
{
    public function index(Request $request)
    {
        $query = Parcel::query()
            ->with(['company', 'assignedDriver', 'state', 'city'])
            ->withCount([
                'smsLogs',
                'smsLogs as sms_logs_today_count' => function ($query) {
                    $query->whereDate('sent_at', now()->today());
                }
            ]);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('receiver_phone', 'like', "%{$search}%")
                    // Include reference in API search to support barcode scans by reference
                    ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 15), 100);


        // Sort by status (pending first) then by creation date
        $parcels = $query->orderByRaw("CASE WHEN status = 'pending' THEN 1 ELSE 2 END")
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json($parcels);
    }

    public function show(Parcel $parcel)
    {
        $parcel->load(['company', 'assignedDriver', 'state', 'city', 'collections']);
        $parcel->loadCount('smsLogs');
        return response()->json($parcel);
    }

    public function logSms(Request $request, Parcel $parcel)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $parcel->smsLogs()->create([
            'user_id' => $request->user()->id,
            'message' => $request->message,
            'sent_at' => now(),
        ]);

        return response()->json(['message' => 'SMS logged successfully']);
    }

    /**
     * Get initial data for Stopdesk Payment page
     */
    public function getStopdeskData(Request $request)
    {
        $user = $request->user();
        $currentUserId = $user->id;

        // Get recent collections
        $recentCollections = Collection::with(['parcel'])
            ->where('created_by', $currentUserId)
            ->whereDoesntHave('recoltes')
            ->orderBy('collected_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($collection) {
                return [
                    'id' => $collection->id,
                    'tracking_number' => $collection->parcel->tracking_number ?? 'N/A',
                    'cod_amount' => $collection->parcel->cod_amount ?? 0,
                    'collected_at' => $collection->collected_at,
                    'changeAmount' => $collection->amount_given - ($collection->parcel->cod_amount ?? 0),
                    'parcel_type' => $collection->parcel_type ?? 'stopdesk',
                    'amount_collected' => $collection->amount_given,
                ];
            });

        // Get active money cases
        if ($user->role === 'admin') {
            $activeCases = MoneyCase::where('status', 'active')
                ->where(function ($query) use ($currentUserId) {
                    $query->whereNull('last_active_by')
                        ->orWhere('last_active_by', $currentUserId);
                })
                ->orderBy('name')
                ->get();
        } else {
            $userCompanyIds = $user->companies()->pluck('companies.id');
            $activeCases = MoneyCase::where('status', 'active')
                ->whereIn('company_id', $userCompanyIds)
                ->where(function ($query) use ($currentUserId) {
                    $query->whereNull('last_active_by')
                        ->orWhere('last_active_by', $currentUserId);
                })
                ->orderBy('name')
                ->get();
        }

        $activeCases = $activeCases->map(function ($case) use ($currentUserId) {
            return [
                'id' => $case->id,
                'name' => $case->name,
                'description' => $case->description,
                'balance' => $case->calculated_balance,
                'currency' => 'DA',
                'is_user_active' => $case->last_active_by === $currentUserId,
            ];
        });

        $userLastActiveCase = MoneyCase::where('status', 'active')
            ->where('last_active_by', $currentUserId)
            ->orderBy('last_activated_at', 'desc')
            ->first();

        return response()->json([
            'recentCollections' => $recentCollections,
            'activeCases' => $activeCases,
            'userLastActiveCaseId' => $userLastActiveCase ? $userLastActiveCase->id : null,
        ]);
    }

    /**
     * Search for a parcel by tracking number for stopdesk payment
     */
    public function searchStopdesk(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string',
        ]);

        $parcel = Parcel::with(['company', 'state', 'city'])
            ->where('tracking_number', $request->tracking_number)
            ->orWhere('reference', $request->tracking_number)
            ->first();

        if (!$parcel) {
            return response()->json([
                'success' => false,
                'message' => 'Parcel not found',
                'allow_manual_entry' => true,
            ]);
        }

        if ($parcel->status === 'delivered') {
            return response()->json([
                'success' => false,
                'message' => 'Parcel already delivered and collected',
                'allow_manual_entry' => false,
                'parcel_status' => 'delivered',
            ]);
        }

        return response()->json([
            'success' => true,
            'parcel' => [
                'id' => $parcel->id,
                'tracking_number' => $parcel->tracking_number,
                'recipient_name' => $parcel->recipient_name,
                'recipient_phone' => $parcel->recipient_phone,
                'recipient_address' => $parcel->recipient_address,
                'cod_amount' => $parcel->cod_amount,
                'status' => $parcel->status,
                'company' => $parcel->company, // Return full company object
                'state' => $parcel->state ? $parcel->state->name : null,
                'city' => $parcel->city ? $parcel->city->name : null,
                'reference' => $parcel->reference, // Ensure reference is included
            ],
        ]);
    }

    /**
     * Confirm payment for stopdesk collection
     */
    public function collectStopdesk(Request $request)
    {
        // Check if user can collect without money case
        // Assuming 'can_collect_stopdesk' is on the user model. If not, we might need to check how it's implemented.
        // In the web controller: auth()->user()->can_collect_stopdesk
        $canCollectWithoutCase = $request->user()->can_collect_stopdesk ?? false;

        $request->validate([
            'parcel_id' => 'required|exists:parcels,id',
            'amount_given' => 'required|numeric|min:0',
            'case_id' => $canCollectWithoutCase
                ? 'nullable|exists:money_cases,id'
                : 'required|exists:money_cases,id',
            'parcel_type' => 'required|in:stopdesk,home_delivery',
        ]);

        DB::beginTransaction();

        try {
            $parcel = Parcel::lockForUpdate()->findOrFail($request->parcel_id);

            if ($parcel->status === 'delivered' || $parcel->collections()->exists()) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Parcel is already delivered or collected'
                ], 400);
            }

            if ($request->amount_given < $parcel->cod_amount) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient payment amount'
                ], 400);
            }

            // Update parcel
            $parcel->update([
                'status' => 'delivered',
                'delivered_at' => now(),
                'amount_paid' => $parcel->cod_amount,
            ]);

            // Calculate margin
            $margin = 0;
            if ($parcel->company) {
                if ($request->parcel_type === 'stopdesk') {
                    $margin = $parcel->company->commission ?? 0;
                } elseif ($request->parcel_type === 'home_delivery') {
                    $margin = $parcel->company->home_delivery_commission ?? 0;
                }
            }

            // Create collection
            Collection::create([
                'collected_at' => now(),
                'parcel_id' => $parcel->id,
                'created_by' => $request->user()->id,
                'note' => 'Stop desk payment collection',
                'amount' => $parcel->cod_amount,
                'amount_given' => $request->amount_given,
                'driver_id' => null,
                'margin' => $margin,
                'driver_commission' => null,
                'case_id' => $request->case_id,
                'parcel_type' => $request->parcel_type,
            ]);

            DB::commit();

            // Return updated recent collections
            $recentCollections = Collection::with(['parcel'])
                ->where('created_by', $request->user()->id)
                ->whereDoesntHave('recoltes')
                ->orderBy('collected_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function ($collection) {
                    return [
                        'id' => $collection->id,
                        'tracking_number' => $collection->parcel->tracking_number ?? 'N/A',
                        'cod_amount' => $collection->parcel->cod_amount ?? 0,
                        'collected_at' => $collection->collected_at,
                        'changeAmount' => $collection->amount_given - ($collection->parcel->cod_amount ?? 0),
                        'parcel_type' => $collection->parcel_type ?? 'stopdesk',
                        'amount_collected' => $collection->amount_given,
                    ];
                });

            // Fetch updated active cases
            $currentUserId = $request->user()->id;
            $user = $request->user();

            if ($user->role === 'admin') {
                $activeCases = MoneyCase::where('status', 'active')
                    ->where(function ($query) use ($currentUserId) {
                        $query->whereNull('last_active_by')
                            ->orWhere('last_active_by', $currentUserId);
                    })
                    ->orderBy('name')
                    ->get();
            } else {
                $userCompanyIds = $user->companies()->pluck('companies.id');
                $activeCases = MoneyCase::where('status', 'active')
                    ->whereIn('company_id', $userCompanyIds)
                    ->where(function ($query) use ($currentUserId) {
                        $query->whereNull('last_active_by')
                            ->orWhere('last_active_by', $currentUserId);
                    })
                    ->orderBy('name')
                    ->get();
            }

            $activeCases = $activeCases->map(function ($case) use ($currentUserId) {
                return [
                    'id' => $case->id,
                    'name' => $case->name,
                    'description' => $case->description,
                    'balance' => $case->calculated_balance,
                    'currency' => 'DA',
                    'is_user_active' => $case->last_active_by === $currentUserId,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Collection successful',
                'recentCollections' => $recentCollections,
                'activeCases' => $activeCases,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Stopdesk collection error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], 500);
        }
    }
}