<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Parcel;
use Illuminate\Http\Request;

class ParcelController extends Controller
{
    public function index(Request $request)
    {
        $query = Parcel::query()->with(['company', 'assignedDriver', 'state', 'city']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('tracking_number', 'like', "%{$search}%")
                  ->orWhere('recipient_name', 'like', "%{$search}%")
                  ->orWhere('recipient_phone', 'like', "%{$search}%")
                  // Include reference in API search to support barcode scans by reference
                  ->orWhere('reference', 'like', "%{$search}%");
            });
        }

        $perPage = min($request->get('per_page', 15), 100);
        $parcels = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($parcels);
    }

    public function show(Parcel $parcel)
    {
        $parcel->load(['company', 'assignedDriver', 'state', 'city', 'collections']);
        return response()->json($parcel);
    }
}