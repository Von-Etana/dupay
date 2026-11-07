<?php

use App\Http\Controllers\Admin\DashboardController;

Route::group(['prefix' => 'admin'], function () {

    Route::put('page/edit/{pageType}/{id}', [GeneralViewController::class, 'update'])->name('UpdateItem');
    Route::get('visitor-chart', [DashboardController::class, 'getVisitorChart'])->name('visitorChart');

    Route::get('get-js-item', [DashboardController::class, 'getJsItem'])->name('adminGetJsItem');
    Route::get('get-offer-js-item', [DashboardController::class, 'getOfferPlanJsItem'])->name('adminGetOfferJsItem');

});
