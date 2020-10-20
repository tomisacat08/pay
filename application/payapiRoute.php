<?php

use think\Route;

$afterBehavior = [
    //'\app\payapi\behavior\ApiAuth',
];

Route::group('payapi', function () use ($afterBehavior) {
    //一些带有特殊参数的路由写到这里
    Route::rule([
        'Login/index'  => [
            'admin/Login/index',
            ['method' => 'post']
        ],
        'Index/upload' => [
            'admin/Index/upload',
            [
                'method'         => 'post',
                'after_behavior' => [
                    '\app\admin\behavior\ApiAuth',
                    '\app\admin\behavior\AdminLog'
                ]
            ]
        ],
        'Login/logout' => [
            'admin/Login/logout',
            [
                'method'         => 'get',
                'after_behavior' => [
                    '\app\admin\behavior\ApiAuth',
                    '\app\admin\behavior\AdminLog'
                ]
            ]
        ]

    ]);
    //大部分控制器的路由都以分组的形式写到这里
    Route::group('Index', [
        'order'        => [
            'payapi/Index/order',
            ['method' => 'post']
        ],
        'api'        => [
            'payapi/Index/api',
            ['method' => 'get']
        ],
        'testCallBak'        => [
            'payapi/Index/testCallBak',
            ['method' => 'post']
        ],
        'alipay_command_key/:id' => [
            'payapi/Index/alipay_command_key',
            ['method' => 'get']
        ],
        'alipay_h5/:id' => [
            'payapi/Index/alipay_h5',
            ['method' => 'get']
        ],
        'alipayToCard/:id' => [
            'payapi/Index/alipayToCard',
            ['method' => 'get']
        ],
        'wechat_qrcode/:id' => [
            'payapi/Index/wechat_qrcode',
            ['method' => 'get']
        ],
        'alipay_account/:id' => [
            'payapi/Index/alipay_account',
            ['method' => 'get']
        ],
        'alipay_once/:id' => [
            'payapi/Index/alipay_once',
            ['method' => 'get']
        ],
        'alipay_qrcode/:id' => [
            'payapi/Index/alipay_qrcode',
            ['method' => 'get']
        ],
        'bank_card/:id' => [
            'payapi/Index/bank_card',
            ['method' => 'get']
        ],
        'createQrCodeIndex' => [
            'payapi/Index/createQrCodeIndex',
            ['method' => 'get']
        ],
        'createQrCode' => [
            'payapi/Index/createQrCode',
            ['method' => 'post']
        ],
        'select' => [
            'payapi/Index/select',
            ['method' => 'post']
        ],
        'agentTest' => [
            'payapi/Index/agentTest',
            ['method' => 'post']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('BuildToken', [
        'getAccessToken'        => [
            'payapi/BuildToken/getAccessToken',
            ['method' => 'post']
        ],
        'getAuthToken' => [
            'payapi/BuildToken/getAuthToken',
            ['method' => 'get']
        ],
        'buildAccessToken' => [
            'payapi/BuildToken/buildAccessToken',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);

    //新版建单
    Route::group('CreateOrder', [
        'index' => [
            'payapi/CreateOrder/index',
            ['method' => 'get']
        ],
        'createOrder' => [
            'payapi/CreateOrder/createOrder',
            ['method' => 'post']
        ],
        'api' => [
            'payapi/CreateOrder/api',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);

    Route::group('AppDownload', [
        'index'  => [
            'payapi/AppDownload/index',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Withdraw', [
        'withdrawAudit' => [
            'payapi/Withdraw/withdrawAudit',
            ['method' => 'post']
        ],
        //获取商户下发余额
        'getBalance' => [
            'payapi/Withdraw/getBalance',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);
    //Route::miss('payapi/Miss/index');
});
