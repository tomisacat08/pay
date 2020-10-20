<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/18 0018
 * Time: 17:51
 */

namespace app\agent\model;

use app\model\Agent as AgentModel;
use think\Request;

class Agent extends AgentModel
{
    protected $hidden = [
        'password',
        'pay_password',
    ];
    /**
     * 最后登录时间
     * @param $value
     * @return false|string
     */
    public function getLastLoginTimeAttr($value)
    {
        return date('Y-m-d H:i:s',$value);
    }

    /**
     * 分组名称
     * @param $value
     * @param $data
     * @return mixed|string
     */
    public function getGroupNameAttr($value,$data)
    {
        $group_name = '';
        $group_id = (new AgentAuthGroupAccess())->where(['uid'=>$data['id']])->value('groupId');
        if(!empty($group_id)){
            $group_name = (new AgentAuthGroup())->where(['id'=>$group_id,'agent_id'=>$data['parent_id']])->value('name');
        }
        return $group_name;
    }

    /**
     * 多对多关联
     * @return \think\model\relation\BelongsToMany
     */
//    public function GroupName()
//    {
//        return $this->belongsToMany("AgentAuthGroup",'AgentAuthGroupAccess','groupId','uid');
//    }

    /**
     * 子账号列表
     * @param array $where
     * @param string $order
     * @param $page
     * @param $limitRows
     * @param $field
     * @return \think\Paginator
     */
    public static function getSubList($where = [], $order = 'id desc', $page = 1, $limitRows = 15, $field = '*')
    {
        $model = new static();
        return $model->where($where)
            ->order($order)
            ->field($field)
            ->paginate($limitRows, false, [
                'page' => $page,
                "query" => Request::instance()->query()
            ]);
    }
}