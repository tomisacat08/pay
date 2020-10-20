<?php
/**
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;


class Merchant extends Base {

    protected $name = 'merchant';
    protected $pk = 'id';

    public function intermediaryInfo()
    {
        return $this->belongsTo('Intermediary','intermediary_id','id')->field('password',true);
    }

    public function bucketInfo()
    {
        return $this->belongsTo('Bucket','bucket_id','id');
    }

}
