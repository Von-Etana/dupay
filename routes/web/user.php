<?php

use App\Http\Controllers\FlutterWaveWebHookController;
use App\Http\Controllers\User\IndexController;
use App\Http\Controllers\WebHookController;
use App\Http\Controllers\PaystackWebHookController;
use App\Http\Controllers\SafeHavenWebhookController;


// Route::get('/', function () {
//     return redirect('admin/login');
// });

Route::get('/', [IndexController::class, 'index'])->name("index");
Route::get('/pages/{id}', [IndexController::class, 'pages'])->name("getPages");
Route::get('/privacy-policy', [IndexController::class, 'privacy'])->name("privacy");

Route::get('/user-account-deletion', function () {
    return view('delete_account');
});


//monnify webhook
Route::post('monnify-webhook', [WebHookController::class, 'monnifyWebHook'])->name('monnifyWebHook');
Route::get('monnify-verify-transaction/{paymentRef}', [WebHookController::class, 'monnifyVerifyTransaction'])->name('monnifyVerifyTransaction');

//paystack webhook
Route::post('paystack-webhook', [PaystackWebHookController::class, 'index'])->name('paystackWebHook');

//flutterwave webhook
Route::post('flutterwave-virtual-account-webhook', [FlutterWaveWebHookController::class, 'index'])->name('flutterWaveWebHook');
Route::post('/vcard/{email}', [FlutterWaveWebHookController::class, 'vcardCallBack'])->name('vcardCallBack');

//safehaven webhook
Route::post('webhooks/safehaven', [SafeHavenWebhookController::class, 'virtualAccountTransferHook'])->name('safehavenWebHook.web');







