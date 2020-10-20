<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/4/2 0002
 * Time: 19:15
 */

namespace app\agent\model;

use app\model\MemberImages as MemberImagesModel;
use think\Request;

class MemberImages extends MemberImagesModel
{
    public function getImgAttr($value)
    {
        if(!empty($value)){
            $domain = Request::instance()->domain();
            return $domain.$value;
        }else {
            return '';
        }
    }
}