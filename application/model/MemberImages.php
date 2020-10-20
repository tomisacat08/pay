<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/18 0018
 * Time: 13:59
 */

namespace app\model;

class MemberImages extends Base
{

    public function wechatInfo()
    {
        return $this->belongsTo('MemberWechat','wechat_id','id');
    }


}