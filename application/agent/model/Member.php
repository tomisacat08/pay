<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/18 0018
 * Time: 13:59
 */

namespace app\agent\model;

use app\model\Member as MemberModel;
use think\Db;
use think\Exception;
use think\Request;

/**
 * 会员模型
 * Class Member
 * @package app\agent\model
 */
class Member extends MemberModel
{

    protected $hidden = [
        'password'
    ];


    /**
     * 接单状态
     * @param $value
     * @return mixed
     */
    public function getIsReceiptAttr($value)
    {
        $text = [1 => '接单中', 2 => '未接单'];
//        return ['text'=> $text[$value],'value'=>$value];
        return $text[$value];
    }


    /**
     * 关联用户组模型 分组名称
     * @return \think\model\relation\BelongsTo
     */
    public function groupNames()
    {
        return $this->belongsTo('MemberGroup','group_id','id')
            ->bind(['group_name'=>'name']);
    }

    /**
     * 关联用户组模型
     * @return \think\model\relation\BelongsTo
     */
    public function memberGroup()
    {
        return $this->belongsTo('MemberGroup','group_id','id');
    }
    /**
     * 关联订单模型
     * @return \think\model\relation\HasMany
     */
    public function memberOrder()
    {
        return $this->hasMany("MerchantOrder", 'member_id', 'id');
    }

    /**
     * 关联代理商
     * @return \think\model\relation\BelongsTo
     */
    public function agent()
    {
        return $this->belongsTo('Agent', 'agent_id', 'id');
        //代理商账号绑定到Member模型属性
//            ->bind(['agent_mobile'=>'mobile']);
    }

    /**
     * 根据条件获取会员id
     * @param $where
     * @return array
     */
    public static function getSubMemberId($where)
    {
        $model = new static();
        return $model->where($where)
            ->column('id');
    }

    /**
     * 获取代理商下某个分组的会员id
     * @param $agent_id
     * @param $group_id
     * @return array
     */
    public static function getMemberId($agent_id, $group_id)
    {
        $model = new static();
        return $model->where(['group_id'=>$group_id,'agent_id'=>$agent_id])->column('id');
    }

    /**
     * 获取代理商下今日新增的会员数
     * @param $agent_id
     * @return int|string
     */
    public static function memberCount($agent_id)
    {
        $model = new static();
        $date = strtotime(date('Y-m-d'), time());
        return $model->where('agent_id', '=', $agent_id)
            ->where('create_time', '>=', $date)
            ->count();
    }

    /**
     * 会员列表
     * @param array $where
     * @param string $filter
     * @param string $order
     * @param int $page
     * @param int $limitRows
     * @return \think\Paginator
     * @throws \think\exception\DbException
     */
    public function getMemberList( $where = [], $filter = "*", $order = 'id desc', $page = 1, $limitRows = 15)
    {
        return $this->with(['groupNames'])
            ->where($where)
            ->field($filter)
            ->order($order)
            ->paginate($limitRows, false, [
                'page' => $page,
                'query' => Request::instance()->query()
            ]);
    }

    /**
     * 新增会员
     * @param $param
     * @return bool
     */
    public function add($param)
    {
        Db::startTrans();
        try {
            $member = self::allowField(true)->create($param);
            Db::commit();
            return $member['id'];
        } catch(\Exception $e) {
            Db::rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * 编辑会员
     * @param $param
     * @return bool
     * @throws \think\exception\DbException
     */
    public function edit($param)
    {
        $detail = self::get(['id' => $param['id']]);
        if (empty($detail)) {
            $this->error = '参数错误数据不存在';
            return false;
        }
        /*if((int)$detail->getData('type') != (int)$param['type']){
            $this->error = '会员类型暂时不支持转换';
            return false;
        }*/
        Db::startTrans();
        try {
            $detail->allowField(true)->save($param);
            Db::commit();
            return true;
        } catch(\Exception $e) {
            Db::rollback();
            $this->error = $e->getMessage();
            return false;
        }
    }

}