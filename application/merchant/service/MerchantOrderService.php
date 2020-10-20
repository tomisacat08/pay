<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14 0014
 * Time: 10:22
 */

namespace app\merchant\service;

use app\model\MerchantOrder;

class MerchantOrderService
{
    public static function getOrderList($orderList)
    {
        $listInfo = [];
        foreach ($orderList as $val) {
            $data = [];
            $data['id'] = $val['id'];
            $sn = $val['merchant_order_sn'];
            if(empty($val['merchant_order_sn']) && !empty($val['remark']) ){
                $sn = '补单备注:'. $val['remark'];
            }
            $data['merchant_order_sn'] = $sn;
            $data['start_money'] = $val['start_money'];

            $data['create_time'] = $val['create_time'];
            $data['confirm_time'] = $val['confirm_time'] ? date('Y-m-d H:i:s',$val['confirm_time']) : 'N/A';
            $data['merchant_order_date'] = $val['merchant_order_date'];
            $data['pay_status'] = $val['pay_status'];
            $data['order_status_name'] = $val['pay_status'] == 2 ? '已付款' : '未付款';
            $data['merchant_order_callbak_confirm_duein'] = $val['merchant_order_callbak_confirm_duein'];
            $data['ip'] = $val['ip'];
            $listInfo[] = $data;
        }
        return $listInfo;
    }
}