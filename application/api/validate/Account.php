<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/17 0017
 * Time: 15:38
 */

namespace app\api\validate;


use think\Validate;

/**
 * 会员添加验证器
 * Class Member
 * @package app\admin\validate
 */
class Account extends Validate
{
    protected $rule = [
        'wechatId' => 'require|number',
        'account' => 'require|max:50',
        'real_name' => 'require|max:50',
    ];

    protected $message = [
        'wechatId.require' => '账号分组ID缺失',
        'account.require' => '账号必填',
        'real_name.require' => '验证姓名必填',
    ];

    //验证场景
    protected $scene = [
        'add'  =>  ['wechatId','account','real_name'],
    ];

}
