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
class MerchantOrder extends Validate
{
    protected $rule = [
        'merchant_order_uid' => 'require|number',
        'merchant_order_channel' => 'require',
        'merchant_order_money' => 'require|number|gt:0|lt:50000',
        'merchant_order_date' => 'require|dateFormat:Y-m-d H:i:s',
        'merchant_order_callbak_confirm_duein' => 'url',
        'merchant_order_callbak_redirect' => 'url',
        'merchant_order_sign' => 'require',
        'merchant_order_sn' => 'require|max:50',
        'merchant_order_name' => 'max:100',
        'merchant_order_count' => 'number|gt:0',
    ];

    protected $message = [
        'merchant_order_uid.require' => 'UID参数缺失',
        'merchant_order_channel.require' => '支付渠道不能为空',
        'merchant_order_money.require' => '支付金额不能为空',
        'merchant_order_money.number' => '支付金额格式不正确',
        'merchant_order_money.gt' => '支付金额要大于0',
        'merchant_order_money.lt' => '支付金额不能大于50000',
        'merchant_order_date.require' => '支付日期不能为空',
        'merchant_order_date.dateFormat' => '支付日期格式不正确，格式为Y-m-d H:i:s',
        'merchant_order_callbak_confirm_duein.url' => '确认收款回调地址格式不正确',
        'merchant_order_callbak_redirect.url' => '确认收款后跳转地址格式不正确',
        'merchant_order_callbak_confirm_create.url' => '确认收款回调地址格式不正确',
        'merchant_order_sign.require' => 'md5签名不能为空',
        'merchant_order_sn.require' => '商户订单号不能为空',
        'merchant_order_count.number' => '商品个数只能为数字',
        'merchant_order_count.gt' => '商品个数要大于0',

    ];

    //验证场景
    protected $scene = [
        'add'  =>  ['merchant_order_uid','merchant_order_money','merchant_order_date','merchant_order_callbak_confirm_duein','merchant_order_callbak_redirect','merchant_order_sign','merchant_order_sn','merchant_order_name','merchant_order_count'],
        'test'  => ['merchant_order_money','merchant_order_callbak_redirect','merchant_order_name'],
    ];

}
