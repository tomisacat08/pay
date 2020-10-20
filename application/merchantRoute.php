<?php

use think\Route;

$afterBehavior = [
    '\app\merchant\behavior\ApiAuth',
   /*'\app\merchant\behavior\ApiPermission',
    '\app\merchant\behavior\MerchantLog'*/
];

Route::group('merchant', function () use ($afterBehavior) {
    //一些带有特殊参数的路由写到这里
    Route::rule([
        'Login/index'  => [
            'merchant/Login/index',
            [
                'method' => 'post',
                'after_behavior' => [
                ]
            ]
        ],
        'Login/logout' => [
            'merchant/Login/logout',
            [
                'method'         => 'get',
                'after_behavior' => [
                    '\app\merchant\behavior\ApiAuth',
                   /* '\app\merchant\behavior\MerchantLog'*/
                ]
            ]
        ],
        'Login/unlock' => [
            'merchant/Login/unlock',
            [
                'method'         => 'post',
                'after_behavior' => [
                    '\app\merchant\behavior\ApiAuth',
                ]
            ]
        ]
    ]);
    //大部分控制器的路由都以分组的形式写到这里
    Route::group('index', [
        'index'        => [
            'merchant/Index/index',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Menu', [
        'index'        => [
            'merchant/Menu/index',
            ['method' => 'post']
        ],
        'changeStatus' => [
            'merchant/Menu/changeStatus',
            ['method' => 'get']
        ],
        'add'          => [
            'merchant/Menu/add',
            ['method' => 'post']
        ],
        'edit'         => [
            'merchant/Menu/edit',
            ['method' => 'post']
        ],
        'del'          => [
            'merchant/Menu/del',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('User', [
        'own'          => [
            'merchant/User/own',
            ['method' => 'post']
        ],
        'payPasswordEdit' => [
            'merchant/User/payPasswordEdit',
            ['method' => 'post']
        ],
        'getGoogleQrcode'          => [
            'merchant/User/getGoogleQrcode',
            ['method' => 'post']
        ],
        'addGoogleAuth'          => [
            'merchant/User/addGoogleAuth',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Child', [
        'index'          => [
            'merchant/Child/index',
            ['method' => 'get']
        ],
        'add'          => [
            'merchant/Child/add',
            ['method' => 'post']
        ],
        'edit'          => [
            'merchant/Child/edit',
            ['method' => 'post']
        ],
        'del'          => [
            'merchant/Child/del',
            ['method' => 'post']
        ],
        'changeStatus'          => [
            'merchant/Child/changeStatus',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Log', [
        'index' => [
            'merchant/Log/index',
            ['method' => 'get']
        ],
        'del'   => [
            'merchant/Log/del',
            ['method' => 'get']
        ],
        'lists'   => [
            'merchant/Log/merchantLog',
            ['method' => 'get']
        ]

    ], ['after_behavior' => $afterBehavior]);

    Route::group('Merchant', [
        'indexBank' => [
            'merchant/Merchant/indexBank',
            ['method' => 'get']
        ],
        'indexCard' => [
            'merchant/Merchant/indexCard',
            ['method' => 'get']
        ],
        'addCard' => [
            'merchant/Merchant/addCard',
            ['method' => 'post']
        ],
        'editCard' => [
            'merchant/Merchant/editCard',
            ['method' => 'post']
        ],
        'delCard'   => [
            'merchant/Merchant/delCard',
            ['method' => 'get']
        ],
        'changeCardStatus'   => [
            'merchant/Merchant/changeCardStatus',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Withdraw', [
        'index' => [
            'merchant/Withdraw/index',
            ['method' => 'get']
        ],
        'withdrawIndex' => [
            'merchant/Withdraw/withdrawIndex',
            ['method' => 'get']
        ],
        'withdrawAudit' => [
            'merchant/Withdraw/withdrawAudit',
            ['method' => 'post']
        ],
        'indexDetails' => [
            'merchant/Withdraw/indexDetails',
            ['method' => 'get']
        ],
        'no_index' => [
            'merchant/Withdraw/no_index',
            ['method' => 'get']
        ],
        'confirm' => [
            'merchant/Withdraw/confirm',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('PayApi', [
        'index' => [
            'merchant/PayApi/index',
            ['method' => 'post']
        ],
        'lookApiKey' => [
            'merchant/PayApi/lookApiKey',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('MerchantOrder', [
        'index' => [
            'merchant/MerchantOrder/index',
            ['method' => 'get']
        ],
        'schedulingdetails' => [
            'merchant/MerchantOrder/schedulingdetails',
            ['method' => 'get']
        ],
        'confirmDueIn' => [
            'merchant/MerchantOrder/confirmDueIn',
            ['method' => 'get']
        ],
        'merchantOrderTest' => [
            'merchant/MerchantOrder/merchantOrderTest',
            ['method' => 'post']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Channel', [
        'getChannelList' => [
            'merchant/Channel/getChannelList',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::miss('merchant/Miss/index');
});
