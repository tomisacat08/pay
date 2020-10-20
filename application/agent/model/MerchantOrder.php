<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19 0019
 * Time: 15:12
 */

namespace app\agent\model;

use app\model\MerchantOrder as MerchantOrderModel;
use think\Request;

class MerchantOrder extends MerchantOrderModel
{
    /**
     * 上传二维码时间 格式化
     * @param $value
     * @param $data
     * @return string
     */
    public function getQrTimeAttr($value,$data)
    {
        if(empty($data['upload_time']) || empty($data['match_time'])){
            return 'N/A';
        }
        $time = bcsub($data['upload_time'],$data['match_time'],0);
        return $time.'秒';
    }

    /**
     * 关联商户
     * @return \think\model\relation\BelongsTo
     */
    public function merchant()
    {
        return $this->belongsTo('Merchant', 'merchant_id', 'id')
            ->bind([
                'merchant_uid' => 'uid'//商户编号
            ]);
    }

    /**
     * 收款码
     * @param $value
     * @return string
     */
//    public function getGetMoneyQrcodePicAttr($value)
//    {
//        if(!empty($value)){
//            $domain = Request::instance()->domain();
//            return $domain.$value;
//        }else {
//            return '';
//        }
//    }
    /**
     * 返款凭证
     * @param $value
     * @return string
     */
//    public function getReturnPicAttr($value)
//    {
//        if(!empty($value)){
//            $domain = Request::instance()->domain();
//            return $domain.$value;
//        }else {
//            return '';
//        }
//    }
    /**
     * 关联会员
     * @return \think\model\relation\BelongsTo
     */
    public function member()
    {
        return $this->belongsTo('Member', 'member_id', 'id')
            ->bind([
                'member_mobile' => 'mobile',
                'member_type' => 'type',
                'member_nickname' => 'nickname'
            ]);
    }

    /**
     * 交易管理交易记录 列表
     * @param array $where
     * @param string $order
     * @param string $field
     * @param int $page
     * @param int $limitRows
     * @return \think\Paginator
     */
    public static function getOrderList($where = [], $order = 'id desc', $field = '*', $page = 1, $limitRows = 15)
    {
        $model = new static();
        return $model->with(['member', 'merchant'])
            ->where($where)
            ->order($order)
            ->field($field)
            ->paginate($limitRows, false, [
                'page' => $page,
                'query' => Request::instance()->query()
            ]);
    }

    /**
     * 根据会员ID 统计成交金额
     * @param string $member_ids '1,2,3' 或 [1,2,3]
     * @return float|int
     */
    public static function cumulativeMoney($member_ids)
    {
        if(empty($member_ids)) return '0.00';
        if(is_string($member_ids)){
            $member_ids = explode(',',$member_ids);
        }
        $member_ids = array_filter($member_ids);
        $model = new static();
        $money = $model
            ->where('pay_status','=',2)
            ->whereIn('member_id',$member_ids)
            ->sum('start_money');
        return $money;
    }

    public static function getMoneyByMemberGroupId( int $memberGroupId ,$where = [])
    {
        if(empty($memberGroupId)){
            return '0.00';
        }
        $whereArr = [
            'pay_status'=>2,
        ];

        $where = array_merge($where,$whereArr);

        $model = new static();
        $money = $model
            ->where('member_group_id',$memberGroupId)
            ->where($where)
            ->sum('start_money');
        return $money;
    }

    public static function getReturnMoneyByMemberGroupId( int $memberGroupId ,$where = [])
    {
        if(empty($memberGroupId)){
            return '0.00';
        }
        $whereArr = [
            'pay_status'=>2,
            'is_clear'=>2,
        ];

        $where = array_merge($where,$whereArr);

        $model = new static();
        $money = $model
            ->where('member_group_id',$memberGroupId)
            ->where($where)
            ->sum('return_money');
        return $money;
    }
}