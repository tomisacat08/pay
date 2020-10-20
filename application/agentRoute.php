<?php

use think\Route;

$afterBehavior = [
    '\app\agent\behavior\ApiAuth',
    '\app\agent\behavior\ApiPermission',
    '\app\agent\behavior\AgentLog'
];

Route::group('agent', function () use ($afterBehavior) {
    //一些带有特殊参数的路由写到这里
    Route::rule([
        'Login/index' => [
            'agent/Login/index',
            ['method' => 'post']
        ],
        'Index/upload' => [
            'agent/Index/upload',
            [
                'method' => 'post',
                'after_behavior' => [
                    '\app\agent\behavior\ApiAuth',
                    '\app\agent\behavior\AgentLog'
                ]
            ]
        ],
        'Index/getImgUrlById' => [
            'agent/Index/getImgUrlById',
            ['method' => 'get']
        ],
        'Login/logout' => [//路由规则
            'agent/Login/logout',//路由地址 模块/控制器/方法 检测匹配后台菜单 url 检验
            [
                'method' => 'get',
                'after_behavior' => [
                    '\app\agent\behavior\ApiAuth',
                    '\app\agent\behavior\AgentLog'
                ]
            ]
        ]
    ]);

    //大部分控制器的路由都以分组的形式写到这里
    /*Route::group('Menu', [
        'index' => [
            'agent/Menu/index',
            ['method' => 'get']
        ],
        'changeStatus' => [
            'agent/Menu/changeStatus',
            ['method' => 'get']
        ],
        'add' => [
            'agent/Menu/add',
            ['method' => 'post']
        ],
        'edit' => [
            'agent/Menu/edit',
            ['method' => 'post']
        ],
        'del' => [
            'agent/Menu/del',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);*/

    Route::group('Agent', [
        'getInfo' => [
            'agent/Agent/getInfo',
            ['method' => 'post']
        ],
        'info' => [
            'agent/Agent/info',
            ['method' => 'get']
        ],
        'own' => [
            'agent/Agent/own',
            ['method' => 'post']
        ],
        'index' => [
            'agent/Agent/index',
            ['method' => 'get']
        ],
        'getUsers' => [
            'agent/Agent/getUsers',
            ['method' => 'get']
        ],
        'add' => [
            'agent/Agent/add',
            ['method' => 'post']
        ],
        'edit' => [
            'agent/Agent/edit',
            ['method' => 'post']
        ],
        'changeStatus' => [
            'agent/Agent/changeStatus',
            ['method' => 'post']
        ],
        'del' => [
            'agent/Agent/del',
            ['method' => 'post']
        ],
        'paypwd' => [
            'agent/Agent/paypwd',
            ['method' => 'post']
        ],
        'bankcard' => [
            'agent/Agent/bankcard',
            ['method' => 'get']
        ],
        'addCard' => [
            'agent/Agent/addCard',
            ['method' => 'post']
        ],
        'editCard' => [
            'agent/Agent/editCard',
            ['method' => 'post']
        ],
        'indexCard' => [
            'agent/Agent/indexCard',
            ['method' => 'get']
        ],
        'delCard' => [
            'agent/Agent/delCard',
            ['method' => 'post']
        ],
        'statusCard' => [
            'agent/Agent/statusCard',
            ['method' => 'post']
        ],
        'getGoogleQrcode'          => [
            'agent/Agent/getGoogleQrcode',
            ['method' => 'post']
        ],
        'addGoogleAuth'          => [
            'agent/Agent/addGoogleAuth',
            ['method' => 'post']
        ],


    ], ['after_behavior' => $afterBehavior]);

    Route::group('Member', [
        'index' => [
            'agent/Member/index',
            ['method' => 'get']
        ],
        'group' => [
            'agent/Member/group',
            ['method' => 'get']
        ],
        'getAllGroup' => [
            'agent/Member/getAllGroup',
            ['method' => 'get']
        ],
        'groupMember' => [
            'agent/Member/groupMember',
            ['method' => 'get']
        ],
        'delMember' => [
            'agent/Member/delMember',
            ['method' => 'post']
        ],
        'delGroup' => [
            'agent/Member/delGroup',
            ['method' => 'get']
        ],
        'addGroup' => [
            'agent/Member/addGroup',
            ['method' => 'post']
        ],
        'editGroup' => [
            'agent/Member/editGroup',
            ['method' => 'post']
        ],
        'changeStatusGroup' => [
            'agent/Member/changeStatusGroup',
            ['method' => 'post']
        ],
        'add' => [
            'agent/Member/add',
            ['method' => 'post']
        ],
        'edit' => [
            'agent/Member/edit',
            ['method' => 'post']
        ],
        'del' => [
            'agent/Member/del',
            ['method' => 'post']
        ],
        'changeStatus' => [
            'agent/Member/changeStatus',
            ['method' => 'get']
        ],
        'receipt' => [
            'agent/Member/receipt',
            ['method' => 'get']
        ],
        'refundRecord' => [
            'agent/Member/refundRecord',
            ['method' => 'get']
        ],
        'settlementRecord' => [
            'agent/Member/settlementRecord',
            ['method' => 'get']
        ],


    ], ['after_behavior' => $afterBehavior]);

    Route::group('Transaction', [
        'index' => [
            'agent/Transaction/index',
            ['method' => 'get']
        ],
        'appoint' => [
            'agent/Transaction/appoint',
            ['method' => 'get']
        ],
        'replacement' => [
            'agent/Transaction/replacement',
            ['method' => 'get']
        ],
        'confirmReceipt' => [
            'agent/Transaction/confirmReceipt',
            ['method' => 'post']
        ],
        
        'reconcile' => [
            'agent/Transaction/reconcile',
            ['method' => 'get']
        ],

        'confirmRefund' => [
            'agent/Transaction/confirmRefund',
            ['method' => 'post']
        ],
        'oneKeyConfirm' => [
            'agent/Transaction/oneKeyConfirm',
            ['method' => 'post']
        ],
        'exportTemplate' => [
            'agent/Transaction/exportTemplate',
            ['method' => 'get']
        ],
        'importExcel' => [
            'agent/Transaction/importExcel',
            ['method' => 'post']
        ],
        'accountLog' => [
            'agent/Transaction/accountLog',
            ['method' => 'get']
        ],

    ], ['after_behavior' => $afterBehavior]);

    Route::group('Balance', [
        'index' => [
            'agent/Balance/index',
            ['method' => 'get']
        ],
        'task' => [
            'agent/Balance/task',
            ['method' => 'get']
        ],
        'myOrder' => [
            'agent/Balance/myOrder',
            ['method' => 'get']
        ],

        'statistics' => [
            'agent/Balance/statistics',
            ['method' => 'get']
        ],
        'allotInfo' => [
            'agent/Balance/allotInfo',
            ['method' => 'get']
        ],
        'allot' => [
            'agent/Balance/allot',
            ['method' => 'post']
        ],
        'proof' => [
            'agent/Balance/proof',
            ['method' => 'post']
        ],
        'proofSave' => [
            'agent/Balance/proofSave',
            ['method' => 'post']
        ],
        'allotLog' => [
            'agent/Balance/allotLog',
            ['method' => 'get']
        ],

    ], ['after_behavior' => $afterBehavior]);

    Route::group('Auth', [
        'index' => [
            'agent/Auth/index',
            ['method' => 'get']
        ],
        'changeStatus' => [
            'agent/Auth/changeStatus',
            ['method' => 'get']
        ],
        'add' => [
            'agent/Auth/add',
            ['method' => 'post']
        ],
        'delMember' => [
            'agent/Auth/delMember',
            ['method' => 'get']
        ],
        'edit' => [
            'agent/Auth/edit',
            ['method' => 'post']
        ],
        'del' => [
            'agent/Auth/del',
            ['method' => 'get']
        ],
        'getGroups' => [
            'agent/Auth/getGroups',
            ['method' => 'get']
        ],
        'getRuleList' => [
            'agent/Auth/getRuleList',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);


    Route::group('Log', [
        'index' => [
            'agent/Log/index',
            ['method' => 'get']
        ],
        'memberLog' => [
            'agent/Log/memberLog',
            ['method' => 'get']
        ],
        'agentLog' => [
            'agent/Log/agentLog',
            ['method' => 'get']
        ],
        'del' => [
            'agent/Log/del',
            ['method' => 'get']
        ]
    ], ['after_behavior' => $afterBehavior]);

    Route::miss('agent/Miss/index');
});
