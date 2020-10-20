<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14 0014
 * Time: 10:22
 */

namespace app\admin\service;

use app\model\MerchantOrder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class MerchantOrderService
{
    public static function getOrderList($orderList)
    {
        $listInfo = [];
        $orderModel = new MerchantOrder();
        foreach ($orderList as $val) {
            $data = [];
            $data['id'] = $val['id'];
            $data['merchantInfo'] = $val['merchant_info']['nickname'].' - '.$val['merchant_info']['uid'];
            $sn = $val['merchant_order_sn'];
            if(empty($val['merchant_order_sn']) && !empty($val['remark']) ){
                $sn = '备注:'. $val['remark'];
            }
            $data['merchant_order_sn'] = $sn;
            $data['tip_order'] = $val['tip_order'];
            $data['ip'] = data_get($val,'ip','N/A');
            $data['start_money'] = $val['start_money'];
            $data['get_money'] = $val['get_money'];
            $memberInfo = 'N/A';
            if(!empty($val['member_info'])){
                $memberInfo = $val['member_info']['nickname'].' - '.$val['member_info']['mobile'];
            }
            $data['memberInfo'] = $memberInfo;

            $agentInfo = 'N/A';
            if(!empty($val['agent_info'])){
                $agentInfo = $val['agent_info']['nickname'].' - '.$val['agent_info']['mobile'];
            }
            $data['agentInfo'] = $agentInfo;

            $confirmInterval = 'N/A';
            if ( $val['confirm_time'] > 0 ) {
                $confirmInterval = secToTime($val['confirm_time'] - $val['upload_time']);
            }
            $data['confirmInterval'] = $confirmInterval;
            $data['create_time'] = $val['create_time'];
            $data['get_money_qrcode_img_id'] = $val['get_money_qrcode_img_id'];
            $data['order_status'] = $orderModel->getOrderStatusCAttr('', $val);
            $data['order_status_name'] = $data['order_status']['text'];
            $data['merchant_order_channel'] = $val['merchant_order_channel'];
            $listInfo[] = $data;
        }
        return $listInfo;
    }
}