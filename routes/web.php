<?php

use Illuminate\Support\Facades\Route;

// Public website user authentication is temporarily disabled while abuse is
// investigated. Mobile authentication continues through /api/v1/login and
// the admin panel keeps its separate authentication routes.
Auth::routes(['register' => false, 'login' => false]);
Route::get('/login/otp', [App\Http\Controllers\Auth\LoginController::class, 'showOtpForm'])->name('login.otp.form');
Route::post('/login/otp', [App\Http\Controllers\Auth\LoginController::class, 'verifyOtp'])->name('login.otp.verify');

include('web/user.php');
include('web/admin.php');
include('web/card_disputes.php');

Route::get('/health-check', function () {
    return response()->json(['status' => 'ok']);
});
