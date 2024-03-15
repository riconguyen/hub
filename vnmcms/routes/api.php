<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteÚServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
//Route::middleware('auth:api')->get('/user', function (Request $request) {
//    return $request->user();
//});
Route::group(['middleware' => ['api', 'cors','logVT']], function () {




    Route::post('auth/login', 'ApiController@login'); // API LOGIN
    Route::post('auth/loginWeb', 'ApiController@loginWeb'); // WEB LOGIN WITH GCAPCHA TOKEN VERIFY
    Route::group(['middleware' => ['jwt.auth','jwt-auth']], function () {


      Route::get('roles', 'UserController@getListRoles');
      Route::get('entities', 'UserController@getListEntity');
      //  Route::post('entities', 'UserController@setEntity');
      Route::post('entity-role', 'UserController@setEntityRole');
      Route::post('remove-entity-role', 'UserController@removeEntityRole');
      Route::post('remove-roles', 'UserController@setRemoveRoles');
      Route::post('roles', 'UserController@postRoles');



        Route::post('user', 'ApiController@getAuthUser');
        Route::post('check', 'ApiController@check');
        Route::post('auth/logout', 'ApiController@logOutApi');
      Route::prefix('/customer')->group(function () {
        Route::post('tos', 'V1CustomerController@postTosService'); //3.11
      });
        // Viettel API


        Route::prefix('/v1')->group(function () {

            Route::prefix('/service')->group(function () { // 2
                Route::get('getServices', 'V1ServiceController@getServices'); //2.1
                Route::get('getServiceByCode', 'V1ServiceController@getServiceByCode'); //2.2
            });
            Route::prefix('/customer')->group(function () {

                Route::get('getCustomers', 'V1CustomerController@getCustomers'); //3.1
                Route::post('postSearchCustomer', 'V1CustomerController@postSearchCustomer'); //3.1
                Route::post('getCustomersV2', 'V1CustomerController@getCustomersV2'); //3.1
                Route::post('addCustomer', 'V1CustomerController@addCustomer'); //3.2
                Route::post('editCustomer', 'V1CustomerController@editCustomer'); //3.3
                Route::post('changeCustomerIdentity', 'V1CustomerController@changeCustomerIdentity'); //3.4
                Route::post('changeCustomerProductCode', 'V1CustomerController@changeCustomerProductCode'); //3.5
                Route::post('changeCustomersStatus', 'V1CustomerController@changeCustomersStatus'); //3.6
                Route::post('changePauseState', 'V1CustomerController@changePauseState'); //3.6
                Route::post('removeCustomer', 'V1CustomerController@removeCustomer'); //3.7
                Route::post('upgradeEnterpriseToHotline', 'V1CustomerController@upgradeEnterpriseToHotline'); //3.8
                Route::post('rollbackCustomer', 'V1CustomerController@rollbackCustomer'); //3.9
              Route::post('tos', 'V1CustomerController@postTosService'); //3.11
              Route::post('postRechargeCustomer', 'V1CustomerController@postRechargeCustomer'); //3.11
              Route::post('cfu', 'V1CustomerController@postUpdateCfu'); //3.12

            });
            Route::prefix('/hotline')->group(function () { // 4
                Route::get('getCustomerHotlines', 'V1HotlineController@getCustomerHotlines'); //4.1
                Route::post('addCustomerHotlines', 'V1HotlineController@addCustomerHotlines'); //4.2
                Route::get('getHotlineConfig', 'V1HotlineController@getHotlineConfig'); //4.3
                Route::post('changeHotlineConfig', 'V1HotlineController@changeHotlineConfig'); //4.4

                Route::post('changeHotlineStatus', 'V1HotlineController@changeHotlineStatus'); //4.5
                Route::post('removeHotline', 'V1HotlineController@removeHotline'); //4.6
                Route::post('changeHotlineProfile', 'V1HotlineController@changeHotlineProfile'); //4.7
                Route::post('changePauseStateHotline', 'V1HotlineController@changePauseStateHotline'); //4.7
            });

          Route::get('webservice', 'ServerController@getActiveServer'); //5.1

          Route::prefix('/charging')->group(function () { // 2
            Route::post('request-check-charging', 'HomePhoneChargingController@postRequestCheckCharging'); //2.1
            Route::post('request-charge', 'HomePhoneChargingController@postRequestCharge'); //2.2
          });


        });


        // WEB REQUEST API
        Route::prefix('/billing')->group(function () {
            Route::post('/getBillingByEntNumber/{id}', 'BillingController@getBillingByEntNumber');
            Route::post('/getBillLog/{id}', 'BillingController@getBillLog');
            Route::post('/getBillLogV2', 'BillingController@getBillLogV2');
        });
      Route::post('/logs', 'LogController@getLogs');

      Route::prefix('/role')->group(function () {
        Route::post('/getRoles', 'RoleController@getRoles');
        Route::post('/getUserByRole', 'RoleController@getUserByRole');
      });


      Route::prefix('/services')->group(function () {
            Route::get('/getServiceZoneQuantityType', 'ServiceController@getServiceZoneQuantityType');
            Route::post('/postServicePrefixType', 'ServiceController@postServicePrefixType');
            Route::post('/deleteServicePrefixType', 'ServiceController@deleteServicePrefixType');
        });


        Route::prefix('/accounts')->group(function () {
            Route::get('/getDashboardInfo', 'DashboardController@getDashboardInfo');
            Route::post('/postViewDashboardDailyFlow', 'DashboardController@postViewDashboardDailyFlow');
            Route::get('/getList', 'AccountController@getList');
            Route::get('/getListCustomers', 'AccountController@getListCustomers');
            Route::get('/getServiceConfig', 'ServiceController@getServiceConfig');
            Route::get('/getServiceConfigById/{id}', 'ServiceController@getServiceConfigById');
            Route::get('/getCallFeeConfigByServiceId/{id}', 'AccountController@getCallFeeConfigByServiceId');
            Route::get('/getAccountsById/{id}', 'AccountController@getAccountsById');
            Route::get('/getConfigByCustomer/{id}', 'AccountController@getConfigByCustomer');
            Route::get('/getListSips', 'SipController@getListSips');
            Route::get('/getFeeByEntNumber/{id}', 'FeeController@getFeeByEntNumber');
            Route::post('/postAccountBlock', 'AccountController@postAccountBlock');
            Route::post('/postServiceSubscriber', 'AccountController@postServiceSubscriber');  // Them khach hang
            Route::post('/postServiceSubscriber/{id}', 'AccountController@updateServiceSubscriber'); // Sua khach hang
            Route::post('/postQuickSetupSubcriber', 'AccountController@postQuickSetupSubcriber'); // Them nhanh khach hang
            Route::post('/postServiceConfig', 'ServiceController@postServiceConfig');
            Route::post('/postServiceConfigHotlinePrice', 'ServiceController@postServiceConfigHotlinePrice');
            Route::post('/postServiceConfigPrice', 'ServiceController@postServiceConfigPrice');
            Route::post('/postServiceCallPrice', 'ServiceController@postServiceCallPrice');
            Route::post('/postServiceSmsPrice', 'ServiceController@postServiceSmsPrice');
            Route::post('/postQuickSetupSubcriber', 'AccountController@postQuickSetupSubcriber');
            Route::post('/postServiceOptionPrice', 'ServiceController@postServiceOptionPrice');
            Route::post('/postServiceQuantityPrice', 'ServiceController@postServiceQuantityPrice');
            Route::post('/postServiceCustomerHotline', 'AccountController@postServiceCustomerHotline');
            Route::post('/postServiceCustomerHotline/{id}', 'AccountController@updateServiceCustomerHotline');
            Route::post('/postServiceCustomerOption', 'AccountController@postServiceCustomerOption');
            Route::post('/postServiceCustomerQuantity', 'AccountController@postServiceCustomerQuantity');
            Route::post('/saveFeeLimit', 'AccountController@saveFeeLimit');


            Route::post('/saveAddFeeLimit', 'AccountController@saveAddFeeLimit');
            Route::post('/getFeeLimitLogs', 'AccountController@getFeeLimitLogs');


            Route::post('/saveRedWarning', 'AccountController@saveRedWarning');   // Update 2002 05 22
            Route::post('/serviceAddedCode', 'V1CustomerController@serviceAddedCode'); // Serviced Added code  20200302
            Route::get('/operator-telco', 'ServiceController@getOperatorTelco'); // Serviced Added code  20200302

            // Get account activity

          Route::get('/getListHotLinesByCustomers', 'AccountController@getListHotLinesByCustomers');
        });
        Route::prefix('/sip')->group(function () {
            Route::post('/postSipRouting', 'SipController@postSipRouting');
            Route::post('/callDebugPython/{id}', 'SipController@callDebugPython');
            Route::post('/postSearchSip', 'SipController@postSearchSip');

            Route::post('/postCallLog/{id}', 'SipController@postCallLog');
            Route::get('/getSipConfigByCaller/{id}', 'SipController@getSipConfigByCaller');
            Route::post('/putDeleteSipByCaller', 'SipController@putDeleteSipByCaller');
          Route::post('/postCheckNumberRouting', 'SipController@postCheckNumberRouting');
        });
        Route::prefix('/service')->group(function () {
            Route::post('/deleteQuantityPrice', 'ServiceController@deleteQuantityPrice');
            Route::post('/deleteOptionPrice', 'ServiceController@deleteOptionPrice');
            Route::post('/deleteSmsPrice', 'ServiceController@deleteSmsPrice');
            Route::post('/deleteCallPrice', 'ServiceController@deleteCallPrice');
            Route::post('/deleteConfigHotlinePrice', 'ServiceController@deleteConfigHotlinePrice');
            Route::post('/deleteConfigPrice', 'ServiceController@deleteConfigPrice');
        });
        Route::prefix('/report')->group(function () {
            Route::post('/quantity', 'ReportController@postViewReportQuantity');
            Route::post('/flow', 'ReportController@postViewReportFlow');
            Route::post('/customer', 'ReportController@postViewReportCustomer');
            Route::post('/monthly', 'ReportController@postViewReportMonthlyAudit');
            Route::post('/searchReportGrowth', 'ReportController@postSearchReportGrowth');
            Route::post('/searchReportAudit', 'ReportController@postSearchReportAudit');
            Route::post('/searchReportMonthBilling', 'ReportController@searchReportMonthBilling');
        });
        Route::post('/activity', function (Request $request) {
            $table = $request->input('table');
            $id = $request->input('id');
            $activity = new \App\Http\Controllers\ActivityController();
            $active = $activity->GetActivity($table, $id);
            return $active;
        });
        Route::prefix('/admin')->group(function () {
            Route::post('/users', 'UserController@store');
            Route::get('/listServer', 'ServerController@getList');
            Route::post('/postServer', 'ServerController@postServer');
            Route::post('/postActiveServer', 'ServerController@postActiveServer');

            Route::post('/server-resource', 'ServerController@postServerResource');
            Route::post('/server-move-customer', 'MoveCustomerController@postServerMoveCustomer');
            Route::post('/users/{id}', 'UserController@update');
            Route::get('/am', 'UserController@getAms');
          Route::post('/migration', 'MoveCustomerController@mirgradeServer');
        });



      Route::prefix('/nd91')->group(function () {
        Route::get('/init', 'Nd91Controller@initNd91Config');
        Route::get('/init-time-range', 'Nd91Controller@initNd91TimeRange');
        Route::get('/init-quota', 'Nd91Controller@initNd91Quota');
        Route::post('/set-dnc', 'Nd91Controller@postSaveDncConfig');
        Route::post('/set-quota', 'Nd91Controller@postSaveQuotaConfig');
        Route::post('/set-quota-item', 'Nd91Controller@postSaveQuotaConfigItem');
        Route::post('/set-time-range', 'Nd91Controller@postSaveTimeRangeConfig');
        Route::post('/report', 'Nd91Controller@getReport');
        Route::post('/report-brandname', 'Nd91Controller@getReportBrandName');
        Route::post('/report-detail', 'Nd91Controller@getReportDetail');
        Route::post('/dnc-blacklist', 'DNCController@postDNCBlacklist');
        Route::post('/deactive-dnc-blacklist', 'DNCController@postDeactiveDNCBlacklist');
        Route::get('/dnc-blacklist', 'DNCController@getDNCBlacklist');
        Route::get('/dnc-whitelist', 'DNCController@getDNCWhiteList');
        Route::post('/dnc-whitelist', 'DNCController@postDNCWhitelist');
        Route::post('/syncReport', 'Nd91Controller@synNd91Report');

      });



        Route::prefix('/user')->group(function () {
            Route::post('/changepassword', 'UserController@changePassword');
        });
        Route::resource('/admin/users', 'UserController');


    });
});




