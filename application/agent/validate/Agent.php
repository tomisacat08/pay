<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/17 0017
 * Time: 15:38
 */

namespace app\agent\validate;


use think\Validate;

/**
 * 会员添加验证器
 * Class Member
 * @package app\agent\validate
 */
class Agent extends Validate
{
    protected $rule = [
        'nickname' => 'require|max:25',
        'password' => 'require|min:6',
        'mobile' => 'require|unique:agent|checkMobile',
        'groupId' => 'require'
    ];

    protected $message = [
        'nickname.require' => '昵称必填',
        'nickname.max' => '昵称不得大于25位',
        'password.require' => '密码必填',
        'password.min' => '密码不得小于6位数',
        'mobile.require' => '手机号必填',
        'mobile.unique' => '手机号已经注册',
        'mobile.checkMobile' => '手机号格式错误',
        'groupId.require' => '请选择权限组，若无权限组请先添加权限组'
    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['nickname','mobile','groupId'],
        'add'  =>  ['nickname','password','mobile','groupId']
    ];

    // 自定义验证规则
    protected function checkMobile($value, $rule, $data)
    {
        $result = check_mobile($value);
        return $result ? true : '手机号格式错误';
    }
}