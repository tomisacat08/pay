<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/29 0029
 * Time: 17:57
 */

namespace app\agent\validate;


use think\Validate;

class BankCard extends Validate
{
    protected $rule = [
        'bank_name' => 'require',
//        'bank_address' => 'require',
//        'card' => 'require|checkCard',
        'card' => 'require|number',
        'name' => 'require',
    ];

    protected $message = [
        'bank_name.require' => '请选择开户行',
//        'bank_address.require' => '开户支行/开户地址必填',
        'card.require' => '卡号必填',
        'card.number' => '卡号格式不正确',
        'name.require' => '持卡人姓名必填',

    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['bank_name','card','name'],
        'add'  =>  ['bank_name','card','name']
    ];

    // 自定义验证规则
    protected function checkCard($value, $rule, $data)
    {
        $result = checkBankCard($value);
        return $result ? true : '卡号格式不正确';
    }
}