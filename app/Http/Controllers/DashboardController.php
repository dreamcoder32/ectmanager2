<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\Collection;
use App\Models\MoneyCase;
use App\Models\Expense;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display the dashboard with statistics and recent parcels.
     */
    public function index()
    {
        // Calculate statistics
        $stats = $this->calculateStats();
        
        // Get recent parcels
        $recentParcels = $this->getRecentParcels();
        
        // Get money case statistics
        $caseStats = $this->getCaseStats();
        
        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentParcels' => $recentParcels,
            'caseStats' => $caseStats
        ]);
    }
    
    /**
     * Calculate dashboard statistics.
     */
    private function calculateStats()
    {
        // Total parcels count
        $totalParcels = Parcel::count();
        
        // Pending parcels count (not delivered)
        $pendingParcels = Parcel::whereNotIn('status', ['delivered', 'returned'])->count();
        
        // Delivered parcels count
        $deliveredParcels = Parcel::where('status', 'delivered')->count();
        
        // Total revenue from delivered parcels
        $totalRevenue = Parcel::where('status', 'delivered')
            ->sum('cod_amount');
        
        return [
            'total_parcels' => $totalParcels,
            'pending_parcels' => $pendingParcels,
            'delivered_parcels' => $deliveredParcels,
            'total_revenue' => $totalRevenue
        ];
    }
    
    /**
     * Get recent parcels for the dashboard.
     */
    private function getRecentParcels()
    {
        return Parcel::with(['company', 'state', 'city'])
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
    private function getCaseStats()
    {
        // Get all active money cases with their balances and user information
        $activeCases = MoneyCase::where('status', 'active')
            ->with('lastActiveUser:id,uid') // Load the user who last activated the case
            ->withCount(['collections', 'expenses'])
            ->get()
            ->map(function ($case) {
                $case->calculated_balance = $case->calculateBalance();
                return $case;
            });
        
        // Calculate totals
        $totalBalance = $activeCases->sum('calculated_balance');
        $totalCollections = Collection::sum('amount');
        $totalExpenses = Expense::where('status', 'approved')->sum('amount');
        $pendingExpenses = Expense::where('status', 'pending')->sum('amount');
        
        return [
            'total_balance' => $totalBalance,
            'total_collections' => $totalCollections,
            'total_expenses' => $totalExpenses,
            'pending_expenses' => $pendingExpenses,
            'active_cases_count' => $activeCases->count(),
            'cases' => $activeCases->take(5) // Top 5 cases for quick view
        ];
    }
}