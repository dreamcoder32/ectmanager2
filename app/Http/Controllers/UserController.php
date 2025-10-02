<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $users = User::with('manager', 'subordinates')
            ->select([
                'id', 'uid', 'display_name', 'email', 'role', 'is_active',
                'first_name', 'last_name', 'date_of_birth', 'identity_card_number',
                'national_identification_number', 'started_working_at', 
                'payment_day_of_month', 'monthly_salary', 'manager_id'
            ])
            ->orderBy('display_name')
            ->get();

        return Inertia::render('Users/Index', [
            'users' => $users
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Get potential supervisors (users who can manage others)
        $supervisors = User::where('role', 'supervisor')
            ->orWhere('role', 'admin')
            ->select('id', 'display_name', 'first_name', 'last_name')
            ->orderBy('display_name')
            ->get();

        return Inertia::render('Users/Create', [
            'supervisors' => $supervisors
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'required|string|max:255',
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
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'uid' => 'USR' . str_pad(User::count() + 1, 6, '0', STR_PAD_LEFT),
            'display_name' => $request->display_name,
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

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load('manager', 'subordinates');
        
        return Inertia::render('Users/Show', [
            'user' => $user
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Get potential supervisors (excluding the user being edited to prevent circular reference)
        $supervisors = User::where('role', 'supervisor')
            ->orWhere('role', 'admin')
            ->where('id', '!=', $user->id)
            ->select('id', 'display_name', 'first_name', 'last_name')
            ->orderBy('display_name')
            ->get();

        return Inertia::render('Users/Edit', [
            'user' => $user,
            'supervisors' => $supervisors
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'display_name' => 'required|string|max:255',
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
            'is_active' => 'boolean'
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $updateData = [
            'display_name' => $request->display_name,
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

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Check if user has subordinates
        if ($user->subordinates()->exists()) {
            return back()->withErrors(['error' => 'Cannot delete user who manages other users. Please reassign their subordinates first.']);
        }

        $user->delete();

        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
