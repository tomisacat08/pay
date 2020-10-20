<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/17 0017
 * Time: 15:38
 */

namespace app\payapi\validate;


use think\Validate;

/**
 * 会员添加验证器
 * Class Member
 * @package app\admin\validate
 */
class WithdrawAudit extends Validate
{
    protected $rule = [
        'bank_name' => 'require|max:100',
        'bank_address' => 'require|max:100',
        'card' => 'require|number',
        'name' => 'require|min:1|max:25',

        'uid' => 'require|number',
        'sn' => 'require|max:50',
        'money' => 'require|number|gt:99|lt:500000',
        'callback' => 'require|url',
        'sign' => 'require',
        'remark' => 'max:100',
    ];

    protected $message = [
        'bank_name.require' => '银行名称不能为空',
        'bank_address.require' => '开户地址必填',
        'card.require' => '银行卡号必填',
        'card.number' => '银行卡号必须为数字',
        'name.require' => '开户名必填',
        'bank_name.max' => '银行不得大于100位',
        'bank_address.max' => '银行地址不得大于100位',
        'name.min' => '开户名不得小于1位',
        'name.max' => '开户名不得大于25位',

        'uid.require' => 'UID参数缺失',
        'money.require' => '金额不能为空',
        'money.number' => '金额格式不正确',
        'money.gt' => '金额要大于0',
        'money.lt' => '金额不能大于50000',
        'callback.url' => '回调地址格式不正确',
        'callback.require' => '回调不能为空',
        'sign.require' => 'md5签名不能为空',
        'sn.require' => '商户订单号不能为空',

    ];

    //验证场景
    protected $scene = [
        'add'  =>  ['bank_name','bank_address','card','name','uid','sn','money','callback','sign','remark']
    ];

}
