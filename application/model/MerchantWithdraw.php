<?php
/**
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;


class MerchantWithdraw extends Base
{
    protected $name = 'merchant_withdraw';
    protected $pk = 'id';
    protected $updateTime = false;
    public function getType($type){
        $arr = [1=>'代理商'];
        return $arr[$type];
    }
    public function getStatus($status){
        $arr = [1=>'等待打款',2=>'打款成功',3=>'等待打款被驳回',4=>'打款成功被驳回'];
        return $arr[$status];
    }
}
