<?php

namespace App\Http\Controllers;

use App\Models\Parcel;
use App\Models\Collection;
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
        
        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentParcels' => $recentParcels
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
}