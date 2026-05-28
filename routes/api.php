<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\SuperadminController;
use App\Http\Controllers\Api\WorkerController;
use Illuminate\Support\Facades\Route;

// Superadmin rute — ne vezane za tenant
Route::middleware(['auth:sanctum', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/tenants', [SuperadminController::class, 'tenants']);
    Route::get('/tenants/{tenant}', [SuperadminController::class, 'showTenant']);
    Route::patch('/tenants/{tenant}/subscription', [SuperadminController::class, 'updateSubscription']);
    Route::patch('/tenants/{tenant}/deploy-status', [SuperadminController::class, 'updateDeployStatus']);
    Route::patch('/tenants/{tenant}/theme', [SuperadminController::class, 'updateTheme']);
});

// Tenant-aware rute
Route::middleware(['resolve.tenant', 'check.subscription'])->group(function () {
    // Public rute — korisnik bez logovanja
    Route::get('/slots/available', [SlotController::class, 'available']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{token}', [BookingController::class, 'show']);
    Route::patch('/bookings/{token}/cancel', [BookingController::class, 'cancel']);
    Route::get('/bookings/{token}/confirm-by-link', [BookingController::class, 'confirmByLink'])
        ->name('bookings.confirm-by-link');

    // Auth rute — klijent
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Protected rute — samo ulogovani klijent
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);

        Route::apiResource('workers', WorkerController::class);
        Route::apiResource('slots', SlotController::class);
        Route::get('/bookings', [BookingController::class, 'index']);
        Route::patch('/bookings/{token}/confirm', [BookingController::class, 'confirm']);
        Route::patch('/bookings/{token}/reject', [BookingController::class, 'reject']);

        Route::patch('/client/settings', [ClientController::class, 'updateSettings']);

        Route::get('/admin/clients', [AdminController::class, 'clients']);
        Route::patch('/admin/clients/{client}/suspend', [AdminController::class, 'suspendClient']);
    });
});
