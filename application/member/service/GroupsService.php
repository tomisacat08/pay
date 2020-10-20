<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   12/23 023 01:00
 */

namespace app\member\service;


use app\model\MemberWechat;

class GroupsService
{
    public function getGroupList($where,int $offset = 0,int $length = 20)
    {
        $model = MemberWechat::where($where);

        $list = $model->limit($offset,$length)
                      ->order('create_time','desc')
                      ->select();
        if(empty($list)){
            return [];
        }
        $data = [];
        foreach($list as $item){
            $row['id'] = $item->id;
            $row['title'] = $item->title;
            $row['create_time'] = $item->create_time;
            $row['status'] = $item->status;
            $row['desc'] = $item->desc;
            $data[] = $row;
        }
        return $data;
    }

    public function edit($memberId,$id,$title=null,$desc=null)
    {
        $data = [];
        if(!is_null($title)){
            $data['title'] = $title;
        }
        if(!is_null($desc)){
            $data['desc'] = $desc;
        }
        MemberWechat::where(['id'=>$id,'member_id'=>$memberId])->update($data);
        return true;
    }
}