<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21 0021
 * Time: 15:20
 */

namespace app\model;


class PlatformWithdraw extends Base
{
    protected $name = 'platform_withdraw';
    protected $pk = 'id';
    protected $updateTime = false;
    public function getStatus($status){
        $arr = [1=>'等待打款',2=>'打款成功'];
        return $arr[$status];
    }
}