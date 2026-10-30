<?php

use App\Http\Controllers\Api\V1\GeneralController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\Ecommerce\IndexController as EcommerceIndexController;
use App\Http\Controllers\Api\V1\Ecommerce\ProductController;
use App\Http\Controllers\Api\V1\KYCVerificationController;
use App\Http\Controllers\Api\V1\Telecom\AirtimeController;
use App\Http\Controllers\Api\V1\Telecom\BillController;
use App\Http\Controllers\Api\V1\Telecom\BulkSmsController;
use App\Http\Controllers\Api\V1\Telecom\CableController;
use App\Http\Controllers\Api\V1\Telecom\DataCardController;
use App\Http\Controllers\Api\V1\Telecom\DataController;
use App\Http\Controllers\Api\V1\Telecom\ExamController;
use App\Http\Controllers\Api\V1\Telecom\TransferController;
use App\Http\Controllers\Api\V1\VirtualCardController;
use App\Http\Controllers\Api\V1\Telecom\PhysicalCardController;

use App\Http\Controllers\Api\V1\UserController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\User\IndexController;
use App\Http\Controllers\Api\V1\Telecom\RechargePinController;
use App\Http\Controllers\StroWalletWebhookController;
use App\Http\Controllers\Jobs\CardMaintenanceController;
use App\Http\Controllers\SafeHavenWebhookController;
use App\Http\Controllers\Api\V1\ForeignAccountController;
use App\Http\Controllers\FincraWebhookController;
use App\Http\Controllers\TokenController;
use App\Http\Controllers\GraphWebHookController;
use App\Http\Controllers\WebHookController;
use App\Http\Controllers\FlutterWaveWebHookController;
use App\Http\Controllers\Api\V1\ExchangeController;

//version one



Route::group(['prefix' => 'v1'], function () {

Route::post('/login', [AuthController::class, 'login'])->name("userLogin")->middleware('throttle:auth');
Route::post('/register', [AuthController::class, 'register'])->name("userRegister")->middleware('throttle:auth');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name("userResetPassword")->middleware('throttle:otp');
Route::post('/send-email-otp', [AuthController::class, 'sendEmailOtp'])->name("sendEmailOtp")->middleware('throttle:otp');
Route::post('verify-email-otp',[AuthController::class, 'verifyEmailOTP'])->name('verifyEmailOTP')->middleware('throttle:otp');
Route::post('reset-password-verified',[AuthController::class, 'resetPasswordEmailOTP'])->name('resetPasswordEmailOTP')->middleware('throttle:otp');
Route::post('/check-user', [AuthController::class, 'checkUser'])->name("checkUser");

//sitesettings
Route::get('get-site-basic-settings', [IndexController::class, 'getBasicSiteSettings'])->name('basicSiteSettings');

//monnify atm
Route::post('/monnify-atm-payment', [WebHookController::class, 'monnifyAtmWebhook'])->name("monnifyAtmPayment");

//flutterwave
Route::post('/flutterwave-atm-payment', [FlutterWaveWebHookController::class, 'atmWebhook'])->name("flutterWaveAtmPayment");


Route::get('documentation/{type}', [GeneralController::class, 'getData'])->name("apiDocumentationItem");


    Route::group(['middleware'=>'auth:api'], function(){
    Route::post('update-profile-field',[UserController::class, 'updateProfileField'])->name('updateProfileField');
    //Route::post('send-email-otp',[VerificationController::class, 'sendEmailOTP'])->name('sendEmailOTP');
    //Route::post('verify-email-otp',[VerificationController::class, 'verifyEmailOTP'])->name('verifyEmailOTP');
    Route::post('email-verify-submit',[VerificationController::class, 'emailVerifySubmit'])->name('emailVerifySubmit');
    Route::post('resend-verify-submit',[VerificationController::class, 'resendEmail'])->name('resendVerifySubmit');
    Route::get('get-updated-user-information', [AuthController::class, 'getUpdatedUserInformation'])->name("getUpdatedUserInfo");
    });


    //optional login routes
Route::group(['middleware'=>'verifyTelecomAPiUsers'], function(){

    Route::get('pos-request', [UserController::class, 'posRequestDetails'])->name("POSRequest");
    Route::post('post-request', [UserController::class, 'submitPOSRequest'])->name("submitPOSRequest");


    Route::get('wallet-kyc-details', [KYCVerificationController::class, 'walletKYCDetails'])->name("WalletKYCDetails");
    Route::post('verification/wallet-kyc', [KYCVerificationController::class, 'verifyUserWalletUsingMonnifyBVN'])->name("WalletKYC");
    Route::post('verification/bank', [KYCVerificationController::class, 'verifyBankDetails'])->name("verifyBankDetails");
    Route::get('profile-kyc-details', [KYCVerificationController::class, 'profileKYCDetails'])->name("profileKYCDetails");
    Route::post('verification/profile-kyc', [KYCVerificationController::class, 'submitProfileKYC'])->name("ProfileKYC");
  

    Route::get('network/data/plan', [UserController::class, 'getDataNetworkPlan'])->name("getDataNetworkPlan");
    Route::get('item/{type}', [GeneralController::class, 'getItem'])->name("getItem");

    Route::get('data/networks', [DataController::class, 'getDataNetwork'])->name("getDataNetwork");
    Route::post('buy/data', [DataController::class, 'buyData'])->name("buyData")->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money']);


    Route::get('data-card/networks', [DataCardController::class, 'getNetwork'])->name("getDataCardNetwork");
    Route::post('buy/data-card', [DataCardController::class, 'buyDataCard'])->name("buyDataCard")->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money']);


    Route::get('airtime/networks', [AirtimeController::class, 'getAirtimeNetwork'])->name("getAirtimeNetwork");
    Route::post('buy/airtime', [AirtimeController::class, 'buyAirtime'])->name("buyAirtime")->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money']);

    Route::get('cable-list', [CableController::class, 'getCableList'])->name("getCableList");
    Route::post('buy/cable', [CableController::class, 'buyCableSub'])->name("buyCable")->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money']);
    Route::post('verify-cable-number', [CableController::class, 'verifyCableNumber'])->name("verifyCable");

    Route::get('airtime-to-cash/networks', [AirtimeController::class, 'getAirtimeToCashNetwork'])->name("getAirtimeToCashNetwork");
    Route::post('convert-airtime-to-cash', [AirtimeController::class, 'convertAirtimeToCash'])->name("convertAirtimeToCash")->middleware(['notBlocked', 'checkUserTransactionLimit']);


    Route::get('bill-list', [BillController::class, 'getBillList'])->name("getBillList");
    Route::post('pay-bill', [BillController::class, 'buyBill'])->name("buyBill")->middleware(['notBlocked', 'checkUserTransactionLimit']);
    Route::post('verify-bill', [BillController::class, 'verifyBillNumber'])->name("verifyBill");

    Route::post('send-bulk-SMS', [BulkSmsController::class, 'sendBulkSms'])->name("sendBulkSms")->middleware(['notBlocked', 'checkUserTransactionLimit']);

    Route::get('exam/amount', [ExamController::class, 'calAmount'])->name("getExamAmount");
    Route::post('buy/exam-pin', [ExamController::class, 'buyExam'])->name("buyExamPin")->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money']);


     //bank transfer
    //  Route::get('bank-transfer/banklist', [TransferController::class, 'getBankList'])->name("getBankList");
    
    
    
    
    
    
    
    
    
    
    
    
    
    

     Route::get('bank-transfer/usd-to-bank', [TransferController::class, 'transferUSDtoBankNG'])->name("transferUSDtoBankNG")->middleware('notBlocked');


      //virtual card
           Route::get('virtual-card/exchanges-rates', [VirtualCardController::class, 'rates'])->name("fundVirtualCardRates");
                 Route::post('virtual-card/fund', [VirtualCardController::class, 'fundVirtualCard'])->name("fundVirtualCard")->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money']);



      Route::resource('virtual-card', VirtualCardController::class);
      Route::get('virtual-card/fetch/{currency}', [VirtualCardController::class, 'fetchCard']);
      Route::get('virtual-card/reveal/{currency}', [VirtualCardController::class, 'revealCard']);
      
      // USD
      
     
     
     
     
      
     Route::post('virtual-card/withdraw', [VirtualCardController::class, 'withdrawFromVirtualCard'])->name("withdrawFromVirtualCard")->middleware(['notBlocked', 'throttle:money']);
     Route::post('virtual-card/status-update', [VirtualCardController::class, 'virtualCardStatusUpdate'])->name("virtualCardStatusUpdate");
     Route::post('virtual-card/freeze-or-unfreeze', [VirtualCardController::class, 'freezeUnfreeze'])->name("freezeUnfreezeVirtualCard");
     
     Route::get('virtual-card/transactions/{id}/{currency}', [VirtualCardController::class, 'getVirtualCardTransactions'])->name("getVirtualCardTransaction");
     
     // NGN
     Route::post('virtual-card/card/change-pin', [VirtualCardController::class, 'changeCardPin']);
    Route::post('virtual-card/card/reset-pin', [VirtualCardController::class, 'resetCardPin']);
    Route::post('virtual-card/card/change-status', [VirtualCardController::class, 'changeCardStatus']);
    Route::post('virtual-card/card/enable2fa', [VirtualCardController::class, 'enable2FA']);
    
    
    
    Route::get('/card/get', [VirtualCardController::class, 'get']);
     
     
     
     //physical card 
     
     
 
     


    Route::get('all-users', [UserController::class, 'allUsers'])->name("getAllUsers");
    Route::get('all-users/search/{search}', [UserController::class, 'searchUsers'])->name("UserSearchUser");
    Route::get('find-user/{search?}', [UserController::class, 'findUser'])->name("UserFindUser");
    Route::get('get-user/{search}', [UserController::class, 'getUser'])->name("UserGetUser");
    Route::post('fund/transfer', [TransferController::class, 'transferFund'])->name("transferFund")->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money', 'deduplicateMoney']);
    Route::post('fund/request', [TransferController::class, 'requestFund'])->name("requestFund")->middleware(['notBlocked', 'checkUserTransactionLimit']);
    Route::post('fund/withdraw', [TransferController::class, 'transferBank'])->name("withdrawFund")->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money', 'deduplicateMoney']);
    Route::post('fund/withdraw/two', [TransferController::class, 'transferBankTwo'])->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money', 'deduplicateMoney']);
    Route::get('bank-transfer/banklist', [TransferController::class, 'getBankList'])->name("getBankList");
    Route::post('bank-transfer/name-enquiry', [TransferController::class, 'nameEnquiry'])->name('nameEnquiry');
    Route::post('fund/manual-funding', [UserController::class, 'manualFunding'])->name("manualFunding");

    Route::get('transactions/{search?}', [UserController::class, 'getTransaction'])->name("UserGetTransaction");
    Route::get('transactions/filter', [UserController::class, 'getTransactionByDate'])->name("UserTransactionFilter");
    // Keeps the device push token current after login — see
    // UserController::updateFcmToken for why login/register alone isn't enough.
    Route::post('update-fcm-token', [UserController::class, 'updateFcmToken'])->name("userUpdateFcmToken");
    Route::post('logout', [UserController::class, 'logoutCurrentToken'])->name('userLogout')->middleware('auth:api');

    Route::post('transactions/create-pin', [UserController::class, 'createTransactionPin'])->name("userCreateTransationPin");
    Route::post('transactions/change-pin', [UserController::class, 'changeTransactionPin'])->name("userChangeTransationPin");

    // Route::get('virtual-account/create', [UserController::class, 'createVirtualAccount'])->name("userCreateVirtualAccount");
    Route::get('bank-account', [UserController::class, 'getBankAccount'])->name("userGetBankAccount");
    Route::post('bank-account/add', [UserController::class, 'storeBankAccount'])->name("userAddBankAccount");
    Route::get('bank-account/delete/{id}', [UserController::class, 'distoryBankAccount'])->name("userDeleteBankAccount");


    Route::post('setting/change-password', [UserController::class, 'updatePassword'])->name("userChangePassword");
    Route::post('setting/delete-account', [UserController::class, 'deleteAccount'])->name("userDeleteAccount");
    Route::post('setting/update-profile', [UserController::class, 'updateProfile'])->name("userUpdateProfile");
    Route::post('setting/account-upgrade', [UserController::class, 'upgradeAccount'])->name("userUpgradeAccount")->middleware(['notBlocked', 'checkUserTransactionLimit']);
    Route::post('service-pricing', [UserController::class, 'levelServicePricing'])->name("userSevicePricing");

    Route::get('account-upgrade-list', [UserController::class, 'upgradeLevelList'])->name("userUpgradeAccountList");

    Route::post('support/submit-new-ticket', [UserController::class, 'storeSupport'])->name('userSubmitSupport');
    Route::post('support/submit-new-message/{ticketNumber}', [UserController::class, 'storeSupportMessage'])->name('userSubmitSupportMessage');

    Route::post('redeem-commission', [UserController::class, 'redeemCommission'])->name('userSubmitRedeemCommission')->middleware(['notBlocked', 'checkUserTransactionLimit']);

    Route::post('video-watch-bonus', [UserController::class, 'rewardVideoCommission'])->name('userSubmitVideoWatchCommission');
    
    
    
    Route::post('/buy-epin', [RechargePinController::class, 'buyEpin'])->name('buy.epin')->middleware(['notBlocked', 'checkUserTransactionLimit']);

    Route::get('/user/fetch-epins', [RechargePinController::class, 'fetchEpins']);
    
    Route::get('/get-epin-plain', [RechargePinController::class, 'epinPlan']);
    

    // Route::resource('physical-card', PhysicalCardController::class);




Route::post('/try-sms', [RechargePinController::class, 'trysms'])->middleware(['auth:api', 'throttle:money']);


    // Virtual Account
    Route::get('virtual-account/create', [UserController::class, 'createVirtualAccount'])->name("userCreateVirtualAccount");
    Route::get('virtual-account/fetch', [UserController::class, 'getVirtualAccount']);
 
 
  

Route::post('/create-naira-customer', [PhysicalCardController::class, 'createCustomer'])->name('create.naira.customer');
Route::post('/create-atm-card', [PhysicalCardController::class, 'createCard'])->name('create.atm.card')->middleware('notBlocked');
Route::post('/card/change-pin', [PhysicalCardController::class, 'changeCardPin']);
Route::post('/card/reset-pin', [PhysicalCardController::class, 'resetCardPin']);

Route::post('/card/change-status', [PhysicalCardController::class, 'changeCardStatus']);
Route::post('/update-card-channels', [PhysicalCardController::class, 'cardToggleChannels']);
Route::post('/enable2fa', [PhysicalCardController::class, 'enable2FA']);
Route::post('/physical-card/block', [PhysicalCardController::class, 'reportCard']);

Route::post('setup-card', [PhysicalCardController::class, 'setupCard'])->name("setupCard")->middleware('notBlocked');
     Route::resource('physical-card', PhysicalCardController::class);
    


Route::post('update-sms-alerts', [UserController::class, 'toggleSms'])->name('toggleSms');

Route::get('fees', [UserController::class, 'fees'])->name('fees');


    // 
    // #######     Foreign Account #######
    //
    Route::post('/foreign/account/request', [ForeignAccountController::class, 'store'])->name("foreign.account.request");
    Route::post('/foreign/account/create', [ForeignAccountController::class, 'createAccount']);
    Route::get('/foreign/account/fetch/{currency}', [ForeignAccountController::class, 'getForeignAccount'])->name("foreign.account.fetch");
    Route::post('/foreign/account/whitelist/add/{id}', [ForeignAccountController::class, 'whitelistAccount']);
    Route::post('/foreign/account/whitelist/list/{id}', [ForeignAccountController::class, 'listWhitelistAccount']);
    Route::post('/foreign/account/whitelist/remove/{id}/{item_id}', [ForeignAccountController::class, 'removeWhitelistAccount']);
    
    Route::post('/foreign/account/convert-currency', [ForeignAccountController::class, 'convert'])->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money']); 


    Route::post('/convert-currency', [ExchangeController::class, 'convert'])->middleware(['notBlocked', 'checkUserTransactionLimit', 'throttle:money']);
    Route::get('/exchange-rates', [ExchangeController::class, 'rates']);


});


Route::post('/webhooks/strowallet/atm/withdraw', [StroWalletWebhookController::class, 'handleHook']);
Route::post('/webhooks/strowallet/approve', [StroWalletWebhookController::class, 'debitTransaction']);
Route::post('/webhooks/safehaven', [SafeHavenWebhookController::class, 'virtualAccountTransferHook'])->name('safehavenWebHook.api');
Route::post('/v1/webhooks/safehaven', [SafeHavenWebhookController::class, 'virtualAccountTransferHook'])->name('safehavenWebHook.api.v1');
Route::post('/webhooks/fincra', [FincraWebhookController::class, 'handle']);
Route::post('/webhooks/graph', [GraphWebHookController::class, 'handle']);






Route::get('/get-json', [RechargePinController::class, 'getJson']);




Route::post('/job/card-maintenance', [CardMaintenanceController::class, 'chargeAtmCardMaintenance']);

//ecommerce
Route::group(['middleware'=>'verifyTelecomAPiUsers', 'prefix' => 'ecommerce'], function(){
    Route::get('product', [ProductController::class, 'index'])->name('product');
    Route::get('product/details/{id}', [ProductController::class, 'show'])->name('viewProduct');
    
});




Route::group(['middleware'=>'auth:api', 'prefix' => 'ecommerce'], function(){

    Route::get('get-category', [EcommerceIndexController::class, 'getCategory'])->name('getCategory');


    
    Route::get('my-product', [ProductController::class, 'myIndex'])->name('myProduct');
    Route::post('product/create', [ProductController::class, 'store'])->name('storeProduct');
    Route::post('product/report', [ProductController::class, 'reportProduct'])->name('reportProduct');
    Route::post('product/request-call-back', [ProductController::class, 'requestCallBack'])->name('requestCallBack');
    Route::post('product/make-unavailable', [ProductController::class, 'markProductUnavailable'])->name('markProductUnavailable');
   
    Route::delete('product/{id}', [ProductController::class, 'destroy'])->name('destroyProduct');


});



});
