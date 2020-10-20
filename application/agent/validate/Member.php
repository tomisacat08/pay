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
class Member extends Validate
{
    protected $rule = [
        'nickname' => 'require|max:25',
        'password' => 'require',
        'group_id' => 'require|gt:0',
        'mobile' => 'require|unique:member|checkMobile',
        'poundage_ratio' => 'require',
        'total_limit' => 'require'
    ];

    protected $message = [
        'nickname.require' => '昵称必填',
        'group_id.require' => '请选择分组',
        'group_id.gt' => '请选择分组',
        'nickname.max' => '昵称不得大于25位',
        'password.require' => '密码必填',
        'mobile.require' => '手机号必填',
        'mobile.unique' => '手机号已经注册',
        'mobile.checkMobile' => '手机号格式错误',
        'poundage_ratio.require' => '手续费比例必填',
        'total_limit.require' => '总额度必填'
    ];

    //验证场景
    protected $scene = [
        'edit'  =>  ['nickname','mobile','poundage_ratio','total_limit','group_id'],
        'add'  =>  ['nickname','password','mobile','poundage_ratio','total_limit','group_id']
    ];

    // 自定义验证规则
    protected function checkMobile($value, $rule, $data)
    {
        $result = check_mobile($value);
        return $result ? true : '手机号格式错误';
    }
}