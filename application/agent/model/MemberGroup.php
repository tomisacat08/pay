<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/16 0016
 * Time: 11:20
 */

namespace app\agent\model;

use app\model\MemberGroup as MemberGroupModel;
use think\Request;


class MemberGroup extends MemberGroupModel
{

    /**
     * 当前组收款员数量 分组列表用
     * @param $value
     * @param $data
     * @return int|string
     * @throws \think\Exception
     */
    public function getMemberCountAttr($value, $data)
    {
        return (new Member())->where(['group_id'=>$data['id'],'agent_id'=>$data['agent_id']])->count('id');
    }

    /**
     * 关联会员模型
     * @return \think\model\relation\HasMany
     */
    public function member()
    {
        return $this->hasMany('Member','group_id','id');
    }


    /**
     * 分组列表
     * @param array  $where
     * @param string $filter
     * @param string $order
     * @param int    $page
     * @param int    $limitRows
     * @return \think\Paginator
     * @throws \think\exception\DbException
     * @date   3/14 014 02:28
     */
    public static function getGroupList($where = [], $filter = "*", $order = 'id desc', $page = 1, $limitRows = 15)
    {
        $model = new static();

        return $model->where($where)
                    ->field($filter)
                    ->order($order)
                    ->paginate($limitRows, false, [
                        'page' => $page,
                        'query' => Request::instance()->query()
                    ]);



    }
}