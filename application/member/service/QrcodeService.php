<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   12/23 023 01:00
 */

namespace app\member\service;


use app\model\MemberImages;
use app\model\MemberWechat;

class QrcodeService
{
    public function getQrcodeList($where,int $offset = 0,int $length = 20)
    {
        $model = MemberImages::with(['wechatInfo'])->where($where)->where('delete_at',0);

        $list = $model->limit($offset,$length)
                      ->order('is_used','asc')
                      ->order('create_time','desc')
                      ->select();
        if(empty($list)){
            return [];
        }
        $data = [];
        foreach($list as $item){
            $row['id'] = $item->id;
            $row['img_id'] = $item->id;
            $row['money'] = $item->money;
            $row['create_time'] = $item->create_time;
            $row['is_used'] = $item->is_used;
            $row['group_name'] = $item->wechatInfo['title'];
            $row['group_id'] = $item->wechat_id;
            $data[] = $row;
        }
        return $data;
    }


    public function changeStatus( $memberId,$id,$isUsed = 0 )
    {
        $update = MemberImages::where(['id'=>$id,'member_id'=>$memberId])->update(['is_used'=>$isUsed]);
        return $update;
    }

}