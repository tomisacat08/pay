<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19 0019
 * Time: 15:08
 */

namespace app\model;


class MerchantAddOrder extends Base
{

    protected $name = 'merchant_add_order';
    protected $pk = 'id';

    public function merchant()
    {
        return $this->belongsTo('Merchant','merchant_id','id');
    }
}