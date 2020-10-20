<?php
/**
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;

class MemberWechat extends Base
{
    protected $name = 'member_wechat';
    protected $pk = 'id';

    public function getUsedWechatByMemberId($memberId)
    {
        $row = $this->where('member_id',$memberId)->where('status',1)->find();
        return $row->id;
    }

}
