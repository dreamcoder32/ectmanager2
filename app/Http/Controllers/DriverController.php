<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Dompdf\Dompdf;
use Dompdf\Options;

class DriverController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $drivers = Driver::with(['state:id,name'])
            ->select(['id', 'name', 'phone', 'license_number', 'vehicle_info', 'is_active', 'commission_rate', 'commission_type', 'commission_is_active', 'state_id'])
            ->orderBy('name')
            ->get();

        return Inertia::render('Drivers/Index', [
            'drivers' => $drivers,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Drivers/Create', [
            'states' => State::active()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string',
            'vehicle_info' => 'nullable|string',
            'is_active' => 'boolean',
            'commission_rate' => 'nullable|numeric|min:0|max:600',
            'commission_type' => ['nullable', Rule::in(['percentage', 'fixed_per_parcel'])],
            // 'commission_is_active' => 'boolean',
            'state_id' => 'nullable|exists:states,id',
            // Contract-related fields
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'contract_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $driver = Driver::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'license_number' => $request->license_number,
            'vehicle_info' => $request->vehicle_info,
            'is_active' => $request->is_active ?? true,
            'commission_rate' => $request->commission_rate ?? 0,
            'commission_type' => $request->commission_type ?? 'percentage',
            'commission_is_active' => true,
            'state_id' => $request->state_id,
            // Contract-related
            'birth_date' => $request->birth_date,
            'birth_place' => $request->birth_place,
            'address' => $request->address,
            'contract_date' => $request->contract_date,
        ]);

        // Removed city syncing: communes are no longer linked to drivers

        return redirect()->route('drivers.index')->with('success', 'Driver created successfully.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Driver $driver)
    {
        $driver->load('state');
        return Inertia::render('Drivers/Edit', [
            'driver' => $driver,
            'states' => State::active()->get(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Driver $driver)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'license_number' => 'nullable|string',
            'vehicle_info' => 'nullable|string',
            'is_active' => 'boolean',
            'commission_rate' => 'nullable|numeric|min:0|max:600',
            'commission_type' => ['nullable', Rule::in([ 'fixed_per_parcel'])],
            'commission_is_active' => 'boolean',
            'state_id' => 'nullable|exists:states,id',
            // Contract-related fields
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'contract_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $driver->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'license_number' => $request->license_number,
            'vehicle_info' => $request->vehicle_info,
            'is_active' => $request->is_active ?? true,
            'commission_rate' => $request->commission_rate ?? 0,
            'commission_type' => $request->commission_type ?? 'percentage',
            'commission_is_active' => $request->commission_is_active ?? false,
            'state_id' => $request->state_id,
            // Contract-related
            'birth_date' => $request->birth_date,
            'birth_place' => $request->birth_place,
            'address' => $request->address,
            'contract_date' => $request->contract_date,
        ]);

        // Removed city syncing: communes are no longer linked to drivers

        return redirect()->route('drivers.index')->with('success', 'Driver updated successfully.');
    }

    /**
     * Generate contract PDF for a driver.
     */
    public function contract(Driver $driver)
    {
        $driver->refresh();

        // Ensure multibyte handling uses UTF-8
        if (function_exists('mb_internal_encoding')) {
            mb_internal_encoding('UTF-8');
        }

        $html = view('exports.drivercontract', [
            'driver_name' => $driver->name,
            'birth_date' => optional($driver->birth_date)->format('Y-m-d'),
            'birth_place' => $driver->birth_place ?? '',
            'address' => $driver->address ?? '',
            'commission'=>$driver->comission_rate,
            'license_number' => $driver->license_number ?? '',
            'contract_date' => optional($driver->contract_date)->format('Y-m-d') ?? date('Y-m-d'),
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        // Limit Dompdf to public directory so local @font-face src URLs resolve
        $options->set('chroot', public_path());
        // Use DejaVu Sans by default to support Arabic and Latin without external fonts
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4');

        // Register Cairo font family if TTF files are present
        $cairoRegular = public_path('fonts/cairo/Cairo-Regular.ttf');
        $cairoBold = public_path('fonts/cairo/Cairo-Bold.ttf');
        
        // Prefer Noto Naskh Arabic as default font when present; CSS @font-face will load it
        $notoRegular = public_path('fonts/noto/NotoNaskhArabic-Regular.ttf');
        $notoBold = public_path('fonts/noto/NotoNaskhArabic-Bold.ttf');
        if (file_exists($notoRegular)) {
            $options->set('defaultFont', 'Noto Naskh Arabic');
        }
        // No manual font registration is needed; fonts are loaded via @font-face in the Blade template

        // Base styles enforce RTL and prefer Noto Naskh Arabic with DejaVu Sans and Cairo fallbacks
        $baseStyles = '<style>@page { margin: 5mm } body, html, p, div, span, h1, h2, h3 { font-family: "Noto Naskh Arabic", "DejaVu Sans", "Cairo", sans-serif; direction: rtl; unicode-bidi: embed; }</style>';
        // Use public_path as base to resolve URLs in CSS (e.g., font files under public/fonts)
        $dompdf->loadHtml($baseStyles . $html);
        $dompdf->render();
        $pdfOutput = $dompdf->output();

        $fileName = 'driver_contract_' . $driver->id . '_' . date('Y-m-d') . '.pdf';

        return response($pdfOutput, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $fileName . '"',
        ]);
    }
    /**
     * Show contract Blade view directly for preview without PDF rendering.
     */
    public function contractPreview(Driver $driver)
    {
        $driver->refresh();
        return view('exports.drivercontract', [
            'driver_name' => $driver->name,
            'birth_date' => optional($driver->birth_date)->format('Y-m-d'),
            'birth_place' => $driver->birth_place ?? '',
            'address' => $driver->address ?? '',
            'commission'=>$driver->commission_rate,
            'license_number' => $driver->license_number ?? '',
            'contract_date' => optional($driver->contract_date)->format('Y-m-d') ?? date('Y-m-d'),
        ]);
    }
}