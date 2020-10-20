<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用行为扩展定义文件
return [
    'module_init' => [
        'app\\payapi\\behavior\\Init'
    ],
    // 输出结束
    'response_send'     => [
        'app\\payapi\\behavior\\BuildResponse',
    ],
];
