<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Parcel;
use App\Services\EcoTrackService;

class TrackingVerificationController extends Controller
{
    protected $ecoTrackService;

    public function __construct(EcoTrackService $ecoTrackService)
    {
        $this->ecoTrackService = $ecoTrackService;
    }

    public function index()
    {
        return Inertia::render('TrackingVerification/Index');
    }

    public function verify(Request $request)
    {
        $request->validate([
            'tracking_number' => 'required|string',
        ]);

        $trackingNumber = $request->input('tracking_number');
        $result = $this->ecoTrackService->getTrackingStatus($trackingNumber);

        // Find internal parcel
        $parcel = Parcel::where('tracking_number', $trackingNumber)
            ->orWhere('reference', $trackingNumber)
            ->first();

        if ($parcel) {
            $result['internal_status'] = $parcel->status;
            $result['internal_updated_at'] = $parcel->updated_at?->format('Y-m-d H:i:s');
            $result['recipient_name'] = $parcel->recipient_name;
            $result['recipient_phone'] = $parcel->recipient_phone;
        } else {
            $result['internal_status'] = 'Not Found';
        }

        return back()->with('result', $result);
    }
}
