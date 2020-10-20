<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14 0014
 * Time: 10:22
 */

namespace app\admin\service;


use app\model\AdminUser;

class AuthService
{
    public static function getUserInfoByApiAuth($apiAuth)
    {
        //截取MD5 32位后的串,作为ID
        $adminId = substr($apiAuth,32);
        if(empty($adminId) || !is_numeric($adminId)){
            return false;
        }

        $userInfo = AdminUser::find($adminId);
        return $userInfo;
    }
}