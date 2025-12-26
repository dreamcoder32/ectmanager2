<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WhatsAppWebhookController;

/*
|--------------------------------------------------------------------------
| Web Routes (Central Domain)
|--------------------------------------------------------------------------
|
| Here you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Generate a regex pattern for central domains
$centralDomains = config('tenancy.central_domains');
$centralDomainsPattern = implode('|', array_map(function ($domain) {
    return preg_quote($domain, '/');
}, $centralDomains));

Route::domain('{central_domain}')
    ->where(['central_domain' => $centralDomainsPattern])
    ->group(function () {
        Route::get('/', function () {
            return 'Welcome to the Central Delivery Management System. Please visit your tenant subdomain.';
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

        // Central Admin Dashboard Routes
        Route::middleware(['auth'])->group(function () {
            Route::get('/dashboard', [\App\Http\Controllers\CentralController::class, 'index'])->name('central.index');
            Route::get('/tenants/create', [\App\Http\Controllers\CentralController::class, 'create'])->name('central.create');
            Route::post('/tenants', [\App\Http\Controllers\CentralController::class, 'store'])->name('central.store');
            Route::get('/tenants/{tenant}/edit', [\App\Http\Controllers\CentralController::class, 'edit'])->name('central.edit');
            Route::put('/tenants/{tenant}', [\App\Http\Controllers\CentralController::class, 'update'])->name('central.update');
            Route::post('/tenants/{tenant}/renew', [\App\Http\Controllers\CentralController::class, 'renew'])->name('central.renew');
            Route::get('/tenants/{tenant}/payments', [\App\Http\Controllers\CentralController::class, 'payments'])->name('central.payments');
        });

        // Auth Routes for Central Admin
        Route::middleware('guest')->group(function () {
            Route::get('login', [\App\Http\Controllers\Auth\AuthController::class, 'showLogin'])->name('central.login');
            Route::post('login', [\App\Http\Controllers\Auth\AuthController::class, 'login']);
        });

        Route::post('logout', [\App\Http\Controllers\Auth\AuthController::class, 'logout'])->name('central.logout');
    });

