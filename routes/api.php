<?php

use Illuminate\Http\Request;
use Dingo\Api\Contract\Routing\Router;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:api');

//Route::get('user/{id}', function ($id) {
//    return 'User '.$id;
//});
$api = app('Dingo\Api\Routing\Router');

//$api->version('v1', function ($api) {
//    $api->get('users/{uid}',['middleware' => 'auth.channel.token'],['uses'=>'App\Http\Controllers\FeiMiController@test']);
//});
//$api->version('v1', function ($api) {
//    $api->get('feimi/{uid}', 'App\Http\Controllers\FeiMiController@getFeimiByUnionid');
//});

$api->version('v1',['middleware' => 'auth.channel.token'], function ($api) {
    $api->post('feimi/pointstore', 'App\Http\Controllers\FeiMiController@postConsumeFromPointStore');
    $api->post('feimi/pointstore/callback', 'App\Http\Controllers\FeiMiController@pointStoreCallBack');
    $api->get('feimi/test', 'App\Http\Controllers\FeiMiController@geifeimiapitest');
    $api->get('feimi/{uid}', 'App\Http\Controllers\FeiMiController@getFeimiByUnionid')->where('uid', '[0-9]+');
    $api->get('feimi/channel', 'App\Http\Controllers\FeiMiController@getFeimiChannels');
    $api->get('feimi/operationtypes', 'App\Http\Controllers\FeiMiController@getFeimiOperationsTypes');
    $api->get('feimi/operation', 'App\Http\Controllers\FeiMiController@getFeimiOperations');
    $api->get('feimi/consume', 'App\Http\Controllers\FeiMiController@getFeimiConsume');
    $api->get('feimi/rollback', 'App\Http\Controllers\FeiMiController@getFeimiRollback');
    $api->get('feimi/recycle', 'App\Http\Controllers\FeiMiController@getFeimiRecycle');
    $api->get('feimi/outputbill', 'App\Http\Controllers\FeiMiController@getFeimiOutputBill');
    $api->get('feimi/consumebill', 'App\Http\Controllers\FeiMiController@getFeimiConsumeBill');
    $api->get('feimi/details', 'App\Http\Controllers\FeiMiController@getFeimiDetails');
    $api->post('feimi/operation', 'App\Http\Controllers\FeiMiController@postFeimiOperations');



    $api->post('feimi/{uid}', 'App\Http\Controllers\FeiMiController@postFeimiByUnionid');

    $api->get('feimi/detail/{uid}', 'App\Http\Controllers\FeiMiController@getFeimiDetailByUnionid');
//    $api->post('feimi/output/{uid}', 'App\Http\Controllers\FeiMiController@postFeimiOutPutByUnionid');
//    $api->post('feimi/comsume/{uid}', 'App\Http\Controllers\FeiMiController@postFeimiConsumeByUnionId');
//    $api->post('feimi/rollback/{uid}', 'App\Http\Controllers\FeiMiController@postFeimiRollBackByUnionId');
//    $api->post('feimi/outoftime/{uid}', 'App\Http\Controllers\FeiMiController@postFeimiOutOfTimeByUnionId');

});

//$api->version('v1', function ($api) {
//    $api->get('users/{uid}', 'app\Http\Controllers\FeiMiController@test');
//    $api->get('users/{uid}',function($uid){
//        return "hello ".$uid;
//    });
//
//});

