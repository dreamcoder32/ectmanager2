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
use Smalot\PdfParser\Parser as PdfParser;

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
        $drivers = Driver::active()->select('id', 'name')->orderBy('name')->get();

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
     * Parse the uploaded PDF to extract tracking numbers and an optional guessed driver name
     */
    public function parse(Request $request)
    {
        $request->validate([
            'pdf_file' => 'required|file|mimes:pdf|max:20480', // up to 20MB
        ]);

        $file = $request->file('pdf_file');
        $parser = new PdfParser();
        $pdf = $parser->parseFile($file->getPathname());
        $text = $pdf->getText();

        // Extract tracking numbers: long alphanumeric sequences (12-30 chars)
        preg_match_all('/[A-Z0-9]{12,30}/', $text, $matches);
        $candidates = array_values(array_unique($matches[0] ?? []));

        // Try to guess driver name from occurrences after the word "Livreur"
        $guessDriverName = null;
        if (preg_match_all('/Livreur\s*([A-Za-zÀ-ÿ0-9\s\-]+)/u', $text, $dmatches)) {
            $names = array_map(function ($n) { return trim($n); }, $dmatches[1]);
            $names = array_filter($names, fn($n) => strlen($n) > 0);
            if (!empty($names)) {
                // Choose the most frequent name candidate
                $counts = array_count_values($names);
                arsort($counts);
                $guessDriverName = array_key_first($counts);
            }
        }

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
            'case_id' => 'nullable|exists:money_cases,id',
            'note' => 'nullable|string|max:1000',
        ]);

        $driver = Driver::findOrFail($request->driver_id);

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

                // Calculate margin: company home delivery commission minus driver commission
                $companyCommission = $parcel->company ? ($parcel->company->home_delivery_commission ?? 0) : 0;
                $margin = max(0, $companyCommission - floatval($request->driver_commission));

                // Create the collection record for home delivery
                $collection = Collection::create([
                    'collected_at' => now(),
                    'parcel_id' => $parcel->id,
                    'created_by' => Auth::id(),
                    'note' => $request->note ? $request->note : 'Driver settlement import',
                    'amount' => $parcel->cod_amount,
                    'amount_given' => $parcel->cod_amount,
                    'driver_id' => $driver->id,
                    'margin' => $margin,
                    'driver_commission' => $request->driver_commission,
                    'case_id' => $request->case_id,
                    'parcel_type' => 'home_delivery',
                ]);

                $collectionIds[] = $collection->id;
            }

            // Create recolte and attach collections
            $recolte = Recolte::create([
                'note' => ($request->note ? $request->note.' | ' : '').'Auto-created from driver settlement for '.$driver->name,
                'created_by' => Auth::id(),
            ]);

            if (!empty($collectionIds)) {
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
            }

            DB::commit();

            return redirect()->route('recoltes.show', ['recolte' => $recolte->id])
                ->with('success', "Recolte #{$recolte->code} created successfully with ".count($collectionIds)." collections.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to process driver settlement: ' . $e->getMessage()]);
        }
    }
}