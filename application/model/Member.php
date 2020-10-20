<?php
/**
 * @since   2017-11-02
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\model;

use app\api\swoole\RedisService;

class Member extends Base
{
    protected $name = 'member';
    protected $pk = 'id';
    public function getType($type){
        $arr = [1=>'普通会员'];
        return $arr[$type];
    }

    //删除其中一条队列
    public function del_queue($member_id){
        $value = RedisService::zRem('swoole_member_list',$member_id);
        return $value;
    }
    //查看队列
    public function look(){
        $list = RedisService::zRange('swoole_member_list',0,-1);
        return $list;
    }
    //获取第一个队列的值
    public function first_queue(){
        $list = RedisService::zRange('swoole_member_list',0,0);
        return $list;
    }

}
