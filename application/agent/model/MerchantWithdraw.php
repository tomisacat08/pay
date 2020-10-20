<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/21 0021
 * Time: 15:23
 */

namespace app\agent\model;

use app\model\MerchantWithdraw as MerchantWithdrawModel;
use think\Request;

class MerchantWithdraw extends MerchantWithdrawModel
{
    /**
     * 凭证图路径
     * @param $value
     * @return string
     */
    public function getPicAttr($value)
    {
        if(!empty($value)){
            $domain = Request::instance()->domain();
            return $domain.$value;
        }else {
            return '';
        }
    }

    /**
     * @param $value
     * @return false|string
     */
    public function getPayTimeAttr($value)
    {
        if(!empty($value)){
            return date('Y-m-d H:i:s',$value);
        }else {
            return '';
        }
    }

    /**
     * 关联结算任务表 获取商户编号
     * @return \think\model\relation\BelongsTo
     */
    public function settlement()
    {
        return $this->belongsTo('SettlementTask', 'settlement_id', 'id')->bind('pm_uid');
    }

    /**
     * 关联结算人 会员账户
     * @return \think\model\relation\BelongsTo
     */
    public function memberAccount()
    {
        return $this->belongsTo('Member', 'member_id', 'id')->bind(["member_mobile"=>"mobile","member_nickname"=>"nickname"]);
    }

    /**
     * 关联结算人 代理商
     * @return \think\model\relation\BelongsTo
     */
    public function agentAccount()
    {
        return $this->belongsTo('Agent', 'agent_id', 'id')->bind(["agent_mobile"=>"mobile"]);
    }

    /**
     * @param $value
     * @return mixed
     */
//    public function getStatusAttr($value)
//    {
//        $text = [1 => '未打款', 2 => '已打款',3=>'等待打款被驳回',4=>'打款成功被驳回'];
//        return $text[$value];
//    }

    /**
     * 结算订单列表
     * @param array $where
     * @param string $field
     * @param string $order
     * @param int $page
     * @param int $limitRows
     * @return \think\Paginator
     */
    public static function getOrderList($where = [], $field = '*', $order = 'id desc', $page = 1, $limitRows = 15)
    {
        //代理商
        $with = ['settlement', 'agentAccount'];

        $model = new static();
        $list = $model->with($with)
            ->where($where)
            ->field($field)
            ->order($order)
            ->paginate($limitRows, false, [
                'page' => $page,
                'query' => Request::instance()->query()
            ]);
        return $list;
    }
}