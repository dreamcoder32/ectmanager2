<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\AuthController as ApiAuthController;
use App\Http\Controllers\API\ParcelController as ApiParcelController;

// Public API route to obtain token
Route::post('/login', [ApiAuthController::class, 'login']);

// Protected routes using Sanctum token auth
Route::middleware('auth:sanctum')->group(function () {
    // Token revoke/logout
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    // Parcels endpoints
    Route::get('/parcels', [ApiParcelController::class, 'index']);
    Route::get('/parcels/{parcel}', [ApiParcelController::class, 'show']);
});
