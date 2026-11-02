<?php

use Illuminate\Support\Facades\Route;

Auth::routes();
Route::get('/login/otp', [App\Http\Controllers\Auth\LoginController::class, 'showOtpForm'])->name('login.otp.form');
Route::post('/login/otp', [App\Http\Controllers\Auth\LoginController::class, 'verifyOtp'])->name('login.otp.verify');

include('web/user.php');
include('web/admin.php');
include('web/card_disputes.php');

Route::get('/health-check', function () {
    return response()->json(['status' => 'ok']);
});
