<?php

use think\Route;

$afterBehavior = [
    '\app\member\behavior\ApiAuth',
   /*'\app\member\behavior\ApiPermission',
    '\app\member\behavior\MerchantLog'*/
];

Route::group('member', function () use ($afterBehavior) {
    //一些带有特殊参数的路由写到这里
    Route::rule([
        'Login/index'  => [
            'member/Login/index',
            ['method' => 'post']
        ],
        'Index/upload' => [
            'member/Index/upload',
            [
                'method'         => 'post',
                'after_behavior' => [
                    '\app\member\behavior\ApiAuth',
                    /*'\app\member\behavior\MerchantLog'*/
                ]
            ]
        ],
        'Login/logout' => [
            'member/Login/logout',
            [
                'method'         => 'get',
                'after_behavior' => [
                    '\app\member\behavior\ApiAuth',
                   /* '\app\member\behavior\MerchantLog'*/
                ]
            ]
        ]
    ]);
    //大部分控制器的路由都以分组的形式写到这里
    Route::group('Index', [
        'index'        => [
            'member/Index/index',
            ['method' => 'post']
        ],
        'getImgUrlById'        => [
            'member/Index/getImgUrlById',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);

    Route::group('MerchantOrder', [
        'index' => [
            'member/MerchantOrder/index',
            ['method' => 'get']
        ],
        'schedulingdetails' => [
            'member/MerchantOrder/schedulingdetails',
            ['method' => 'get']
        ],
        'confirmDueIn' => [
            'member/MerchantOrder/confirmDueIn',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::miss('member/Miss/index');


    Route::group('Groups', [
        'index' => [
            'member/Groups/index',
            ['method' => 'get']
        ],
        'add' => [
            'member/Groups/add',
            ['method' => 'post']
        ],
        'edit' => [
            'member/Groups/edit',
            ['method' => 'post']
        ],
        'del' => [
            'member/Groups/del',
            ['method' => 'get']
        ],
        'changeStatus' => [
            'member/Groups/changeStatus',
            ['method' => 'post']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Qrcode', [
        'index' => [
            'member/Qrcode/index',
            ['method' => 'get']
        ],
        'add' => [
            'member/Qrcode/add',
            ['method' => 'post']
        ],
        'edit' => [
            'member/Qrcode/edit',
            ['method' => 'post']
        ],
        'del' => [
            'member/Qrcode/del',
            ['method' => 'post']
        ],
        'changeStatus' => [
            'member/Qrcode/changeStatus',
            ['method' => 'post']
        ],
        'uploadOnceQrCode' => [
            'member/Qrcode/uploadOnceQrCode',
            ['method' => 'post']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::miss('member/Miss/index');
});
