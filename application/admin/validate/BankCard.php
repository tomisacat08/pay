<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/17 0017
 * Time: 15:38
 */

namespace app\admin\validate;


use think\Validate;

/**
 * 会员添加验证器
 * Class Member
 * @package app\admin\validate
 */
class BankCard extends Validate
{
    protected $rule = [
        'bank_name' => 'require|max:100',
        'bank_address' => 'require|max:100',
        'card' => 'require|number',
        'name' => 'require|min:1|max:25',
    ];

    protected $message = [
        'bank_name.require' => '支付方式必选',
        'bank_address.require' => '开户地址必填',
        'card.require' => '银行卡号必填',
        'card.number' => '银行卡号必须为数字',
        'name.require' => '开户名必填',
        'bank_name.max' => '银行不得大于100位',
        'bank_address.max' => '银行地址不得大于100位',
        'name.min' => '开户名不得小于1位',
        'name.max' => '开户名不得大于25位',

    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['bank_name','bank_address','card','name'],
        'add'  =>  ['bank_name','bank_address','card','name']
    ];

   /* // 自定义验证规则
    protected  function checkCard($value, $rule, $data){
         return checkBankCard($value);
    }*/
}
