<?php

use Illuminate\Support\Facades\Route;

Auth::routes();

include('web/user.php');
include('web/admin.php');
include('web/card_disputes.php');

Route::get('/health-check', function () {
    return response()->json(['status' => 'ok']);
});
