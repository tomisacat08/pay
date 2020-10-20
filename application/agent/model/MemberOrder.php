<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/18 0018
 * Time: 14:05
 */

namespace app\agent\model;

use app\model\MemberOrder as MemberOrderModel;

class MemberOrder extends MemberOrderModel
{
    /**
     * @param $value
     * @return array
     */
    public function getIsClearAttr($value)
    {
        $text = [1=>'已结算',2=>'未结算'];
        return ['text'=>$text[$value],'value'=>$value];
    }


    /**
     * 根据会员ID 统计成交金额
     * @param string $member_ids '1,2,3' 或 [1,2,3]
     * @return float|int
     */
    public static function cumulativeMoney($member_ids)
    {
        $model = new static();
        $money = $model->whereIn('member_id',$member_ids)->sum('money');
        return $money;
    }
}