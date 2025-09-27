<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\SalaryPaymentController;
use App\Http\Controllers\CommissionPaymentController;
use App\Http\Controllers\FinancialDashboardController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\ProfileController;
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
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard/Index');
    })->name('dashboard');
    
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
    
    // Stopdesk Payment Routes
    Route::get('/stopdesk-payment', function () {
        return Inertia::render('StopDeskPayment/Index');
    })->name('stopdesk.payment');
    Route::post('parcels/search-by-tracking', [ParcelController::class, 'searchByTrackingNumber']);
    Route::post('parcels/confirm-payment', [ParcelController::class, 'confirmPayment']);
    
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
