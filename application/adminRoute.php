<?php

use think\Route;

$afterBehavior = [
    '\app\admin\behavior\ApiAuth',
    '\app\admin\behavior\ApiPermission',
    '\app\admin\behavior\AdminLog'
];

//默认首页跳转平台
Route::get('',function(){
    return redirect('/system');
});

Route::group('admin', function () use ($afterBehavior) {
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
        ],
        'Index/homePage'        => [
            'admin/Index/homePage',
            ['method' => 'post']
        ],
        'Index/getImgUrlById'        => [
            'admin/Index/getImgUrlById',
            [
                'method'         => 'get',
                'after_behavior' => [
                    '\app\admin\behavior\ApiAuth',
                    '\app\admin\behavior\AdminLog'
                ]
            ]
        ],
        'Crontab/index'  => [
            'admin/Crontab/index',
            ['method' => 'get']
        ],
    ]);
    //大部分控制器的路由都以分组的形式写到这里
    Route::group('Index', [
        'index'        => [
            'admin/Index/index',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Menu', [
        'index'        => [
            'admin/Menu/index',
            ['method' => 'get']
        ],
        'changeStatus' => [
            'admin/Menu/changeStatus',
            ['method' => 'get']
        ],
        'add'          => [
            'admin/Menu/add',
            ['method' => 'post']
        ],
        'edit'         => [
            'admin/Menu/edit',
            ['method' => 'post']
        ],
        'del'          => [
            'admin/Menu/del',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('User', [
        'index'        => [
            'admin/User/index',
            ['method' => 'get']
        ],
        'getUsers'     => [
            'admin/User/getUsers',
            ['method' => 'get']
        ],
        'changeStatus' => [
            'admin/User/changeStatus',
            ['method' => 'get']
        ],
        'add'          => [
            'admin/User/add',
            ['method' => 'post']
        ],
        'own'          => [
            'admin/User/own',
            ['method' => 'post']
        ],
        'edit'         => [
            'admin/User/edit',
            ['method' => 'post']
        ],
        'del'          => [
            'admin/User/del',
            ['method' => 'get']
        ],
        'getGoogleQrcode'          => [
            'admin/User/getGoogleQrcode',
            ['method' => 'post']
        ],
        'addGoogleAuth'          => [
            'admin/User/addGoogleAuth',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Auth', [
        'index'        => [
            'admin/Auth/index',
            ['method' => 'get']
        ],
        'changeStatus' => [
            'admin/Auth/changeStatus',
            ['method' => 'get']
        ],
        'add'          => [
            'admin/Auth/add',
            ['method' => 'post']
        ],
        'delMember'    => [
            'admin/Auth/delMember',
            ['method' => 'get']
        ],
        'edit'         => [
            'admin/Auth/edit',
            ['method' => 'post']
        ],
        'del'          => [
            'admin/Auth/del',
            ['method' => 'get']
        ],
        'getGroups'    => [
            'admin/Auth/getGroups',
            ['method' => 'get']
        ],
        'getRuleList'  => [
            'admin/Auth/getRuleList',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('App', [
        'index'            => [
            'admin/App/index',
            ['method' => 'get']
        ],
        'refreshAppSecret' => [
            'admin/App/refreshAppSecret',
            ['method' => 'get']
        ],
        'changeStatus'     => [
            'admin/App/changeStatus',
            ['method' => 'get']
        ],
        'add'              => [
            'admin/App/add',
            ['method' => 'post']
        ],
        'getAppInfo'       => [
            'admin/App/getAppInfo',
            ['method' => 'get']
        ],
        'edit'             => [
            'admin/App/edit',
            ['method' => 'post']
        ],
        'del'              => [
            'admin/App/del',
            ['method' => 'get']
        ],
        'uploadApp'        => [
            'admin/App/uploadApp',
            ['method' => 'post']
        ],
        'confirmApp'        => [
            'admin/App/confirmApp',
            ['method' => 'post']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('InterfaceList', [
        'index'        => [
            'admin/InterfaceList/index',
            ['method' => 'get']
        ],
        'changeStatus' => [
            'admin/InterfaceList/changeStatus',
            ['method' => 'get']
        ],
        'add'          => [
            'admin/InterfaceList/add',
            ['method' => 'post']
        ],
        'refresh'      => [
            'admin/InterfaceList/refresh',
            ['method' => 'get']
        ],
        'edit'         => [
            'admin/InterfaceList/edit',
            ['method' => 'post']
        ],
        'del'          => [
            'admin/InterfaceList/del',
            ['method' => 'get']
        ],
        'getHash'      => [
            'admin/InterfaceList/getHash',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Fields', [
        'index'    => [
            'admin/Fields/index',
            ['method' => 'get']
        ],
        'request'  => [
            'admin/Fields/request',
            ['method' => 'get']
        ],
        'add'      => [
            'admin/Fields/add',
            ['method' => 'post']
        ],
        'response' => [
            'admin/Fields/response',
            ['method' => 'get']
        ],
        'edit'     => [
            'admin/Fields/edit',
            ['method' => 'post']
        ],
        'del'      => [
            'admin/Fields/del',
            ['method' => 'get']
        ],
        'upload'   => [
            'admin/Fields/upload',
            ['method' => 'post']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('InterfaceGroup', [
        'index'        => [
            'admin/InterfaceGroup/index',
            ['method' => 'get']
        ],
        'getAll'       => [
            'admin/InterfaceGroup/getAll',
            ['method' => 'get']
        ],
        'add'          => [
            'admin/InterfaceGroup/add',
            ['method' => 'post']
        ],
        'changeStatus' => [
            'admin/InterfaceGroup/changeStatus',
            ['method' => 'get']
        ],
        'edit'         => [
            'admin/InterfaceGroup/edit',
            ['method' => 'post']
        ],
        'del'          => [
            'admin/InterfaceGroup/del',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('AppGroup', [
        'index'        => [
            'admin/AppGroup/index',
            ['method' => 'get']
        ],
        'getAll'       => [
            'admin/AppGroup/getAll',
            ['method' => 'get']
        ],
        'add'          => [
            'admin/AppGroup/add',
            ['method' => 'post']
        ],
        'changeStatus' => [
            'admin/AppGroup/changeStatus',
            ['method' => 'get']
        ],
        'edit'         => [
            'admin/AppGroup/edit',
            ['method' => 'post']
        ],
        'del'          => [
            'admin/AppGroup/del',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Log', [
        'index' => [
            'admin/Log/index',
            ['method' => 'get']
        ],
        'member' => [
            'admin/Log/memberLog',
            ['method' => 'get']
        ],
        'agent' => [
            'admin/Log/agentLog',
            ['method' => 'get']
        ],
        'merchant' => [
            'admin/Log/merchantLog',
            ['method' => 'get']
        ],
        'platform' => [
            'admin/Log/platformLog',
            ['method' => 'get']
        ],
        'del'   => [
            'admin/Log/del',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Config', [
        'index' => [
            'admin/Config/index',
            ['method' => 'get']
        ],
        'add' => [
            'admin/Config/add',
            ['method' => 'post']
        ],
        'edit' => [
            'admin/Config/edit',
            ['method' => 'post']
        ],
        'del'   => [
            'admin/Config/del',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Bucket', [
        'index' => [
            'admin/Bucket/index',
            ['method' => 'get']
        ],
        'add' => [
            'admin/Bucket/add',
            ['method' => 'post']
        ],
        'edit' => [
            'admin/Bucket/edit',
            ['method' => 'post']
        ],
        'getMerchantList' => [
            'admin/Bucket/getMerchantList',
            ['method' => 'get']
        ],
        'getAgentList' => [
            'admin/Bucket/getAgentList',
            ['method' => 'get']
        ],
        'changeMerchantBucket' => [
            'admin/Bucket/changeMerchantBucket',
            ['method' => 'post']
        ],
        'changeAgentBucket' => [
            'admin/Bucket/changeAgentBucket',
            ['method' => 'post']
        ],
        /*'del'   => [
            'admin/Bucket/del',
            ['method' => 'get']
        ]*/
    ], ['after_behavior' => $afterBehavior]);

    /**
     * 获取支付渠道信息
     */
    Route::group('Channel', [
        'getChannelList' => [
            'admin/Channel/getChannelList',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);

    Route::group('Agent', [
        'index' => [
            'admin/Agent/index',
            ['method' => 'get']
        ],
        'getAgentInfo' => [
            'admin/Agent/getAgentInfo',
            ['method' => 'get']
        ],
        'add' => [
            'admin/Agent/add',
            ['method' => 'post']
        ],
        'edit' => [
            'admin/Agent/edit',
            ['method' => 'post']
        ],
        'del'   => [
            'admin/Agent/del',
            ['method' => 'get']
        ],
        'changeStatus'   => [
            'admin/Agent/changeStatus',
            ['method' => 'get']
        ],
        'changeType'   => [
            'admin/Agent/changeType',
            ['method' => 'get']
        ],
        'member_index'   => [
            'admin/Agent/member_index',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Member', [
        'index' => [
            'admin/Member/index',
            ['method' => 'get']
        ],
        'add' => [
            'admin/Member/add',
            ['method' => 'post']
        ],
        'edit' => [
            'admin/Member/edit',
            ['method' => 'post']
        ],
        'del'   => [
            'admin/Member/del',
            ['method' => 'get']
        ],
        'changeStatus'   => [
            'admin/Member/changeStatus',
            ['method' => 'get']
        ],
         'changeReceipt'   => [
            'admin/Member/changeReceipt',
            ['method' => 'get']
         ],
         'memberReplacement'   => [
            'admin/Member/memberReplacement',
            ['method' => 'post']
         ],
         'checkQrcode'   => [
            'admin/Member/checkQrcode',
            ['method' => 'get']
         ],
         'delQrcode'   => [
            'admin/Member/delQrcode',
            ['method' => 'get']
         ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Merchant', [
        'index' => [
            'admin/Merchant/index',
            ['method' => 'get']
        ],
        'getMerchantInfo' => [
            'admin/Merchant/getMerchantInfo',
            ['method' => 'get']
        ],
        'add' => [
            'admin/Merchant/add',
            ['method' => 'post']
        ],
        'edit' => [
            'admin/Merchant/edit',
            ['method' => 'post']
        ],
        'del'   => [
            'admin/Merchant/del',
            ['method' => 'get']
        ],
        'changeStatus'   => [
            'admin/Merchant/changeStatus',
            ['method' => 'get']
        ],
        'changeType'   => [
            'admin/Merchant/changeType',
            ['method' => 'get']
        ],
        'checkDispatch'   => [
            'admin/Merchant/checkDispatch',
            ['method' => 'get']
        ],
        'checkWithdraw'   => [
            'admin/Merchant/checkWithdraw',
            ['method' => 'get']
        ],
        'merchantOrderTest'   => [
            'admin/Merchant/merchantOrderTest',
            ['method' => 'post']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Order', [
        'index' => [
            'admin/Order/index',
            ['method' => 'get']
        ],
        'add' => [
            'admin/Order/add',
            ['method' => 'post']
        ],
        'edit' => [
            'admin/Order/edit',
            ['method' => 'post']
        ],
        'del'   => [
            'admin/Order/del',
            ['method' => 'get']
        ],
        'changeStatus'   => [
            'admin/Order/changeStatus',
            ['method' => 'get']
        ],
        'changeType'   => [
            'admin/Order/changeType',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('BankCard', [
        'index' => [
            'admin/BankCard/index',
            ['method' => 'get']
        ],
        'indexBank' => [
            'admin/BankCard/indexBank',
            ['method' => 'get']
        ],
        'payWay' => [
            'admin/BankCard/payWay',
            ['method' => 'get']
        ],
        'addBank' => [
            'admin/BankCard/addBank',
            ['method' => 'post']
        ],
        'editBank' => [
            'admin/BankCard/editBank',
            ['method' => 'post']
        ],
        'delBank'   => [
            'admin/BankCard/delBank',
            ['method' => 'get']
        ],
        'add' => [
            'admin/BankCard/add',
            ['method' => 'post']
        ],
        'edit' => [
            'admin/BankCard/edit',
            ['method' => 'post']
        ],
        'del'   => [
            'admin/BankCard/del',
            ['method' => 'get']
        ],
        'changeStatus'   => [
            'admin/BankCard/changeStatus',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Notice', [
        'index' => [
            'admin/Notice/index',
            ['method' => 'get']
        ],
        'add' => [
            'admin/Notice/add',
            ['method' => 'post']
        ],
        'edit' => [
            'admin/Notice/edit',
            ['method' => 'post']
        ],
        'del'   => [
            'admin/Notice/del',
            ['method' => 'get']
        ],
        'changeStatus'   => [
            'admin/Notice/changeStatus',
            ['method' => 'get']
        ],
        'isTop'   => [
            'admin/Notice/isTop',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('MerchantWithdrawAudit', [
        'index' => [
            'admin/MerchantWithdrawAudit/index',
            ['method' => 'get']
        ],
        'manualInfo' => [
            'admin/MerchantWithdrawAudit/manualInfo',
            ['method' => 'get']
        ],
        'manualAllot' => [
            'admin/MerchantWithdrawAudit/manualAllot',
            ['method' => 'post']
        ],
        'notPass' => [
            'admin/MerchantWithdrawAudit/notPass',
            ['method' => 'get']
        ],
        'autoAllot' => [
            'admin/MerchantWithdrawAudit/autoAllot',
            ['method' => 'get']
        ],
        'viewDetails' => [
            'admin/MerchantWithdrawAudit/viewDetails',
            ['method' => 'get']
        ],
        'withdrawLoglist' => [
            'admin/MerchantWithdrawAudit/withdrawLoglist',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Platform', [
        'index' => [
            'admin/Platform/index',
            ['method' => 'get']
        ],
        'manualInfo' => [
            'admin/Platform/manualInfo',
            ['method' => 'get']
        ],
        'notPass' => [
            'admin/Platform/notPass',
            ['method' => 'get']
        ],
        'autoAllot' => [
            'admin/Platform/autoAllot',
            ['method' => 'post']
        ],
        'manualAllot' => [
            'admin/Platform/manualAllot',
            ['method' => 'post']
        ]
        ,'platformIndex' => [
            'admin/Platform/platformIndex',
            ['method' => 'get']
        ],
        'viewDetails' => [
            'admin/Platform/viewDetails',
            ['method' => 'get']
        ],
        'withdrawLoglist' => [
            'admin/Platform/withdrawLoglist',
            ['method' => 'get']
        ],
        'confirm' => [
            'admin/Platform/confirm',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);
    Route::group('MerchantOrder', [
        'index' => [
            'admin/MerchantOrder/index',
            ['method' => 'get']
        ],
        'refunds' => [
            'admin/MerchantOrder/refunds',
            ['method' => 'get']
        ],
        'supplement' => [
            'admin/MerchantOrder/supplement',
            ['method' => 'get']
        ],
        'tipIndex' => [
            'admin/MerchantOrder/tipIndex',
            ['method' => 'get']
        ],
        'joinTip' => [
            'admin/MerchantOrder/joinTip',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);
    Route::group('Image', [
        'index' => [
            'admin/Image/index',
            ['method' => 'get']
        ],
        'del' => [
            'admin/Image/del',
            ['method' => 'post']
        ],
        'chooseDel' => [
            'admin/Image/chooseDel',
            ['method' => 'post']
        ],
    ], ['after_behavior' => $afterBehavior]);

    Route::group('Transaction', [
        'reconcile' => [
            'admin/Transaction/reconcile',
            ['method' => 'get']
        ],

        'confirmRefund' => [
            'admin/Transaction/confirmRefund',
            ['method' => 'post']
        ],
        'oneKeyConfirm' => [
            'admin/Transaction/oneKeyConfirm',
            ['method' => 'post']
        ],
        'importExcel' => [
            'admin/Transaction/importExcel',
            ['method' => 'post']
        ],
        'exportTemplate' => [
            'admin/Transaction/exportTemplate',
            ['method' => 'get']
        ],

        'accountLog' => [
            'admin/Transaction/accountLog',
            ['method' => 'get']
        ],
        'appoint' => [
            'admin/Transaction/appoint',
            ['method' => 'get']
        ],

    ], ['after_behavior' => $afterBehavior]);

    //中间人
    Route::group('Intermediary', [
        'index' => [
            'admin/Intermediary/index',
            ['method' => 'get']
        ],
        'add' => [
            'admin/Intermediary/add',
            ['method' => 'post']
        ],
        'edit' => [
            'admin/Intermediary/edit',
            ['method' => 'post']
        ],
        'changeStatus' => [
            'admin/Intermediary/changeStatus',
            ['method' => 'get']
        ],
        'withdrawal' => [
            'admin/Intermediary/withdrawal',
            ['method' => 'post']
        ],
        'balanceDetail' => [
            'admin/Intermediary/balanceDetail',
            ['method' => 'get']
        ],
    ], ['after_behavior' => $afterBehavior]);

    Route::miss('admin/Miss/index');
});
