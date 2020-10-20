<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14 0014
 * Time: 10:22
 */

namespace app\merchant\service;


use app\model\Merchant;

class AuthService
{
    public static function getUserInfoByApiAuth($apiAuth)
    {

        //截取MD5 32位后的串,作为ID
        $merchantId = substr($apiAuth,32);
        if(empty($merchantId) || !is_numeric($merchantId)){
            return false;
        }

        $userInfo = Merchant::find($merchantId);
        return $userInfo;
    }
}