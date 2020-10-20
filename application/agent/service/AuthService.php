<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14 0014
 * Time: 10:22
 */

namespace app\agent\service;


use app\model\Agent;

class AuthService
{
    public static function getUserInfoByApiAuth($apiAuth)
    {

        //截取MD5 32位后的串,作为ID
        $agentId = substr($apiAuth,32);
        if(empty($agentId) || !is_numeric($agentId)){
            return false;
        }

        $userInfo = Agent::find($agentId);
        return $userInfo;
    }
}