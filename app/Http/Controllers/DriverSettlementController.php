<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\Collection;
use App\Models\Recolte;
use App\Models\MoneyCase;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory; // Added for XLSX parsing

class DriverSettlementController extends Controller
{
    public function __construct()
    {
        // Only admin and supervisor can access this feature (align with RecolteController)
        $this->middleware(function ($request, $next) {
            if (!Auth::user()->hasRole(['admin', 'supervisor'])) {
                abort(403, 'Unauthorized. Only admin and supervisor can perform driver settlement operations.');
            }
            return $next($request);
        });
    }

    /**
     * Show the page to import a driver settlement PDF and process collections + recolte
     */
    public function index()
    {
        // Include commission fields so UI can auto-fill commission when driver is selected
        $drivers = Driver::active()
            ->select('id', 'name', 'commission_rate', 'commission_type', 'commission_is_active')
            ->orderBy('name')
            ->get();

        // Get active money cases
        $activeCases = MoneyCase::where('status', 'active')
            ->select('id', 'name', 'description', 'balance')
            ->orderBy('name')
            ->get();

        return Inertia::render('DriverSettlement/Import', [
            'drivers' => $drivers,
            'activeCases' => $activeCases,
        ]);
    }

    /**
     * Parse the uploaded XLSX to extract tracking numbers and an optional guessed driver name
     */
    public function parse(Request $request)
    {
        $request->validate([
            'xlsx_file' => 'required|file|mimes:xlsx,xls|max:20480', // up to 20MB
        ]);

        $file = $request->file('xlsx_file');

        // Load spreadsheet and read rows as array
        $spreadsheet = IOFactory::load($file->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true); // rows as arrays keyed by column letters

        $candidates = [];
        $guessDriverName = null;
        $trackingColumn = null;
        $headerRowIndex = null;

        // Attempt to detect a header row and tracking column within the first 10 rows
        $headerHints = ['tracking', 'suivi', 'awb', 'barcode', 'code barre', 'codebarre', 'code-barre'];
        $maxHeaderScan = 10;
        $scanned = 0;
        foreach ($rows as $rIndex => $row) {
            if ($scanned >= $maxHeaderScan) {
                break;
            }
            foreach ($row as $col => $val) {
                $text = strtolower(trim((string) $val));
                if ($text === '') continue;
                foreach ($headerHints as $hint) {
                    if (strpos($text, $hint) !== false) {
                        $trackingColumn = $col;
                        $headerRowIndex = $rIndex;
                        break 2;
                    }
                }
                // Guess driver name from cells containing keywords
                if ($guessDriverName === null) {
                    if (preg_match('/(livreur|driver)\s*:?\s*(.+)/i', (string) $val, $m)) {
                        $guessDriverName = trim($m[2]);
                    }
                }
            }
            $scanned++;
        }

        if ($trackingColumn !== null) {
            // Read tracking values from the detected column, below the header
            $afterHeader = false;
            foreach ($rows as $rIndex => $row) {
                if (!$afterHeader) {
                    if ($rIndex === $headerRowIndex) {
                        $afterHeader = true;
                        continue; // skip the header row itself
                    }
                    continue; // skip rows before header
                }
                $val = isset($row[$trackingColumn]) ? (string) $row[$trackingColumn] : '';
                $raw = strtoupper(trim($val));
                // Normalize by removing non-alphanumerics
                $normalized = preg_replace('/[^A-Z0-9]/', '', $raw);
                if ($normalized && preg_match('/^[A-Z0-9]{8,30}$/', $normalized)) {
                    $candidates[] = $normalized;
                }
            }
        }

        // Fallback: scan all cells for tracking-like values if no column was found or very few candidates
        if ($trackingColumn === null || count($candidates) === 0) {
            foreach ($rows as $row) {
                foreach ($row as $val) {
                    $text = strtoupper((string) $val);
                    if ($text === '') continue;
                    // Extract long alphanumeric sequences (8-30 chars)
                    if (preg_match_all('/[A-Z0-9]{8,30}/', $text, $matches)) {
                        foreach ($matches[0] as $m) {
                            $candidates[] = $m;
                        }
                    }
                    // Guess driver name
                    if ($guessDriverName === null && preg_match('/(LIVREUR|DRIVER)\s*:?\s*([A-ZÀ-ÿ0-9\s\-]+)/u', $text, $dm)) {
                        $guessDriverName = trim($dm[2]);
                    }
                }
            }
        }

        // Unique the candidates
        $candidates = array_values(array_unique($candidates));

        // Fetch parcels matching extracted tracking numbers
        $parcels = Parcel::with('company')
            ->whereIn('tracking_number', $candidates)
            ->get();

        $foundTrackingNumbers = $parcels->pluck('tracking_number')->toArray();
        $missingTrackingNumbers = array_values(array_diff($candidates, $foundTrackingNumbers));

        $items = $parcels->map(function ($parcel) {
            return [
                'id' => $parcel->id,
                'tracking_number' => $parcel->tracking_number,
                'cod_amount' => $parcel->cod_amount,
                'company_name' => $parcel->company ? $parcel->company->name : null,
                'company_home_delivery_commission' => $parcel->company ? ($parcel->company->home_delivery_commission ?? 0) : 0,
                'status' => $parcel->status,
            ];
        });

        return response()->json([
            'success' => true,
            'guess_driver_name' => $guessDriverName,
            'found' => $items,
            'missing' => $missingTrackingNumbers,
            'total_candidates' => count($candidates),
            'total_found' => count($items),
            'total_missing' => count($missingTrackingNumbers),
        ]);
    }

    /**
     * Process selected tracking numbers: create collections for each parcel and create a recolte immediately
     */
    public function process(Request $request)
    {
        $request->validate([
            'tracking_numbers' => 'required|array|min:1',
            'tracking_numbers.*' => 'string',
            'driver_id' => 'required|exists:drivers,id',
            'driver_commission' => 'required|numeric|min:0',
            'parcel_commissions' => 'sometimes|array', // optional per-parcel overrides keyed by tracking number
            'parcel_commissions.*' => 'numeric|min:0',
            'case_id' => 'nullable|exists:money_cases,id',
            'note' => 'nullable|string|max:1000',
            'manual_amount' => 'required|numeric|min:0',
            'amount_discrepancy_note' => 'nullable|string|max:1000'
        ]);

        $driver = Driver::findOrFail($request->driver_id);
        $wantsJson = $request->expectsJson();

        DB::beginTransaction();
        try {
            $collectionIds = [];
            $skipped = [];

            foreach ($request->tracking_numbers as $tn) {
                $parcel = Parcel::with('company')->where('tracking_number', $tn)->first();
                if (!$parcel) {
                    $skipped[] = ['tracking_number' => $tn, 'reason' => 'parcel_not_found'];
                    continue;
                }

                // If already delivered and has a collection, skip to avoid duplicates
                if ($parcel->status === 'delivered') {
                    $hasExistingCollection = $parcel->collections()->exists();
                    if ($hasExistingCollection) {
                        $skipped[] = ['tracking_number' => $tn, 'reason' => 'already_collected'];
                        continue;
                    }
                }

                // Mark parcel as delivered (payment collected by driver)
                $parcel->update([
                    'status' => 'delivered',
                    'delivered_at' => now(),
                ]);

                // Determine commission for this parcel: per-parcel override or global driver commission
                $commissionForParcel = null;
                if (is_array($request->parcel_commissions) && array_key_exists($tn, $request->parcel_commissions)) {
                    $commissionForParcel = floatval($request->parcel_commissions[$tn]);
                } else {
                    $commissionForParcel = floatval($request->driver_commission);
                }

                // Calculate margin: company home delivery commission minus driver commission
                $companyCommission = $parcel->company ? ($parcel->company->home_delivery_commission ?? 0) : 0;
                $margin = max(0, $companyCommission - $commissionForParcel);

                // Create the collection record for home delivery
                $netAmount = max(0, (float) ($parcel->cod_amount ?? 0) - (float) $commissionForParcel);
                $collection = Collection::create([
                    'collected_at' => now(),
                    'parcel_id' => $parcel->id,
                    'created_by' => Auth::id(),
                    'note' => $request->note ? $request->note : 'Driver settlement import',
                    'amount' => $netAmount,
                    'amount_given' => $parcel->cod_amount,
                    'driver_id' => $driver->id,
                    'margin' => $margin,
                    'driver_commission' => $commissionForParcel,
                    'case_id' => $request->case_id,
                    'parcel_type' => 'home_delivery',
                ]);

                $collectionIds[] = $collection->id;
            }

            // If no collections created, avoid creating an empty recolte
            if (empty($collectionIds)) {
                DB::rollBack();
                if ($wantsJson) {
                    return response()->json([
                        'success' => false,
                        'error' => 'No collections were created. Parcels may already be collected or not found.',
                        'skipped' => $skipped,
                    ], 422);
                }
                return back()->withErrors(['error' => 'No collections were created. Parcels may already be collected or not found.']);
            }

            // Calculate total amount from collections and validate manual amount
            $collections = Collection::whereIn('id', $collectionIds)->get();
            $totalAmount = $collections->sum('amount');
            $manualAmount = $request->manual_amount;
            
            // Check for discrepancy and validate note requirement
            $hasDiscrepancy = abs($totalAmount - $manualAmount) > 0.01; // Allow for small rounding differences
            if ($hasDiscrepancy && empty($request->amount_discrepancy_note)) {
                DB::rollBack();
                if ($wantsJson) {
                    return response()->json([
                        'success' => false,
                        'error' => 'A note explaining the amount discrepancy is required when the manual amount differs from the calculated total.'
                    ], 422);
                }
                return back()->withErrors([
                    'amount_discrepancy_note' => 'A note explaining the amount discrepancy is required when the manual amount differs from the calculated total.'
                ])->withInput();
            }

            // Determine company from collections' parcels
            $companyIds = $collections->pluck('parcel.company_id')->unique()->filter();
            if ($companyIds->count() === 0) {
                DB::rollBack();
                throw new \Exception('No valid company found for the selected collections.');
            }
            if ($companyIds->count() > 1) {
                DB::rollBack();
                throw new \Exception('All collections must belong to the same company.');
            }
            $companyId = $companyIds->first();

            // Create recolte and attach collections
            $recolte = Recolte::create([
                'note' => ($request->note ? $request->note.' | ' : '').'Auto-created from driver settlement for '.$driver->name,
                'created_by' => Auth::id(),
                'company_id' => $companyId,
                'manual_amount' => $manualAmount,
                'amount_discrepancy_note' => $hasDiscrepancy ? $request->amount_discrepancy_note : null
            ]);

            // Update money case if provided and assign to collections
            if ($request->case_id) {
                $collections = Collection::whereIn('id', $collectionIds)->get();
                $collections->each(function ($c) use ($request) { $c->update(['case_id' => $request->case_id]); });
            }

            // Attach collections to recolte first
            $recolte->collections()->attach($collectionIds);

            // Then update money case balance to reflect recolted collections
            if ($request->case_id) {
                $moneyCase = MoneyCase::find($request->case_id);
                if ($moneyCase) {
                    $moneyCase->updateBalance();
                }
            }

            DB::commit();

            $message = "Recolte #{$recolte->code} created successfully with ".count($collectionIds)." collections.";
            $message .= " Calculated total: " . number_format($totalAmount, 2) . " DZD, Manual amount: " . number_format($manualAmount, 2) . " DZD";
            
            if ($hasDiscrepancy) {
                $message .= " (Discrepancy noted)";
            }

            if ($wantsJson) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'recolte_id' => $recolte->id,
                    'redirect' => route('recoltes.show', ['recolte' => $recolte->id])
                ]);
            }

            return redirect()->route('recoltes.show', ['recolte' => $recolte->id])
                ->with('success', $message);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($wantsJson) {
                return response()->json([
                    'success' => false,
                    'error' => 'Failed to process driver settlement: ' . $e->getMessage()
                ], 500);
            }
            return back()->withErrors(['error' => 'Failed to process driver settlement: ' . $e->getMessage()]);
        }
    }
}