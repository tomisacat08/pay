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
class Merchant extends Validate
{
    protected $rule = [
        'nickname' => 'require|unique:merchant|max:25',
        'password' => 'require',
        'pay_password' => 'require',
        'mobile' => 'require|unique:merchant|checkMobile',
        'intermediary_mobile' => 'checkMobile',
        'poundage_ratio' => 'require|checkRatio',
        'order_scope' => 'require|checkScope',
    ];

    protected $message = [
        'nickname.require' => '昵称必填',
        'nickname.unique' => '昵称已存在',
        'nickname.max' => '昵称不得大于25位',
        'password.require' => '密码必填',
        'pay_password.require' => '支付密码必填',
        'mobile.require' => '手机号必填',
        'mobile.unique' => '手机号已经注册',
        'mobile.checkMobile' => '商户手机号格式错误',
        'intermediary_mobile.checkMobile' => '商户代理手机号格式错误',
        'poundage_ratio.require' => '手续费比例必填',
        'order_scope.require' => '请设置下单金额范围',
        'order_scope.checkScope' => '下单金额范围格式不正确',
    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['poundage_ratio','order_scope'],
        'add'  =>  ['nickname','password','pay_password','mobile','poundage_ratio','order_scope','intermediary_mobile']
    ];

    // 自定义验证规则
    protected function checkMobile($value, $rule, $data,$field)
    {
        $result = check_mobile($value);
        return $result ? true : false;
    }
    protected function checkRatio($value, $rule, $data)
    {
        $merchant_ratio = config('merchant_ratio', '0,100');
        $merchant_ratio = explode(',',$merchant_ratio);
        $min = $merchant_ratio[0];
        $max = $merchant_ratio[1];
        if($value<$min || $value>$max){
            return '手续费比例范围只是在'.$min.'%~'.$max.'%之间';
        }else{
            return true;
        }


    }
    protected function checkScope($value, $rule, $data)
    {
        $arr = explode(',',$value);
        if(count($arr) !=2 || !is_numeric($arr[0]) || !is_numeric($arr[1]) || $arr[0]>$arr[1]){
            return false;
        }
        return true;


    }
}
