<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ParcelController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
// Import additional controllers
use App\Http\Controllers\UserController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ExpenseCategoryController;
use App\Http\Controllers\RecolteController;
use App\Http\Controllers\SalaryPaymentController;
use App\Http\Controllers\CommissionPaymentController;
use App\Http\Controllers\MoneyCaseController;
use App\Http\Controllers\FinancialDashboardController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\WhatsAppController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\WhatsAppWebhookController;
use Inertia\Inertia;

Route::get("/", function () {
    if (Auth::check()) {
        return redirect()->route("dashboard");
    }
    return redirect()->route("login");
});

Route::get("/phpinfo", function () {
    return response()->json([
        "sockets_enabled" => extension_loaded("sockets"),
        "available_extensions" => get_loaded_extensions(),
    ]);
});

Route::get("/dashboard", [DashboardController::class, "index"])
    ->middleware(["auth", "verified"])
    ->name("dashboard");

// Custom Authentication Routes
Route::middleware("guest")->group(function () {
    Route::get("/login", [AuthController::class, "showLogin"])->name("login");
    Route::post("/login", [AuthController::class, "login"]);
    // Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    // Route::post('/register', [AuthController::class, 'register']);
});

Route::middleware("auth")->group(function () {
    Route::post("/logout", [AuthController::class, "logout"])->name("logout");
    Route::get("/user", [AuthController::class, "user"])->name("user");
    Route::get("/csrf-token", function () {
        return response()->json(["csrf_token" => csrf_token()]);
    })->name("csrf-token");
});

Route::middleware("auth")->group(function () {
    // Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Parcel routes
Route::middleware(["auth"])->group(function () {
    Route::get("/parcels", [ParcelController::class, "index"])->name(
        "parcels.index",
    );
    Route::get("/parcels/create", [ParcelController::class, "create"])->name(
        "parcels.create",
    );
    Route::post("/parcels", [ParcelController::class, "store"])->name(
        "parcels.store",
    );
    Route::get("/parcels/{parcel}", [ParcelController::class, "show"])->name(
        "parcels.show",
    );
    Route::get("/parcels/{parcel}/edit", [
        ParcelController::class,
        "edit",
    ])->name("parcels.edit");
    Route::put("/parcels/{parcel}", [ParcelController::class, "update"])->name(
        "parcels.update",
    );
    // Route::delete('/parcels/{parcel}', [ParcelController::class, 'destroy'])->name('parcels.destroy');

    // Bulk import
    Route::get("/parcels/import/form", [
        ParcelController::class,
        "importForm",
    ])->name("parcels.import.form");
    Route::post("/parcels/import", [ParcelController::class, "import"])->name(
        "parcels.import",
    );
    Route::post("/parcels/import-excel", [
        ParcelController::class,
        "importExcel",
    ])->name("parcels.import-excel");

    // Search by tracking number
    Route::post("/parcels/search-by-tracking", [
        ParcelController::class,
        "searchByTrackingNumber",
    ])->name("parcels.search-by-tracking");
});

// Add API endpoints for states and cities used by selectors
Route::middleware(["auth"])->group(function () {
    Route::get("/api/states", [ParcelController::class, "getStates"])->name(
        "api.states",
    );
    Route::get("/api/states/{state}/cities", [
        ParcelController::class,
        "getCitiesByState",
    ])->name("api.states.cities");
});

// Driver routes
Route::middleware(["auth"])->group(function () {
    Route::get("/drivers", [DriverController::class, "index"])->name(
        "drivers.index",
    );
    Route::get("/drivers/create", [DriverController::class, "create"])->name(
        "drivers.create",
    );
    Route::post("/drivers", [DriverController::class, "store"])->name(
        "drivers.store",
    );
    Route::get("/drivers/{driver}/edit", [
        DriverController::class,
        "edit",
    ])->name("drivers.edit");
    Route::put("/drivers/{driver}", [DriverController::class, "update"])->name(
        "drivers.update",
    );
    // Contract PDF route
    Route::get("/drivers/{driver}/contract", [
        DriverController::class,
        "contract",
    ])->name("drivers.contract");
    // Contract HTML preview route
    Route::get("/drivers/{driver}/contract/preview", [
        DriverController::class,
        "contractPreview",
    ])->name("drivers.contract.preview");
});

// Stopdesk Payment routes
Route::middleware(["auth"])->group(function () {
    Route::post("money-cases/activate", [
        MoneyCaseController::class,
        "activateForUser",
    ])->name("money-cases.activate");
    Route::post("parcels/confirm-payment", [
        ParcelController::class,
        "confirmPayment",
    ]);
    Route::post("parcels/create-manual-and-collect", [
        ParcelController::class,
        "createManualParcelAndCollect",
    ]);

    Route::match(["get", "post"], "/stopdesk-payment", [
        ParcelController::class,
        "stopDeskPayment",
    ])->name("stopdesk-payment.index");
    Route::post("/stopdesk-payment/search", [
        ParcelController::class,
        "searchForStopDesk",
    ])->name("stopdesk-payment.search");
    Route::post("/stopdesk-payment/collect", [
        ParcelController::class,
        "collectStopDesk",
    ])->name("stopdesk-payment.collect");
});

// Collection Transfer (Recoltes) routes
Route::middleware(["auth"])->group(function () {
    Route::get("/recoltes", [RecolteController::class, "index"])->name(
        "recoltes.index",
    );
    Route::get("/recoltes/create", [RecolteController::class, "create"])->name(
        "recoltes.create",
    );
    Route::post("/recoltes", [RecolteController::class, "store"])->name(
        "recoltes.store",
    );
    Route::get("/recoltes/{recolte}", [RecolteController::class, "show"])->name(
        "recoltes.show",
    );
    Route::get("/recoltes/{recolte}/export", [
        RecolteController::class,
        "export",
    ])->name("recoltes.export");
    // Route::get('/recoltes/{recolte}/edit', [RecolteController::class, 'edit'])->name('recoltes.edit');
    // Route::put('/recoltes/{recolte}', [RecolteController::class, 'update'])->name('recoltes.update');
    // Route::delete('/recoltes/{recolte}', [RecolteController::class, 'destroy'])->name('recoltes.destroy']);

    // Driver Settlement (PDF -> Collections -> Recolte)
    Route::get("/driver-settlement", [
        \App\Http\Controllers\DriverSettlementController::class,
        "index",
    ])->name("driver-settlement.index");
    Route::post("/driver-settlement/parse", [
        \App\Http\Controllers\DriverSettlementController::class,
        "parse",
    ])->name("driver-settlement.parse");
    Route::post("/driver-settlement/process", [
        \App\Http\Controllers\DriverSettlementController::class,
        "process",
    ])->name("driver-settlement.process");
});

// Expense routes
Route::middleware(["auth"])->group(function () {
    Route::get("/expenses", [ExpenseController::class, "index"])->name(
        "expenses.index",
    );
    Route::get("/expenses/create", [ExpenseController::class, "create"])->name(
        "expenses.create",
    );
    Route::post("/expenses", [ExpenseController::class, "store"])->name(
        "expenses.store",
    );
    Route::get("/expenses/{expense}", [ExpenseController::class, "show"])->name(
        "expenses.show",
    );
    // Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put("/expenses/{expense}", [
        ExpenseController::class,
        "update",
    ])->name("expenses.update");
    // Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::post("/expenses/{expense}/approve", [
        ExpenseController::class,
        "approve",
    ])->name("expenses.approve");
    Route::post("/expenses/{expense}/pay", [
        ExpenseController::class,
        "pay",
    ])->name("expenses.pay");
});

// Expense Category routes
Route::middleware(["auth"])->group(function () {
    Route::get("/expense-categories", [
        ExpenseCategoryController::class,
        "index",
    ])->name("expense-categories.index");
    Route::get("/expense-categories/create", [
        ExpenseCategoryController::class,
        "create",
    ])->name("expense-categories.create");
    Route::post("/expense-categories", [
        ExpenseCategoryController::class,
        "store",
    ])->name("expense-categories.store");
    Route::get("/expense-categories/{expenseCategory}", [
        ExpenseCategoryController::class,
        "show",
    ])->name("expense-categories.show");
    Route::get("/expense-categories/{expenseCategory}/edit", [
        ExpenseCategoryController::class,
        "edit",
    ])->name("expense-categories.edit");
    Route::put("/expense-categories/{expenseCategory}", [
        ExpenseCategoryController::class,
        "update",
    ])->name("expense-categories.update");
    // Route::delete('/expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy'])->name('expense-categories.destroy');
});

// User Management routes
Route::middleware(["auth"])->group(function () {
    Route::get("/users", [UserController::class, "index"])->name("users.index");
    Route::get("/users/create", [UserController::class, "create"])->name(
        "users.create",
    );
    Route::post("/users", [UserController::class, "store"])->name(
        "users.store",
    );
    Route::get("/users/{user}", [UserController::class, "show"])->name(
        "users.show",
    );
    Route::get("/users/{user}/edit", [UserController::class, "edit"])->name(
        "users.edit",
    );
    Route::put("/users/{user}", [UserController::class, "update"])->name(
        "users.update",
    );
    // Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

    // Company management routes
    Route::post("/users/{user}/companies", [
        UserController::class,
        "addCompanies",
    ])->name("users.companies.add");
    Route::delete("/users/{user}/companies", [
        UserController::class,
        "removeCompanies",
    ])->name("users.companies.remove");
    Route::get("/users/{user}/companies", [
        UserController::class,
        "getCompanies",
    ])->name("users.companies.get");
});

// Salary Payment routes
Route::middleware(["auth"])->group(function () {
    Route::get("/salary-payments", [
        SalaryPaymentController::class,
        "index",
    ])->name("salary-payments.index");
    Route::get("/salary-payments/create", [
        SalaryPaymentController::class,
        "create",
    ])->name("salary-payments.create");
    Route::post("/salary-payments", [
        SalaryPaymentController::class,
        "store",
    ])->name("salary-payments.store");
    Route::get("/salary-payments/{salaryPayment}", [
        SalaryPaymentController::class,
        "show",
    ])->name("salary-payments.show");
    Route::get("/salary-payments/{salaryPayment}/edit", [
        SalaryPaymentController::class,
        "edit",
    ])->name("salary-payments.edit");
    Route::put("/salary-payments/{salaryPayment}", [
        SalaryPaymentController::class,
        "update",
    ])->name("salary-payments.update");
    // Route::delete('/salary-payments/{salaryPayment}', [SalaryPaymentController::class, 'destroy'])->name('salary-payments.destroy');
    Route::post("/salary-payments/generate-monthly", [
        SalaryPaymentController::class,
        "generateMonthlyPayments",
    ])->name("salary-payments.generate-monthly");
    Route::get("/salary-payments/statistics", [
        SalaryPaymentController::class,
        "statistics",
    ])->name("salary-payments.statistics");
});

// Commission Payment routes
Route::middleware(["auth"])->group(function () {
    Route::get("/commission-payments", [
        CommissionPaymentController::class,
        "index",
    ])->name("commission-payments.index");
    Route::get("/commission-payments/create", [
        CommissionPaymentController::class,
        "create",
    ])->name("commission-payments.create");
    Route::post("/commission-payments", [
        CommissionPaymentController::class,
        "store",
    ])->name("commission-payments.store");
    Route::get("/commission-payments/{commissionPayment}", [
        CommissionPaymentController::class,
        "show",
    ])->name("commission-payments.show");
    Route::get("/commission-payments/{commissionPayment}/edit", [
        CommissionPaymentController::class,
        "edit",
    ])->name("commission-payments.edit");
    Route::put("/commission-payments/{commissionPayment}", [
        CommissionPaymentController::class,
        "update",
    ])->name("commission-payments.update");
    // Route::delete('/commission-payments/{commissionPayment}', [CommissionPaymentController::class, 'destroy'])->name('commission-payments.destroy');
    Route::post("/commission-payments/generate-monthly", [
        CommissionPaymentController::class,
        "generateMonthlyPayments",
    ])->name("commission-payments.generate-monthly");
    Route::get("/commission-payments/statistics", [
        CommissionPaymentController::class,
        "statistics",
    ])->name("commission-payments.statistics");
});

// Money Case routes
Route::middleware(["auth"])->group(function () {
    Route::get("/money-cases", [MoneyCaseController::class, "index"])->name(
        "money-cases.index",
    );
    Route::get("/money-cases/create", [
        MoneyCaseController::class,
        "create",
    ])->name("money-cases.create");
    Route::post("/money-cases", [MoneyCaseController::class, "store"])->name(
        "money-cases.store",
    );
    Route::get("/money-cases/{moneyCase}", [
        MoneyCaseController::class,
        "show",
    ])->name("money-cases.show");
    Route::get("/money-cases/{moneyCase}/edit", [
        MoneyCaseController::class,
        "edit",
    ])->name("money-cases.edit");
    Route::put("/money-cases/{moneyCase}", [
        MoneyCaseController::class,
        "update",
    ])->name("money-cases.update");
    // Route::delete('/money-cases/{moneyCase}', [MoneyCaseController::class, 'destroy'])->name('money-cases.destroy');
});

// Financial Dashboard routes
Route::middleware(["auth"])->group(function () {
    Route::get("/financial-dashboard", [
        FinancialDashboardController::class,
        "index",
    ])->name("financial-dashboard.index");
    Route::get("/financial-dashboard/reports", [
        FinancialDashboardController::class,
        "reports",
    ])->name("financial-dashboard.reports");
});

// Company routes
Route::middleware(["auth"])->group(function () {
    Route::get("/companies", [CompanyController::class, "index"])->name(
        "companies.index",
    );
    Route::get("/companies/create", [CompanyController::class, "create"])->name(
        "companies.create",
    );
    Route::post("/companies", [CompanyController::class, "store"])->name(
        "companies.store",
    );
    Route::get("/companies/{company}", [
        CompanyController::class,
        "show",
    ])->name("companies.show");
    Route::get("/companies/{company}/edit", [
        CompanyController::class,
        "edit",
    ])->name("companies.edit");
    Route::put("/companies/{company}", [
        CompanyController::class,
        "update",
    ])->name("companies.update");

    // WhatsApp API endpoints
    Route::post("/companies/{company}/test-whatsapp-connection", [
        CompanyController::class,
        "testWhatsAppConnection",
    ])->name("companies.test-whatsapp-connection");
    Route::put("/companies/{company}/whatsapp-api-key", [
        CompanyController::class,
        "updateWhatsAppApiKey",
    ])->name("companies.whatsapp-api-key");
    Route::get("/companies/whatsapp-status", [
        CompanyController::class,
        "getWhatsAppStatus",
    ])->name("companies.whatsapp-status");
});

// WhatsApp routes
Route::middleware(["auth"])->group(function () {
    Route::get("/whatsapp", [WhatsAppController::class, "index"])->name(
        "whatsapp.index",
    );
    Route::get("/whatsapp/parcels/{parcel}/messages", [
        WhatsAppController::class,
        "showParcelMessages",
    ])->name("whatsapp.parcel-messages");

    // API endpoints
    Route::post("/whatsapp/parcels/{parcel}/send-message", [
        WhatsAppController::class,
        "sendMessage",
    ])->name("whatsapp.send-message");
    Route::post("/whatsapp/send-bulk-messages", [
        WhatsAppController::class,
        "sendBulkMessages",
    ])->name("whatsapp.send-bulk-messages");
    Route::post("/whatsapp/parcels/{parcel}/desk-pickup-notification", [
        WhatsAppController::class,
        "sendDeskPickupNotification",
    ])->name("whatsapp.desk-pickup-notification");
    Route::post("/whatsapp/parcels/{parcel}/delivery-notification", [
        WhatsAppController::class,
        "sendDeliveryNotification",
    ])->name("whatsapp.delivery-notification");
    Route::post("/whatsapp/parcels/{parcel}/collection-notification", [
        WhatsAppController::class,
        "sendCollectionNotification",
    ])->name("whatsapp.collection-notification");
    Route::get("/whatsapp/parcels/{parcel}/message-history", [
        WhatsAppController::class,
        "getMessageHistory",
    ])->name("whatsapp.message-history");
    Route::post("/whatsapp/parcels/{parcel}/toggle-tag", [
        WhatsAppController::class,
        "toggleWhatsAppTag",
    ])->name("whatsapp.toggle-tag");
    Route::get("/whatsapp/companies/status", [
        WhatsAppController::class,
        "getCompaniesStatus",
    ])->name("whatsapp.companies-status");
    Route::post("/whatsapp/companies/{company}/test-connection", [
        WhatsAppController::class,
        "testConnection",
    ])->name("whatsapp.test-connection");
    Route::get("/whatsapp/companies/{company}/session-status", [
        WhatsAppController::class,
        "getCompanySessionStatus",
    ])->name("whatsapp.session-status");
    Route::get("/whatsapp/companies/{company}/qrcode", [
        WhatsAppController::class,
        "getSessionQrCode",
    ])->name("whatsapp.companies.qrcode");

    // Phone verification endpoints
    Route::post("/whatsapp/parcels/{parcel}/verify-phones", [
        WhatsAppController::class,
        "verifyParcelPhoneNumbers",
    ])->name("whatsapp.verify-phones");
    Route::post("/whatsapp/bulk-verify-phones", [
        WhatsAppController::class,
        "bulkVerifyPhoneNumbers",
    ])->name("whatsapp.bulk-verify-phones");

    // Price change endpoints
    Route::post("/whatsapp/parcels/{parcel}/update-price", [
        WhatsAppController::class,
        "updateParcelPrice",
    ])->name("whatsapp.update-price");
    Route::get("/whatsapp/parcels/{parcel}/price-history", [
        WhatsAppController::class,
        "getPriceChangeHistory",
    ])->name("whatsapp.price-history");
    Route::post("/whatsapp/companies/{company}/check-phone", [
        WhatsAppController::class,
        "checkPhoneOnWhatsApp",
    ])->name("whatsapp.check-phone");
});

// WhatsApp Webhook routes (public access for wasenderapi.com)
Route::post("/webhook/whatsapp/incoming", [
    WhatsAppWebhookController::class,
    "handleIncomingMessage",
])->name("webhook.whatsapp.incoming");
Route::get("/webhook/whatsapp/verify", [
    WhatsAppWebhookController::class,
    "verifyWebhook",
])->name("webhook.whatsapp.verify");
Route::get("/webhook/whatsapp/status", [
    WhatsAppWebhookController::class,
    "getWebhookStatus",
])->name("webhook.whatsapp.status");

require __DIR__ . "/auth.php";
