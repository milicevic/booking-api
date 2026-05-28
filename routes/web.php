<?php

use App\Http\Controllers\ManageBookingController;
use Illuminate\Support\Facades\Route;

Route::get('/manage/{token}', [ManageBookingController::class, 'show'])
    ->name('booking.manage');

Route::post('/manage/{token}/cancel', [ManageBookingController::class, 'cancel'])
    ->name('booking.cancel');
