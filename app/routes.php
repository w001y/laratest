<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/


/**
 * The Loyalty API
 */

Route::group(array('prefix' => 'api/v1'), function()
{

    /*Route::get('{any}/{args}', function($action, $args = null)
    {
        return var_dump(explode('/', $args), true);
    })->where('args', '(.*)');*/


    //Route::resource('api_auth',         'APIAuthController');
    Route::resource('apilog',           'APILogController');
    Route::resource('card',             'CardController');
    Route::resource('cards',            'CardController');
    Route::resource('customerjourney',  'CustomerJourneysController');
    Route::resource('customerjourneys', 'CustomerJourneysController');
    Route::resource('dataversion',      'DataVersionController');
    Route::resource('dataversions',     'DataVersionController');
    Route::resource('emailplatforms',   'EmailPlatformsController');
    Route::resource('emailqueue',       'EmailQueueController');
    Route::resource('events',           'EventController');
    Route::resource('ironworkerlog',    'IronWorkerLogController');
    Route::resource('member',           'MemberController');
    Route::resource('members',          'MemberController');
    Route::resource('membertier',       'MemberTiersController');
    Route::resource('membertiers',      'MemberTiersController');
    Route::resource('memberlevel',      'MemberTiersController');
    Route::resource('memberlevels',     'MemberTiersController');
    Route::resource('product',          'ProductController');
    Route::resource('products',         'ProductController');
    Route::resource('redeem',           'RedeemController');
    Route::resource('redemptions',      'RedemptionController');
    Route::resource('redemption',       'RedemptionController');
    Route::resource('redemptionrefund', 'RedemptionRefundController');
    Route::resource('redemptionrefunds','RedemptionRefundController');
    Route::resource('reward',           'RewardController');
    Route::resource('rewards',          'RewardController');
    Route::resource('rewardbank',       'RewardBankController');
    Route::resource('rewarddefinition', 'RewardDefinitionController');
    Route::resource('rewarddefinitions','RewardDefinitionController');
    Route::resource('rewardtype',       'RewardTypeController');
    Route::resource('rewardtype',       'RewardTypeController');
    Route::resource('rewardtypes',      'RewardTypeController');
    Route::resource('store',            'StoreController');
    Route::resource('stores',           'StoreController');
    Route::resource('staff',            'StaffController');
    Route::resource('systemsetting',    'SystemSettingsController');
    Route::resource('systemsettings',   'SystemSettingsController');
    Route::resource('systemstatus',     'SystemStatusController');
    Route::resource('transaction',      'TransactionController');
    Route::resource('transactions',     'TransactionController');
    Route::resource('transactionitem',  'TransactionItemController');
    Route::resource('transactionitems', 'TransactionItemController');

});


/**
 * Iron.io Push Queues - no more cron jobs!
 *
 * There needs to be only one listing here.
 * Any valid POST payload being sent to the endpoint below is parsed, and routed to
 * the model that takes care of this job.
 *
 * Learn more:
 * http://vimeo.com/64703617        - Taylor Otwell shows the iron.io integration with Laravel
 * http://laravel.com/docs/queues   - A generic overview of queues in Laravel
 */

Route::post('queue/push', function(){
    return Queue::marshal();
});





/**
 * The home "/" controller needs to go last in the list.
 */

Route::controller('/', 'HomeController');



