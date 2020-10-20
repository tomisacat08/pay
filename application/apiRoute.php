<?php
/**
 * Api路由
 */

use think\Route;

Route::group('api', function () {
    Route::miss('api/Miss/index');
});
$afterBehavior = [
    '\app\api\behavior\ApiAuth',
    '\app\api\behavior\ApiPermission',
    '\app\api\behavior\RequestFilter'
];

Route::rule('api/5c92f1f7aaa16','api/BuildToken/getAccessToken', 'POST', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c9740f0ad400','api/test/test', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c98335fb10f7','api/AppApi/login', 'POST', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c99e0188cf1f','api/AppApi/uploadImg', 'POST', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c99e2a9583ff','api/AppApi/getDueInInfo', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c99e35112826','api/AppApi/confirmDueIn', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c99ee5c9b880','api/AppApi/unSettlementOrderList', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c9a07d218b6d','api/AppApi/getOrderList', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c9a08f6c9095','api/AppApi/getUserInfo', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c9a0bacf1826','api/AppApi/editPassword', 'POST', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c9a0d553fd45','api/AppApi/logout', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c9b453f06970','api/AppApi/getRefundInfo', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c9c2ec3e7548','api/AppApi/confirmRefund', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5c9d7ec2ce6bb','api/AppApi/getNoticeList', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5ca18043d6b44','api/AppApi/addWechat', 'POST', ['after_behavior' => $afterBehavior]);
Route::rule('api/5ca18a1b9b0a3','api/AppApi/delWechat', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5ca1b16109567','api/AppApi/wechatList', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5ca1fb74a41f7','api/AppApi/getAutoQrcode', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5ca1fbc579e2f','api/AppApi/uploadAutoQrcode', 'POST', ['after_behavior' => $afterBehavior]);
Route::rule('api/5ca20e8a8d6af','api/AppApi/usedWechat', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5cb458621b3c2','api/AppApi/setAutoQrcodeModel', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5ceca1897cbf0','api/AppApi/getImgUrl', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5d7711c3bb458','api/AppApi/getStatus', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5dde185248b1c','api/AppApi/getAppIdImg', 'GET', ['after_behavior' => $afterBehavior]);
Route::rule('api/5dde18d72ac65','api/AppApi/addAlipayAppId', 'POST', ['after_behavior' => $afterBehavior]);
Route::rule('api/5dde1b3da207b','api/AppApi/addQrcodeByUrl', 'POST', ['after_behavior' => $afterBehavior]);
Route::rule('api/5e9baf7ce9d9f','api/AppApi/addBankCard', 'POST', ['after_behavior' => $afterBehavior]);
Route::rule('api/5ebceb83cf39b','api/AppApi/addAccount', 'POST', ['after_behavior' => $afterBehavior]);