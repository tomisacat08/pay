<?php

use think\Route;

$afterBehavior = [
    '\app\intermediary\behavior\ApiAuth',
];

Route::group('inter', function () use ($afterBehavior) {
    //一些带有特殊参数的路由写到这里
    Route::rule([
        'Login/index'  => [
            'intermediary/Login/index',
            ['method' => 'post']
        ],
        'Index/upload' => [
            'intermediary/Index/upload',
            [
                'method'         => 'post',
                'after_behavior' => [
                    '\app\intermediary\behavior\ApiAuth',
                    /*'\app\merchant\behavior\MerchantLog'*/
                ]
            ]
        ],
        'Login/logout' => [
            'intermediary/Login/logout',
            [
                'method'         => 'get',
                'after_behavior' => [
                    '\app\intermediary\behavior\ApiAuth',
                   /* '\app\merchant\behavior\MerchantLog'*/
                ]
            ]
        ]
    ]);
    //大部分控制器的路由都以分组的形式写到这里
    Route::group('index', [
        'index'        => [
            'intermediary/Index/index',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('User', [
        'own' => [
            'intermediary/User/own',
            ['method' => 'post']
        ],
        'getGoogleQrcode'          => [
            'intermediary/User/getGoogleQrcode',
            ['method' => 'post']
        ],
        'addGoogleAuth'          => [
            'intermediary/User/addGoogleAuth',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Log', [
        'merchantLog' => [
            'intermediary/Log/merchantLog',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Withdraw', [
        'index' => [
            'intermediary/Withdraw/index',
            ['method' => 'get']
        ],
        'indexDetails' => [
            'intermediary/Withdraw/indexDetails',
            ['method' => 'get']
        ],

    ], ['after_behavior' => $afterBehavior]);
    Route::group('MerchantOrder', [
        'index' => [
            'intermediary/MerchantOrder/index',
            ['method' => 'get']
        ],
        'schedulingdetails' => [
            'intermediary/MerchantOrder/schedulingdetails',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Merchant', [
        'index' => [
            'intermediary/Merchant/index',
            ['method' => 'get']
        ],
        'add' => [
            'intermediary/Merchant/add',
            ['method' => 'post']
        ],
        'edit' => [
            'intermediary/Merchant/edit',
            ['method' => 'post']
        ],
        'changeStatus'   => [
            'intermediary/Merchant/changeStatus',
            ['method' => 'get']
        ],
        'changeType'   => [
            'intermediary/Merchant/changeType',
            ['method' => 'get']
        ],
        'checkDispatch'   => [
            'intermediary/Merchant/checkDispatch',
            ['method' => 'get']
        ],
        'checkWithdraw'   => [
            'intermediary/Merchant/checkWithdraw',
            ['method' => 'get']
        ],
        'moneyLog'   => [
            'intermediary/Merchant/moneyLog',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::miss('intermediary/Miss/index');
});
