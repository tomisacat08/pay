<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21 0021
 * Time: 15:18
 */

namespace app\model;


class SettlementTask extends Base
{
    protected $name = 'settlement_task';
    protected $pk = 'id';
    public function getStatus($type){
        $arr = [1=>'已完成',2=>'未完成',3=>'已完成被驳回', 4=>'未完成被驳回'];
        return $arr[$type];
    }

}
