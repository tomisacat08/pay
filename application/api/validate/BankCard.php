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
class BankCard extends Validate
{
    protected $rule = [
        'wechatId' => 'require|number',
        'bank_card' => 'require|number',
        'bank_name' => 'require|max:50',
        'bank_account' => 'require|max:50',
        'bank_desc' => 'max:50',
    ];

    protected $message = [
        'wechatId.require' => '账号分组ID缺失',
        'bank_card.require' => '银行卡号必填',
        'bank_name.require' => '银行名称必填',
        'bank_account.require' => '开户人姓名必填',
    ];

    //验证场景
    protected $scene = [
        'add'  =>  ['wechatId','bank_card','bank_name','bank_account','bank_desc'],
    ];

}
