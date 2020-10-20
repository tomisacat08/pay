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
class Intermediary extends Validate
{
    protected $rule = [
        'nickname' => 'require|unique:intermediary|max:25',
        'password' => 'require',
        'mobile' => 'require|unique:intermediary|checkMobile',
    ];

    protected $message = [
        'nickname.require' => '昵称必填',
        'nickname.unique' => '昵称已存在',
        'nickname.max' => '昵称不得大于25位',
        'password.require' => '密码必填',
        'mobile.require' => '手机号必填',
        'mobile.unique' => '手机号已经注册',
        'mobile.checkMobile' => '手机号格式错误',
    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['nickname'],
        'add'  =>  ['nickname','password','mobile']
    ];

    // 自定义验证规则
    protected function checkMobile($value, $rule, $data)
    {
        $result = check_mobile($value);
        return $result ? true : '手机号格式错误';
    }
}
