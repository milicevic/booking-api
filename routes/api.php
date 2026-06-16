<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PushController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\SuperadminController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\WorkerController;
use Illuminate\Support\Facades\Route;

// Platform-level rute — bez tenant konteksta
Route::post('/register', [RegisterController::class, 'store']);
Route::post('/auth/login', [AuthController::class, 'login']);
Route::get('/push/vapid-public-key', [PushController::class, 'vapidPublicKey']);
Route::get('/bookings/{token}/confirm-by-link', [BookingController::class, 'confirmByLink'])
    ->name('bookings.confirm-by-link');

// Admin rute — platform-level, ne vezane za tenant
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/clients', [AdminController::class, 'clients']);
    Route::patch('/clients/{client}/suspend', [AdminController::class, 'suspendClient']);
});

// Superadmin rute — ne vezane za tenant
Route::middleware(['auth:sanctum', 'superadmin'])->prefix('superadmin')->group(function () {
    Route::get('/tenants', [SuperadminController::class, 'tenants']);
    Route::get('/tenants/{tenant}', [SuperadminController::class, 'showTenant']);
    Route::patch('/tenants/{tenant}/subscription', [SuperadminController::class, 'updateSubscription']);
    Route::patch('/tenants/{tenant}/deploy-status', [SuperadminController::class, 'updateDeployStatus']);
    Route::patch('/tenants/{tenant}/theme', [SuperadminController::class, 'updateTheme']);
});

// Tenant config — tenant opcionalan (admin nema tenant)
Route::middleware('resolve.tenant:optional')->get('/tenant/config', [TenantController::class, 'config']);

// Javne tenant rute — tenant obavezan
Route::middleware(['resolve.tenant', 'check.subscription'])->group(function () {
    Route::get('/slots/available', [SlotController::class, 'available']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::get('/bookings/{token}', [BookingController::class, 'show']);
    Route::patch('/bookings/{token}/cancel', [BookingController::class, 'cancel']);
    Route::post('/auth/accept-invite', [AuthController::class, 'acceptInvite']);
});

// Zaštićene rute — tenant opcionalan (admin vidi sve, klijent/worker vidi samo svoje)
Route::middleware(['resolve.tenant:optional', 'check.subscription', 'auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::apiResource('workers', WorkerController::class);
    Route::apiResource('slots', SlotController::class);
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::patch('/bookings/{token}/confirm', [BookingController::class, 'confirm']);
    Route::patch('/bookings/{token}/reject', [BookingController::class, 'reject']);

    Route::patch('/client/settings', [ClientController::class, 'updateSettings']);
    Route::post('/client/deploy-request', [ClientController::class, 'deployRequest']);

    Route::get('/services', [ServiceController::class, 'index']);
    Route::post('/services', [ServiceController::class, 'store']);
    Route::patch('/services/{service}', [ServiceController::class, 'update']);
    Route::delete('/services/{service}', [ServiceController::class, 'destroy']);
    Route::post('/services/{service}/workers/{worker}', [ServiceController::class, 'assignWorker']);
    Route::delete('/services/{service}/workers/{worker}', [ServiceController::class, 'unassignWorker']);

    Route::post('/push/subscriptions', [PushController::class, 'subscribe']);
    Route::delete('/push/subscriptions', [PushController::class, 'unsubscribe']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markRead']);
});
