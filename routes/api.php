<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AdminTenantController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\EmailVerificationController;
use App\Http\Controllers\Api\GoogleAuthController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\PasswordResetController;
use App\Http\Controllers\Api\PushController;
use App\Http\Controllers\Api\RegisterController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\WorkerController;
use App\Http\Controllers\Api\WorkerScheduleController;
use Illuminate\Support\Facades\Route;

// Platform-level rute — bez tenant konteksta
Route::post('/register', [RegisterController::class, 'store'])->middleware('throttle:register');
Route::get('/register/subdomain-preview', [RegisterController::class, 'subdomainPreview'])->middleware('throttle:60,1');
Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');
Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->middleware('throttle:google');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback']);
Route::get('/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->whereNumber('id')
    ->name('verification.verify');
Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend'])->middleware('throttle:6,1');
Route::post('/auth/password/forgot', [PasswordResetController::class, 'forgot'])->middleware('throttle:6,1');
Route::post('/auth/password/reset', [PasswordResetController::class, 'reset'])->middleware('throttle:10,1');
Route::get('/push/vapid-public-key', [PushController::class, 'vapidPublicKey']);
Route::get('/tenant/manifest', [TenantController::class, 'manifest'])->middleware('throttle:60,1');
Route::get('/bookings/{token}/confirm-by-link', [BookingController::class, 'confirmByLink'])
    ->name('bookings.confirm-by-link');
Route::post('/bookings/{token}/push-subscription', [BookingController::class, 'storePushSubscription']);
// Poziv u kalendar (.ics); link se otvara iz mejla ili sa iPhone-a, bez tenant zaglavlja
Route::get('/bookings/{token}/calendar.ics', [BookingController::class, 'calendar'])->name('bookings.calendar');

// Admin rute — platform-level, ne vezane za tenant
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    Route::get('/clients', [AdminController::class, 'clients']);
    Route::patch('/clients/{client}/suspend', [AdminController::class, 'suspendClient']);
});

// Admin: upravljanje firmama i pretplatom — ne vezano za tenant
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin/tenants')->group(function () {
    Route::get('/', [AdminTenantController::class, 'tenants']);
    Route::post('/', [AdminTenantController::class, 'storeTenant']);
    Route::get('/{tenant}', [AdminTenantController::class, 'showTenant']);
    Route::delete('/{tenant}', [AdminTenantController::class, 'destroyTenant']);
    Route::patch('/{tenant}/subscription', [AdminTenantController::class, 'updateSubscription']);
    Route::patch('/{tenant}/deploy-status', [AdminTenantController::class, 'updateDeployStatus']);
    Route::patch('/{tenant}/theme', [AdminTenantController::class, 'updateTheme']);
});

// Tenant config — tenant opcionalan (admin nema tenant)
Route::middleware('resolve.tenant:optional')->get('/tenant/config', [TenantController::class, 'config']);

// Javne tenant rute — tenant obavezan
Route::middleware(['resolve.tenant', 'check.subscription'])->group(function () {
    Route::get('/slots/available', [SlotController::class, 'available']);
    Route::get('/slots/times', [SlotController::class, 'times']);
    Route::post('/bookings', [BookingController::class, 'store'])->middleware('throttle:bookings');
    Route::get('/bookings/{token}', [BookingController::class, 'show']);
    Route::patch('/bookings/{token}/cancel', [BookingController::class, 'cancel']);
    // Kupac sam pomera rezervaciju; /bookings/{token}/reschedule je za vlasnika i radnika (auth)
    Route::patch('/bookings/{token}/customer-reschedule', [BookingController::class, 'customerReschedule'])->middleware('throttle:bookings');
    Route::post('/auth/accept-invite', [AuthController::class, 'acceptInvite']);
});

// Zaštićene rute — tenant opcionalan (admin vidi sve, klijent/worker vidi samo svoje)
Route::middleware(['resolve.tenant:optional', 'check.subscription', 'auth:sanctum'])->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::get('/auth/me', [AuthController::class, 'me']);

    Route::apiResource('workers', WorkerController::class);
    Route::patch('/workers/{worker}/profile', [WorkerController::class, 'updateProfile']);
    Route::post('/workers/{worker}/photo', [WorkerController::class, 'uploadPhoto']);
    Route::delete('/workers/{worker}/photo', [WorkerController::class, 'deletePhoto']);
    Route::get('/workers/{worker}/schedule', [WorkerScheduleController::class, 'show']);
    Route::put('/workers/{worker}/schedule', [WorkerScheduleController::class, 'update']);
    Route::delete('/workers/{worker}/schedule', [WorkerScheduleController::class, 'destroy']);
    Route::post('/slots/bulk', [SlotController::class, 'bulkStore']);
    Route::apiResource('slots', SlotController::class)->except('show');
    Route::get('/bookings', [BookingController::class, 'index']);
    Route::patch('/bookings/{token}/confirm', [BookingController::class, 'confirm']);
    Route::patch('/bookings/{token}/reject', [BookingController::class, 'reject']);
    Route::patch('/bookings/{token}/reschedule', [BookingController::class, 'reschedule']);

    Route::patch('/client/settings', [ClientController::class, 'updateSettings']);
    Route::post('/client/deploy-request', [ClientController::class, 'deployRequest']);
    Route::patch('/client/branding', [TenantController::class, 'updateBranding']);
    Route::post('/client/branding/logo', [TenantController::class, 'uploadLogo']);

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
