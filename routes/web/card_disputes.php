<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CardDisputeController;
use App\Http\Controllers\Admin\VerificationController;

/*
|--------------------------------------------------------------------------
| Physical ATM card disputes (StroWallet) — admin only
|--------------------------------------------------------------------------
|
| Self-contained so nothing has to be pasted inside the nested groups in
| routes/web/admin.php. The nesting below mirrors that file exactly:
|
|   Route::group(['prefix' => 'adminoffice'], ...)                  (admin.php line 32)
|     Route::group(['middleware' => ['Admin','visitors','admin.pin'],
|                   'as' => 'admin.'], ...)                         (admin.php line 66)
|
| so URLs come out as /adminoffice/card-disputes and route names as
| admin.card_disputes.*, identical to what the blades and controller expect.
|
| Registered from routes/web.php with:
|     include('web/card_disputes.php');
|
*/

Route::group(['prefix' => 'adminoffice'], function () {

    Route::group(['middleware' => ['Admin', 'visitors', 'admin.pin'], 'as' => 'admin.'], function () {

        /*
         | Static segments are declared BEFORE {id} so "ajax", "cards" and
         | "create" are not swallowed by the wildcard.
         */
        Route::prefix('card-disputes')->name('card_disputes.')->group(function () {

            Route::get('/',      [CardDisputeController::class, 'index'])->name('index');
            Route::get('ajax',   [CardDisputeController::class, 'ajax'])->name('ajax');
            Route::get('cards',  [CardDisputeController::class, 'searchCards'])->name('cards');
            Route::get('create', [CardDisputeController::class, 'create'])->name('create');
            Route::post('/',     [CardDisputeController::class, 'store'])->name('store');

            Route::get('transactions/{physicalCardId}', [CardDisputeController::class, 'cardTransactions'])
                 ->name('transactions');

            Route::get('{id}',         [CardDisputeController::class, 'show'])->name('show');
            Route::post('{id}/retry',  [CardDisputeController::class, 'retry'])->name('retry');
            Route::post('{id}/status', [CardDisputeController::class, 'updateStatus'])->name('status');
            Route::delete('{id}',      [CardDisputeController::class, 'destroy'])->name('destroy');
        });

        /*
         | KYC: change the identity method (BVN / NIN) without approving.
         | Lives here rather than in admin.php for the same reason — one less
         | edit inside an existing file.
         */
        Route::post('verifications/{id}/method', [VerificationController::class, 'updateMethod'])
             ->name('verifications.method');
    });
});
