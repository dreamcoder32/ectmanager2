<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\City;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

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
            // Removed city_ids validation since communes are no longer linked to drivers
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
            // Removed city_ids validation since communes are no longer linked to drivers
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
        ]);

        // Removed city syncing: communes are no longer linked to drivers

        return redirect()->route('drivers.index')->with('success', 'Driver updated successfully.');
    }
}