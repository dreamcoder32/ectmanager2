<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\Collection;
use App\Models\MoneyCase;
use App\Models\Expense;
use App\Models\Company;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with statistics and recent parcels.
     */
    public function index(Request $request)
    {
        // Parse date filters, default to today
        $startDate = Carbon::parse($request->query('start', now()->toDateString()))->startOfDay();
        $endDate = Carbon::parse($request->query('end', now()->toDateString()))->endOfDay();
        
        // Get authenticated user
        $user = auth()->user();
        
        // Get company filter (null means all companies)
        $companyId = $request->query('company_id');
        $companyId = $companyId === 'all' || $companyId === null ? null : (int) $companyId;
        
        // Validate company access for non-admin users
        if ($companyId && $user->role !== 'admin') {
            if (!$user->belongsToCompany($companyId)) {
                $companyId = null; // Reset to show all accessible companies
            }
        }
        if ( $user->role == 'supervisor') {
           $companyId = $user->primaryCompany()->id;
        }

        // Get companies based on user's access
        if ($user->role === 'admin') {
            // Admins can see all companies
            $companies = Company::active()->orderBy('name')->get(['id', 'name', 'code']);
        } else {
            // Other users can only see their assigned companies
            $companies = $user->companies()->where('is_active', true)->orderBy('name')->get(['companies.id', 'companies.name', 'companies.code']);
        }

        // Calculate statistics
        $stats = $this->calculateStats($startDate, $endDate, $companyId);
        
        // Get recent parcels
        $recentParcels = $this->getRecentParcels($startDate, $endDate, $companyId);
        
        // Get money case statistics
        $caseStats = $this->getCaseStats($startDate, $endDate, $companyId);
        
        // Get daily statistics for charts
        $dailyStats = $this->getDailyStats($startDate, $endDate, $companyId);
        
        // Get recolted money statistics
        $recoltedStats = $this->getRecoltedStats($startDate, $endDate, $companyId);
        
        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentParcels' => $recentParcels,
            'caseStats' => $caseStats,
            'dailyStats' => $dailyStats,
            'recoltedStats' => $recoltedStats,
            'companies' => $companies,
            'filters' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
                'company_id' => $companyId,
            ],
        ]);
    }
    
    /**
     * Calculate dashboard statistics.
     */
    private function calculateStats(Carbon $startDate, Carbon $endDate, ?int $companyId = null)
    {
        // Build parcels query with company filter
        $parcelsQuery = Parcel::query();
        if ($companyId) {
            $parcelsQuery->where('company_id', $companyId);
        }
        
        // Total parcels created within range
        $totalParcels = (clone $parcelsQuery)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    
        // Pending parcels created within range (not delivered or returned)
        $pendingParcels = (clone $parcelsQuery)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', ['delivered', 'returned'])
            ->count();
    
        // Delivered parcels delivered within range
        $deliveredParcels = (clone $parcelsQuery)
            ->where('status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->count();
    
        // Build collections query with company filter
        $collectionsQuery = Collection::query();
        if ($companyId) {
                $collectionsQuery->whereHas('parcel', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
        }
    
        // Revenue: sum of collections' margin within range
        $totalRevenue = (clone $collectionsQuery)
            ->whereBetween('collected_at', [$startDate, $endDate])
            ->sum('margin');
    
        // Collected parcels by type (stopdesk vs home delivery)
        $stopdeskCollectedCount = (clone $collectionsQuery)
            ->whereBetween('collected_at', [$startDate, $endDate])
            ->where('parcel_type', 'stopdesk')
            ->count();
        $homeDeliveryCollectedCount = (clone $collectionsQuery)
            ->whereBetween('collected_at', [$startDate, $endDate])
            ->where('parcel_type', 'home_delivery')
            ->count();
    
        return [
            'total_parcels' => $totalParcels,
            'pending_parcels' => $pendingParcels,
            'delivered_parcels' => $deliveredParcels,
            'total_revenue' => (float) $totalRevenue,
            'stopdesk_collections_count' => $stopdeskCollectedCount,
            'home_delivery_collections_count' => $homeDeliveryCollectedCount,
        ];
    }
    
    /**
     * Get recent parcels for the dashboard.
     */
    private function getRecentParcels(Carbon $startDate, Carbon $endDate, ?int $companyId = null)
    {
        $query = Parcel::with(['company', 'state', 'city'])
            ->whereBetween('created_at', [$startDate, $endDate]);
            
        if ($companyId) {
            $query->where('company_id', $companyId);
        }
        
        return $query
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($parcel) {
                return [
                    'id' => $parcel->id,
                    'tracking_number' => $parcel->tracking_number,
                    'recipient_name' => $parcel->recipient_name,
                    'recipient_phone' => $parcel->recipient_phone,
                    'status' => $parcel->status,
                    'cod_amount' => $parcel->cod_amount,
                    'created_at' => $parcel->created_at,
                    'state' => $parcel->state ? ['name' => $parcel->state->name] : null,
                    'city' => $parcel->city ? ['name' => $parcel->city->name] : null,
                ];
            });
    }
    
    /**
     * Get money case statistics for the dashboard.
     */
    private function getCaseStats(Carbon $startDate, Carbon $endDate, ?int $companyId = null)
    {
        // Build money cases query with company filter
        $casesQuery = MoneyCase::where('status', 'active');
        if ($companyId) {
            $casesQuery->where('company_id', $companyId);
        }
        
        // Get all active money cases with their balances and user information
        $activeCases = $casesQuery
            ->with('lastActiveUser:id,uid')
            ->withCount([
                'collections as collections_count' => function ($q) use ($startDate, $endDate, $companyId) {
                    $q->whereBetween('collected_at', [$startDate, $endDate]);
                    if ($companyId) {
                        $q->whereHas('parcel', function ($pq) use ($companyId) {
                            $pq->where('company_id', $companyId);
                        });
                    }
                },
                'expenses as expenses_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                },
            ])
            ->get()
            ->map(function ($case) use ($startDate, $endDate, $companyId) {
                $collectionsQuery = $case->collections()
                    ->whereBetween('collected_at', [$startDate, $endDate])
                    ->whereDoesntHave('recoltes');
                    
                if ($companyId) {
                    $collectionsQuery->whereHas('parcel', function ($q) use ($companyId) {
                        $q->where('company_id', $companyId);
                    });
                }
                
                $collectionsSum = $collectionsQuery->sum('amount');
                $expensesSum = $case->expenses()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount');
                $case->filtered_balance = (float) $collectionsSum - (float) $expensesSum;
                return $case;
            });
        
        // Calculate totals within range
        $totalBalance = $activeCases->sum('filtered_balance');
        
        // Build collections query with company filter
        $collectionsQuery = Collection::whereBetween('collected_at', [$startDate, $endDate])
            ->whereDoesntHave('recoltes');
        if ($companyId) {
            $collectionsQuery->whereHas('parcel', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }
        $totalCollections = $collectionsQuery->sum('amount');
        
        $totalExpenses = Expense::where('status', 'approved')
            ->whereBetween('approved_at', [$startDate, $endDate]);
        if ($companyId) {
            $totalExpenses->whereHas('moneyCase', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }
        $totalExpenses = $totalExpenses->sum('amount');
        
        $pendingExpenses = Expense::where('status', 'pending')
            ->whereBetween('created_at', [$startDate, $endDate]);
        if ($companyId) {
            $pendingExpenses->whereHas('moneyCase', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            });
        }
        $pendingExpenses = $pendingExpenses->sum('amount');
        
        return [
            'total_balance' => $totalBalance,
            'total_collections' => $totalCollections,
            'total_expenses' => $totalExpenses,
            'pending_expenses' => $pendingExpenses,
            'active_cases_count' => $activeCases->count(),
            'cases' => $activeCases
        ];
    }
    
    /**
     * Get daily statistics for charts.
     */
    private function getDailyStats(Carbon $startDate, Carbon $endDate, ?int $companyId = null)
    {
        $dailyData = [];
        $currentDate = $startDate->copy();
        
        // Loop through each day in the range
        while ($currentDate->lte($endDate)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();
            
            // Revenue (margin from collections)
            $revenueQuery = Collection::whereBetween('collected_at', [$dayStart, $dayEnd]);
            if ($companyId) {
                $revenueQuery->whereHas('parcel', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            }
            $revenue = $revenueQuery->sum('margin');
            
            // Money collected (total amount from collections)
            $moneyCollectedQuery = Collection::whereBetween('collected_at', [$dayStart, $dayEnd]);
            if ($companyId) {
                $moneyCollectedQuery->whereHas('parcel', function ($q) use ($companyId) {
                    $q->where('company_id', $companyId);
                });
            }
            $moneyCollected = $moneyCollectedQuery->sum('amount');
            
            // Number of delivered parcels
            $deliveredQuery = Parcel::where('status', 'delivered')
                ->whereBetween('delivered_at', [$dayStart, $dayEnd]);
            if ($companyId) {
                $deliveredQuery->where('company_id', $companyId);
            }
            $deliveredParcels = $deliveredQuery->count();
            
            $dailyData[] = [
                'date' => $currentDate->format('Y-m-d'),
                'date_formatted' => $currentDate->format('M d'),
                'revenue' => (float) $revenue,
                'money_collected' => (float) $moneyCollected,
                'delivered_parcels' => $deliveredParcels,
            ];
            
            $currentDate->addDay();
        }
        
        return $dailyData;
    }
    
    /**
     * Get recolted money statistics.
     */
    private function getRecoltedStats(Carbon $startDate, Carbon $endDate, ?int $companyId = null)
    {
        // Build recoltes query with company filter
        $recoltesQuery = \App\Models\Recolte::whereBetween('created_at', [$startDate, $endDate]);
        
        if ($companyId) {
            $recoltesQuery->where('company_id', $companyId);
        }
        
        // Get recoltes with their collections
        $recoltes = $recoltesQuery->with('collections')->get();
        
        // Calculate total recolted amount (sum of all collections in recoltes)
        $totalRecoltedAmount = $recoltes->sum(function ($recolte) {
            return $recolte->collections->sum('amount');
        });
        
        // Calculate total manual amount (sum of manual amounts entered by users)
        // Only include recoltes that have manual_amount > 0 (ignore old recoltes without manual amount)
        $totalManualAmount = $recoltes->where('manual_amount', '>', 0)->sum('manual_amount');
        
        // Count recoltes
        $recoltesCount = $recoltes->count();
        
        // Calculate discrepancy count
        // Only count discrepancies for recoltes that have manual_amount > 0
        $discrepancyCount = $recoltes->filter(function ($recolte) {
            if (!$recolte->manual_amount || $recolte->manual_amount <= 0) return false;
            $calculatedAmount = $recolte->collections->sum('amount');
            return abs($calculatedAmount - $recolte->manual_amount) > 0.01;
        })->count();
        
        // Get recent recoltes for display
        $recentRecoltes = $recoltes->map(function ($recolte) {
            $calculatedAmount = $recolte->collections->sum('amount');
            $manualAmount = $recolte->manual_amount; // Pass the raw value from database
            $hasDiscrepancy = $manualAmount && $manualAmount > 0 && abs($calculatedAmount - $manualAmount) > 0.01;
            
            return [
                'id' => $recolte->id,
                'code' => $recolte->code,
                'created_at' => $recolte->created_at,
                'calculated_amount' => $calculatedAmount,
                'manual_amount' => $manualAmount,
                'has_discrepancy' => $hasDiscrepancy,
                'collections_count' => $recolte->collections->count(),
                'company_name' => $recolte->company ? $recolte->company->name : 'N/A'
            ];
        });
        
        return [
            'total_recolted_amount' => (float) $totalRecoltedAmount,
            'total_manual_amount' => (float) $totalManualAmount,
            'recoltes_count' => $recoltesCount,
            'discrepancy_count' => $discrepancyCount,
            'recent_recoltes' => $recentRecoltes
        ];
    }
}