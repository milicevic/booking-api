<?php

use App\Http\Controllers\BookingReviewController;
use App\Http\Controllers\ManageBookingController;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    DB::connection()->getPdo();

    return response()->json([
        'status' => 'ok',
        'message' => 'API is running',
        'version' => app()->version(),
    ]);
});

Route::get('/manage/{token}', [ManageBookingController::class, 'show'])
    ->name('booking.manage');

Route::post('/manage/{token}/cancel', [ManageBookingController::class, 'cancel'])
    ->name('booking.cancel');

// Vlasnik potvrđuje ili odbija rezervaciju iz mejla: link prikazuje stranicu, akcija ide tek POST-om
Route::middleware('signed')->group(function () {
    Route::get('/bookings/{token}/review', [BookingReviewController::class, 'show'])->name('booking.review');
    Route::post('/bookings/{token}/review', [BookingReviewController::class, 'apply'])
        ->middleware('throttle:20,1')
        ->name('booking.review.apply');
});
