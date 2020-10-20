<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19 0019
 * Time: 15:08
 */

namespace app\model;


class MerchantCallbakLog extends Base
{

    protected $name = 'merchant_callbak_log';

    public function merchant()
    {
        return $this->belongsTo('Merchant','merchant_id','id');
    }

    public function createData($data)
    {

    }
}