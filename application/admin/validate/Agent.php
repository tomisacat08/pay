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
class Agent extends Validate
{
    protected $rule = [
        'nickname' => 'require|unique:agent|max:25',
        'password' => 'require',
        'mobile' => 'require|unique:agent|checkMobile',
        'poundage_ratio' => 'require|checkRatio',
        'total_limit' => 'require|number'
    ];

    protected $message = [
        'nickname.require' => '昵称必填',
        'nickname.unique' => '昵称已存在',
        'nickname.max' => '昵称不得大于25位',
        'password.require' => '密码必填',
        'mobile.require' => '手机号必填',
        'mobile.unique' => '手机号已经注册',
        'mobile.checkMobile' => '手机号格式错误',
        'poundage_ratio.require' => '手续费比例必填',
        'total_limit.require' => '总额度必填',
        'total_limit.number' => '总额度必须未数字'
    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['poundage_ratio','total_limit'],
        'add'  =>  ['nickname','password','mobile','poundage_ratio','total_limit']
    ];

    // 自定义验证规则
    protected function checkMobile($value, $rule, $data)
    {
        $result = check_mobile($value);
        return $result ? true : '手机号格式错误';
    }
    protected function checkRatio($value, $rule, $data)
    {
        $agent_ratio = config('agent_ratio', '0,100');
        $agent_ratio = explode(',',$agent_ratio);
        $min = $agent_ratio[0];
        $max = $agent_ratio[1];
        if($value<$min || $value>$max){
            return '手续费比例范围只是在'.$min.'%~'.$max.'%之间';
        }else{
            return true;
        }


    }
}