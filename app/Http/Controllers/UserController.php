<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        
        // Build users query based on user role
        $usersQuery = User::with(['manager', 'subordinates', 'companies'])
            ->select([
                'id', 'uid', 'first_name', 'email', 'role', 'is_active',
                'first_name', 'last_name', 'date_of_birth', 'identity_card_number',
                'national_identification_number', 'started_working_at', 
                'payment_day_of_month', 'monthly_salary', 'manager_id'
            ]);

        // Filter users based on role
        if ($user->role === 'supervisor') {
            // Supervisors can only see users from their companies
            $userCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            $usersQuery->whereHas('companies', function ($query) use ($userCompanyIds) {
                $query->whereIn('companies.id', $userCompanyIds);
            });
        }
        // Admins can see all users (no additional filtering needed)

        $users = $usersQuery->orderBy('first_name')->get();

        // Calculate statistics
        $stats = [
            'total' => $users->count(),
            'supervisors' => $users->where('role', 'supervisor')->count(),
            'agents' => $users->where('role', 'agent')->count(),
            'admins' => $users->where('role', 'admin')->count(),
            'active' => $users->where('is_active', true)->count(),
        ];

        return Inertia::render('Users/Index', [
            'users' => $users,
            'stats' => $stats
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $user = auth()->user();
        
        // Get potential supervisors (users who can manage others)
        $supervisorsQuery = User::where('role', 'supervisor')
            ->orWhere('role', 'admin')
            ->select('id', 'first_name', 'first_name', 'last_name');

        // Filter supervisors based on role
        if ($user->role === 'supervisor') {
            // Supervisors can only see other supervisors from their companies
            $userCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            $supervisorsQuery->whereHas('companies', function ($query) use ($userCompanyIds) {
                $query->whereIn('companies.id', $userCompanyIds);
            });
        }

        $supervisors = $supervisorsQuery->orderBy('first_name')->get();

        // Get companies based on user role
        $companiesQuery = Company::active()->select('id', 'name', 'code');
        
        if ($user->role === 'supervisor') {
            // Supervisors can only create users in their companies
            $userCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            $companiesQuery->whereIn('id', $userCompanyIds);
        }

        $companies = $companiesQuery->orderBy('name')->get();

        return Inertia::render('Users/Create', [
            'supervisors' => $supervisors,
            'companies' => $companies
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        
        $validator = Validator::make($request->all(), [
            'first_name' => 'nullable|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,supervisor,agent',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'identity_card_number' => 'nullable|string|max:255|unique:users',
            'national_identification_number' => 'nullable|string|max:255|unique:users',
            'started_working_at' => 'nullable|date',
            'payment_day_of_month' => 'nullable|integer|min:1|max:31',
            'monthly_salary' => 'nullable|numeric|min:0',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validate company access for supervisors
        if ($user->role === 'supervisor' && $request->filled('company_ids')) {
            $userCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            $requestCompanyIds = $request->company_ids;
            
            // Check if all requested companies are within supervisor's companies
            if (array_diff($requestCompanyIds, $userCompanyIds)) {
                return back()->withErrors(['company_ids' => 'You can only assign users to companies you belong to.'])->withInput();
            }
        }

        $user = User::create([
            'uid' => 'USR' . str_pad(User::count() + 1, 6, '0', STR_PAD_LEFT),
            'first_name' => $request->first_name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'identity_card_number' => $request->identity_card_number,
            'national_identification_number' => $request->national_identification_number,
            'started_working_at' => $request->started_working_at,
            'payment_day_of_month' => $request->payment_day_of_month ?? 1,
            'monthly_salary' => $request->monthly_salary ?? 0,
            'manager_id' => $request->manager_id,
            'is_active' => $request->is_active ?? true
        ]);

        // Attach companies to user
        if ($request->filled('company_ids')) {
            $user->companies()->attach($request->company_ids);
        }

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $currentUser = auth()->user();
        
        // Check if supervisor can view this user
        if ($currentUser->role === 'supervisor') {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $targetUserCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            
            // Check if the target user belongs to any of the supervisor's companies
            if (!array_intersect($userCompanyIds, $targetUserCompanyIds)) {
                abort(403, 'You can only view users from your companies.');
            }
        }

        $user->load('manager', 'subordinates', 'companies');
        
        return Inertia::render('Users/Show', [
            'user' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        $currentUser = auth()->user();
        
        // Check if supervisor can edit this user
        if ($currentUser->role === 'supervisor') {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $targetUserCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            
            // Check if the target user belongs to any of the supervisor's companies
            if (!array_intersect($userCompanyIds, $targetUserCompanyIds)) {
                abort(403, 'You can only edit users from your companies.');
            }
        }

        // Get potential supervisors (excluding the user being edited to prevent circular reference)
        $supervisorsQuery = User::where('role', 'supervisor')
            ->orWhere('role', 'admin')
            ->where('id', '!=', $user->id)
            ->select('id', 'first_name', 'first_name', 'last_name');

        // Filter supervisors based on role
        if ($currentUser->role === 'supervisor') {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $supervisorsQuery->whereHas('companies', function ($query) use ($userCompanyIds) {
                $query->whereIn('companies.id', $userCompanyIds);
            });
        }

        $supervisors = $supervisorsQuery->orderBy('first_name')->get();

        // Get companies based on user role
        $companiesQuery = Company::active()->select('id', 'name', 'code');
        
        if ($currentUser->role === 'supervisor') {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $companiesQuery->whereIn('id', $userCompanyIds);
        }

        $companies = $companiesQuery->orderBy('name')->get();

        // Load user's companies
        $user->load('companies');

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'supervisors' => $supervisors,
            'companies' => $companies
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $currentUser = auth()->user();
        
        // Check if supervisor can update this user
        if ($currentUser->role === 'supervisor') {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $targetUserCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            
            // Check if the target user belongs to any of the supervisor's companies
            if (!array_intersect($userCompanyIds, $targetUserCompanyIds)) {
                abort(403, 'You can only update users from your companies.');
            }
        }

        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role' => 'required|in:admin,supervisor,agent',
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date|before:today',
            'identity_card_number' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'national_identification_number' => ['nullable', 'string', 'max:255', Rule::unique('users')->ignore($user->id)],
            'started_working_at' => 'nullable|date',
            'payment_day_of_month' => 'nullable|integer|min:1|max:31',
            'monthly_salary' => 'nullable|numeric|min:0',
            'manager_id' => 'nullable|exists:users,id',
            'is_active' => 'boolean',
            'company_ids' => 'nullable|array',
            'company_ids.*' => 'exists:companies,id'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // Validate company access for supervisors
        if ($currentUser->role === 'supervisor' && $request->filled('company_ids')) {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $requestCompanyIds = $request->company_ids;
            
            // Check if all requested companies are within supervisor's companies
            if (array_diff($requestCompanyIds, $userCompanyIds)) {
                return back()->withErrors(['company_ids' => 'You can only assign users to companies you belong to.'])->withInput();
            }
        }

        $updateData = [
            'first_name' => $request->first_name,
            'email' => $request->email,
            'role' => $request->role,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'date_of_birth' => $request->date_of_birth,
            'identity_card_number' => $request->identity_card_number,
            'national_identification_number' => $request->national_identification_number,
            'started_working_at' => $request->started_working_at,
            'payment_day_of_month' => $request->payment_day_of_month,
            'monthly_salary' => $request->monthly_salary,
            'manager_id' => $request->manager_id,
            'is_active' => $request->is_active ?? true
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Sync companies
        if ($request->has('company_ids')) {
            $user->companies()->sync($request->company_ids ?? []);
        }

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        $currentUser = auth()->user();
        
        // Check if supervisor can delete this user
        if ($currentUser->role === 'supervisor') {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $targetUserCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            
            // Check if the target user belongs to any of the supervisor's companies
            if (!array_intersect($userCompanyIds, $targetUserCompanyIds)) {
                abort(403, 'You can only delete users from your companies.');
            }
        }

        // Check if user has subordinates
        if ($user->subordinates()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete user who manages other users. Please reassign their subordinates first.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }

    /**
     * Add companies to a user.
     */
    public function addCompanies(Request $request, User $user)
    {
        $currentUser = auth()->user();
        
        // Check if supervisor can modify this user
        if ($currentUser->role === 'supervisor') {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $targetUserCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            
            // Check if the target user belongs to any of the supervisor's companies
            if (!array_intersect($userCompanyIds, $targetUserCompanyIds)) {
                abort(403, 'You can only modify users from your companies.');
            }
        }

        $request->validate([
            'company_ids' => 'required|array',
            'company_ids.*' => 'exists:companies,id'
        ]);

        // Validate company access for supervisors
        if ($currentUser->role === 'supervisor') {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $requestCompanyIds = $request->company_ids;
            
            // Check if all requested companies are within supervisor's companies
            if (array_diff($requestCompanyIds, $userCompanyIds)) {
                return back()->withErrors(['company_ids' => 'You can only assign users to companies you belong to.']);
            }
        }

        $user->companies()->syncWithoutDetaching($request->company_ids);

        return back()->with('success', 'Companies added successfully.');
    }

    /**
     * Remove companies from a user.
     */
    public function removeCompanies(Request $request, User $user)
    {
        $currentUser = auth()->user();
        
        // Check if supervisor can modify this user
        if ($currentUser->role === 'supervisor') {
            $userCompanyIds = $currentUser->companies()->pluck('companies.id')->toArray();
            $targetUserCompanyIds = $user->companies()->pluck('companies.id')->toArray();
            
            // Check if the target user belongs to any of the supervisor's companies
            if (!array_intersect($userCompanyIds, $targetUserCompanyIds)) {
                abort(403, 'You can only modify users from your companies.');
            }
        }

        $request->validate([
            'company_ids' => 'required|array',
            'company_ids.*' => 'exists:companies,id'
        ]);

        $user->companies()->detach($request->company_ids);

        return back()->with('success', 'Companies removed successfully.');
    }

    /**
     * Get user's companies.
     */
    public function getCompanies(User $user)
    {
        $companies = $user->companies()->select('id', 'name', 'code')->get();
        
        return response()->json([
            'companies' => $companies
        ]);
    }
}
