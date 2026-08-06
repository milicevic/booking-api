<?php

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
