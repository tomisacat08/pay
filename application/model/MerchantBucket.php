<?php
/**
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;


class MerchantBucket extends Base {

    protected $name = 'merchant_bucket';
    protected $pk = 'id';


    public function bucketInfo()
    {
        return $this->belongsTo('Bucket','bucket_id','id');
    }

    public function merchantInfo()
    {
        return $this->belongsTo('Merchant','merchant_id','id');
    }

}
