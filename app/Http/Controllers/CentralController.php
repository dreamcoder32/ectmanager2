<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\TenantPayment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Validation\Rules;

class CentralController extends Controller
{
    public function index()
    {
        $tenants = Tenant::with('domains')->get();
        return Inertia::render('Central/Index', [
            'tenants' => $tenants
        ]);
    }

    public function create()
    {
        return Inertia::render('Central/Create');
    }

    public function store(Request $request, $central_domain)
    {
        $request->validate([
            'id' => 'required|string|max:255|unique:tenants', // Tenant ID (e.g., 'foo')
            'domain' => 'required|string|max:255|unique:domains', // Subdomain (e.g., 'foo.app.com')
            'plan' => 'required|string',
            'billing_status' => 'required|string',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8',
        ]);

        $tenant = Tenant::create([
            'id' => $request->id,
            'plan' => $request->plan,
            'billing_status' => $request->billing_status,
            'billing_expires_at' => now()->addDays(30), // Free Trial - 30 Days
        ]);

        $tenant->domains()->create([
            'domain' => $request->domain
        ]);

        $tenant->run(function () use ($request) {
            $user = \App\Models\User::create([
                'uid' => \Illuminate\Support\Str::uuid()->toString(),
                'first_name' => 'Admin',
                'last_name' => 'User',
                'email' => $request->email,
                'password' => bcrypt($request->password),
                'role' => 'admin',
                'is_active' => true,
            ]);

            $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
            $user->assignRole($role);
        });

        return redirect()->route('central.index', ['central_domain' => $central_domain]);
    }

    public function edit($central_domain, $id)
    {
        $tenant = Tenant::with('domains')->findOrFail($id);
        return Inertia::render('Central/Edit', [
            'tenant' => $tenant
        ]);
    }

    public function update(Request $request, $central_domain, $id)
    {
        $tenant = Tenant::findOrFail($id);
        $request->validate([
            'plan' => 'required|string',
            'billing_status' => 'required|string',
        ]);

        $tenant->update([
            'plan' => $request->plan,
            'billing_status' => $request->billing_status,
        ]);

        return redirect()->route('central.index', ['central_domain' => $central_domain]);
    }

    public function renew(Request $request, $central_domain, $id)
    {
        $tenant = Tenant::findOrFail($id);

        $request->validate([
            'months' => 'required|integer|min:1',
            'amount' => 'required|numeric|min:0',
            'method' => 'required|string',
            'reference' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        TenantPayment::create([
            'tenant_id' => $tenant->id,
            'amount' => $request->amount,
            'method' => $request->method,
            'reference' => $request->reference,
            'notes' => $request->notes,
            'paid_at' => now(),
        ]);

        $newExpiry = $tenant->billing_expires_at
            ? $tenant->billing_expires_at->copy()->addMonths($request->months)
            : now()->addMonths($request->months);

        $tenant->update([
            'billing_expires_at' => $newExpiry,
            'billing_status' => 'active', // Reactivate if it was expired
        ]);

        return redirect()->back();
    }

    public function payments($central_domain, $id)
    {
        $payments = TenantPayment::where('tenant_id', $id)->orderBy('paid_at', 'desc')->get();
        return response()->json($payments);
    }
}
