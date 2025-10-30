<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\Collection;
use App\Models\MoneyCase;
use App\Models\Expense;
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

        // Calculate statistics
        $stats = $this->calculateStats($startDate, $endDate);
        
        // Get recent parcels
        $recentParcels = $this->getRecentParcels($startDate, $endDate);
        
        // Get money case statistics
        $caseStats = $this->getCaseStats($startDate, $endDate);
        
        // Get daily statistics for charts
        $dailyStats = $this->getDailyStats($startDate, $endDate);
        
        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentParcels' => $recentParcels,
            'caseStats' => $caseStats,
            'dailyStats' => $dailyStats,
            'filters' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ]);
    }
    
    /**
     * Calculate dashboard statistics.
     */
    private function calculateStats(Carbon $startDate, Carbon $endDate)
    {
        // Total parcels created within range
        $totalParcels = Parcel::whereBetween('created_at', [$startDate, $endDate])->count();
    
        // Pending parcels created within range (not delivered or returned)
        $pendingParcels = Parcel::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', ['delivered', 'returned'])
            ->count();
    
        // Delivered parcels delivered within range
        $deliveredParcels = Parcel::where('status', 'delivered')
            ->whereBetween('delivered_at', [$startDate, $endDate])
            ->count();
    
        // Revenue: sum of collections' margin within range
        $totalRevenue = Collection::whereBetween('collected_at', [$startDate, $endDate])->sum('margin');
    
        // Collected parcels by type (stopdesk vs home delivery)
        $stopdeskCollectedCount = Collection::whereBetween('collected_at', [$startDate, $endDate])
            ->where('parcel_type', 'stopdesk')
            ->count();
        $homeDeliveryCollectedCount = Collection::whereBetween('collected_at', [$startDate, $endDate])
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
    private function getRecentParcels(Carbon $startDate, Carbon $endDate)
    {
        return Parcel::with(['company', 'state', 'city'])
            ->whereBetween('created_at', [$startDate, $endDate])
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
    private function getCaseStats(Carbon $startDate, Carbon $endDate)
    {
        // Get all active money cases with their balances and user information
        $activeCases = MoneyCase::where('status', 'active')
            ->with('lastActiveUser:id,uid')
            ->withCount([
                'collections as collections_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('collected_at', [$startDate, $endDate]);
                },
                'expenses as expenses_count' => function ($q) use ($startDate, $endDate) {
                    $q->whereBetween('created_at', [$startDate, $endDate]);
                },
            ])
            ->get()
            ->map(function ($case) use ($startDate, $endDate) {
                $collectionsSum = $case->collections()
                    ->whereBetween('collected_at', [$startDate, $endDate])
                    ->whereDoesntHave('recoltes')
                    ->sum('amount');
                $expensesSum = $case->expenses()
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount');
                $case->filtered_balance = (float) $collectionsSum - (float) $expensesSum;
                return $case;
            });
        
        // Calculate totals within range
        $totalBalance = $activeCases->sum('filtered_balance');
        $totalCollections = Collection::whereBetween('collected_at', [$startDate, $endDate])
            ->whereDoesntHave('recoltes')
            ->sum('amount');
        $totalExpenses = Expense::where('status', 'approved')
            ->whereBetween('approved_at', [$startDate, $endDate])
            ->sum('amount');
        $pendingExpenses = Expense::where('status', 'pending')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount');
        
        return [
            'total_balance' => $totalBalance,
            'total_collections' => $totalCollections,
            'total_expenses' => $totalExpenses,
            'pending_expenses' => $pendingExpenses,
            'active_cases_count' => $activeCases->count(),
            'cases' => $activeCases->take(5)
        ];
    }
    
    /**
     * Get daily statistics for charts.
     */
    private function getDailyStats(Carbon $startDate, Carbon $endDate)
    {
        $dailyData = [];
        $currentDate = $startDate->copy();
        
        // Loop through each day in the range
        while ($currentDate->lte($endDate)) {
            $dayStart = $currentDate->copy()->startOfDay();
            $dayEnd = $currentDate->copy()->endOfDay();
            
            // Revenue (margin from collections)
            $revenue = Collection::whereBetween('collected_at', [$dayStart, $dayEnd])
                ->sum('margin');
            
            // Money collected (total amount from collections)
            $moneyCollected = Collection::whereBetween('collected_at', [$dayStart, $dayEnd])
                ->whereDoesntHave('recoltes')
                ->sum('amount');
            
            // Number of delivered parcels
            $deliveredParcels = Parcel::where('status', 'delivered')
                ->whereBetween('delivered_at', [$dayStart, $dayEnd])
                ->count();
            
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
}