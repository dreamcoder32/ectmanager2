<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SalaryPaymentController;
use App\Http\Controllers\CommissionPaymentController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\FinancialDashboardController;
use App\Http\Controllers\MoneyCaseController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecolteController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    
    // Redirect unauthenticated users to login
    Route::get('/', function () {
        return redirect()->route('login');
    });
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
    Route::get('/user', [AuthController::class, 'user']);
});

// Dashboard routes (protected)
Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');
    
    // Financial Dashboard
    Route::get('/financial/dashboard', function () {
        return Inertia::render('Financial/Dashboard');
    })->name('financial.dashboard');
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Parcel Management Routes
    Route::resource('parcels', ParcelController::class);
    Route::post('parcels/import-excel', [ParcelController::class, 'importExcel'])->name('parcels.import-excel');
    
    // Money Case Management Routes (Admin and Supervisor only)
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::resource('money-cases', MoneyCaseController::class);
        Route::get('money-cases/{moneyCase}/update-balance', [MoneyCaseController::class, 'updateBalance'])->name('money-cases.update-balance');
    });
    
    // API route for getting active cases (for dropdowns)
    Route::get('api/money-cases/active', [MoneyCaseController::class, 'getActiveCases'])->name('api.money-cases.active');
    
    // Money case activation route for stopdesk
    Route::post('money-cases/activate', [MoneyCaseController::class, 'activateForUser'])->name('money-cases.activate');
    
    // Expense Management Routes - All authenticated users can access
    Route::resource('expenses', ExpenseController::class);
    
    // Expense approval routes - Only supervisors and admins
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::post('expenses/{expense}/approve', [ExpenseController::class, 'approve'])->name('expenses.approve');
        Route::post('expenses/{expense}/mark-as-paid', [ExpenseController::class, 'markAsPaid'])->name('expenses.mark-as-paid');
        Route::post('expenses/{expense}/reject', [ExpenseController::class, 'reject'])->name('expenses.reject');
    });
    
    // Expense Category Management Routes - Admin only
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('expense-categories', ExpenseCategoryController::class);
        Route::post('expense-categories/{expenseCategory}/toggle-status', [ExpenseCategoryController::class, 'toggleStatus'])->name('expense-categories.toggle-status');
    });
    
    // Recolte Management Routes (Admin and Supervisor only)
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::resource('recoltes', RecolteController::class);
    });
    
    // User Management Routes (Supervisor and Admin only)
    Route::middleware(['role:admin,supervisor'])->group(function () {
        Route::resource('users', UserController::class);
    });
    
    // Stopdesk Payment Routes
    Route::get('/stopdesk-payment', function () {
        // Get recent collections for the logged-in user, excluding those that have been recolted
        $recentCollections = \App\Models\Collection::with(['parcel'])
            ->where('created_by', auth()->id())
            ->whereDoesntHave('recoltes') // Exclude collections that are part of any recolte
            ->orderBy('collected_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($collection) {
                return [
                    'id' => $collection->id,
                    'tracking_number' => $collection->parcel->tracking_number ?? 'N/A',
                    'cod_amount' => $collection->parcel->cod_amount ?? 0,
                    'collected_at' => $collection->collected_at,
                    'changeAmount' => $collection->amount - ($collection->parcel->cod_amount ?? 0),
                ];
            });

        // Debug: Log the query for troubleshooting
        \Log::info('Stopdesk collections query', [
            'user_id' => auth()->id(),
            'total_collections' => \App\Models\Collection::where('created_by', auth()->id())->count(),
            'collections_with_recoltes' => \App\Models\Collection::where('created_by', auth()->id())->whereHas('recoltes')->count(),
            'collections_without_recoltes' => \App\Models\Collection::where('created_by', auth()->id())->whereDoesntHave('recoltes')->count(),
            'filtered_count' => $recentCollections->count()
        ]);

        // Get active money cases for case selection - show free cases and cases used by current user
        $currentUserId = auth()->id();
        $activeCases = \App\Models\MoneyCase::where('status', 'active')
            ->where(function ($query) use ($currentUserId) {
                $query->whereNull('last_active_by') // Free cases
                      ->orWhere('last_active_by', $currentUserId); // Cases used by current user
            })
            ->orderBy('name')
            ->get()
            ->map(function ($case) use ($currentUserId) {
                return [
                    'id' => $case->id,
                    'name' => $case->name,
                    'description' => $case->description,
                    'balance' => $case->calculated_balance,
                    'currency' => $case->currency,
                    'is_user_active' => $case->last_active_by === $currentUserId,
                ];
            });

        // Find the user's last active case
        $userLastActiveCase = \App\Models\MoneyCase::where('status', 'active')
            ->where('last_active_by', $currentUserId)
            ->orderBy('last_activated_at', 'desc')
            ->first();

        // Get flash data if available
        $flashData = [];
        if (session()->has('searchResult')) {
            $flashData['searchResult'] = session('searchResult');
        }
        if (session()->has('paymentResult')) {
            $flashData['paymentResult'] = session('paymentResult');
        }

        return Inertia::render('StopDeskPayment/Index', array_merge([
            'recentCollections' => $recentCollections,
            'activeCases' => $activeCases,
            'userLastActiveCaseId' => $userLastActiveCase ? $userLastActiveCase->id : null,
            'auth' => [
                'user' => [
                    'id' => auth()->user()->id,
                    'can_collect_stopdesk' => auth()->user()->can_collect_stopdesk ?? false
                ]
            ]
        ], $flashData));
    })->name('stopdesk.payment');
    Route::post('parcels/search-by-tracking', [ParcelController::class, 'searchByTrackingNumber']);
    Route::post('parcels/confirm-payment', [ParcelController::class, 'confirmPayment']);
    Route::post('parcels/create-manual-and-collect', [ParcelController::class, 'createManualParcelAndCollect']);
    
    // Redirect root to dashboard for authenticated users
    Route::get('/', function () {
        return redirect()->route('dashboard');
    });
    
    // API Routes for Financial Management
    Route::prefix('api')->group(function () {
        // Financial Dashboard Routes
        Route::get('financial-dashboard', [FinancialDashboardController::class, 'index']);
        Route::get('financial-summary', [FinancialDashboardController::class, 'summary']);
        
        // Salary Payment Routes
        Route::apiResource('salary-payments', SalaryPaymentController::class);
        Route::post('salary-payments/{salaryPayment}/mark-as-paid', [SalaryPaymentController::class, 'markAsPaid']);
        Route::post('salary-payments/generate-monthly', [SalaryPaymentController::class, 'generateMonthlyPayments']);
        Route::get('salary-payments-statistics', [SalaryPaymentController::class, 'statistics']);
        
        // Commission Payment Routes
        Route::apiResource('commission-payments', CommissionPaymentController::class);
        Route::post('commission-payments/{commissionPayment}/mark-as-paid', [CommissionPaymentController::class, 'markAsPaid']);
        Route::post('commission-payments/calculate', [CommissionPaymentController::class, 'calculateCommissions']);
        Route::post('commission-payments/generate', [CommissionPaymentController::class, 'generateCommissionPayments']);
        Route::get('commission-payments-statistics', [CommissionPaymentController::class, 'statistics']);
        
        // State and City API Routes
        Route::get('states', [ParcelController::class, 'getStates']);
        Route::get('cities', [ParcelController::class, 'getCities']);
        Route::get('states/{state}/cities', [ParcelController::class, 'getCitiesByState']);
    });
});
