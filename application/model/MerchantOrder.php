<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/19 0019
 * Time: 15:08
 */

namespace app\model;


class MerchantOrder extends Base
{

    protected $name = 'merchant_order';
    protected $pk = 'id';

    public function merchant()
    {
        return $this->belongsTo('Merchant','merchant_id','id');
    }

    public function merchantInfo()
    {
        return $this->belongsTo('Merchant','merchant_id','id');
    }

    public function memberInfo()
    {
        return $this->belongsTo('Member','member_id','id');
    }

    public function agentInfo()
    {
        return $this->belongsTo('Agent','agent_id','id');
    }

    /**
     * 订单状态
     * @param $value
     * @param $data
     * @return string
     */
    public function getOrderStatusAttr($value,$data)
    {
        if($data['status'] == 1 && $data['pay_status'] == 1){
            return '匹配中';
        }
        if($data['status'] == -1 && $data['pay_status'] == 1){
            return '传码超时';
        }
        if($data['status'] == 2 && $data['pay_status'] == 1){
            return '未收款';
        }

        if($data['status'] == 2 && $data['pay_status'] == 3){
            return '收款超时';
        }

        if($data['status'] == 3 && $data['pay_status'] == 2){
            return '已收款待返款';
        }

        if($data['status'] == 4 && $data['pay_status'] == 2 && $data['is_clear'] == 2){
            return '待确认返款';
        }
        if($data['status'] == 4 && $data['pay_status'] == 2 && $data['is_clear'] == 1){
            return '已返款';
        }
        return '订单状态异常';
    }

    public function getOrderStatusCAttr($value,$data)
    {
        if($data['status'] == 1 && $data['pay_status'] == 1){
            return ['text'=>'匹配中','value'=>1];
        }
        if($data['status'] == -1 && $data['pay_status'] == 1){
            return ['text'=>'传码超时','value'=>2];
        }
        if($data['status'] == 2 && $data['pay_status'] == 1){
            return ['text'=>'未收款','value'=>3];
        }

        if($data['status'] == 2 && $data['pay_status'] == 3){
            return ['text'=>'收款超时','value'=>4];
        }

        if($data['status'] == 3 && $data['pay_status'] == 2){
            return ['text'=>'已收款待返款','value'=>5];
        }

        if($data['status'] == 4 && $data['pay_status'] == 2 && $data['is_clear'] == 2){
            return ['text'=>'待确认返款','value'=>6];
        }
        if($data['status'] == 4 && $data['pay_status'] == 2 && $data['is_clear'] == 1){
            return ['text'=>'已返款','value'=>7];
        }
        return ['text'=>'订单状态异常','value'=>999];
    }

    public function appendButtonInfo($data,&$arr)
    {
        $arr['button_name'] = '';
        $arr['button_type'] = '';
        $arr['show_button'] = 0;


        do{
            //匹配中
            if($data->status == 1 && $data->pay_status == 1){
                break;
            }
            //传码超时
            if($data->status == -1 && $data->pay_status == 1){
                break;
            }
            //未收款
            if($data->status == 2 && $data->pay_status == 1){
                $arr['button_name'] = '确认收款';
                $arr['button_type'] = 1;
                $arr['show_button'] = 1;
                break;
            }

            //收款超时
            if($data->status == 2 && $data->pay_status == 3){
                $arr['button_name'] = '确认收款';
                $arr['button_type'] = 1;
                $arr['show_button'] = 1;
                break;
            }

            //已收款待返款
            if($data->status == 3 && $data->pay_status == 2){
                $arr['button_name'] = '去返款';
                $arr['button_type'] = 2;
                $arr['show_button'] = 1;
                break;
            }

            //待确认返款
            if($data->status == 4 && $data->pay_status == 2 && $data->is_clear == 2){
                break;
            }

            //已返款
            if($data->status == 4 && $data->pay_status == 2 && $data->is_clear == 1){
                break;
            }
        }while(false);

        if($arr['button_type'] == 1 && $arr['show_button'] == 1){
            $now = time();
            $create_time = $data->getData('create_time');
            if( ($now - $create_time) > 7200 ){
                $arr['show_button'] = 0;
            }
        }

        return $arr;



    }
}