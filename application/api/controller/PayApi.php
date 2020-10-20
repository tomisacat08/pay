<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\api\controller;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\Merchant as MerchantModel;
use app\model\MerchantWithdrawAudit ;
use app\model\MerchantWithdraw ;

class PayApi extends Base{


    /**
     * 商户派单接口生成订单
     * @return array
     * @author
     */
    public function index(){
        dump(1111);die;

        $postData = $this->request->post();

        $data['price'] = $postData['merchant_order_money'];//订单总金额

        $data['merchant_id'] = db('merchant')->where(['uid'=>$postData['pay_memberid']])->value('id');//商户ID

        $data['start_money'] = $postData['merchant_order_money'];//初始金额

        if(config('random_money')){
            $min = explode('~',config('random_money'))[0]*100;
            $max = explode('~',config('random_money'))[1]*100;
            $random_money = rand($min,$max)/100;
            $data['money'] = $postData['merchant_order_money']-$random_money;//随机立减后的金额
        }else{
            $data['money'] = $postData['merchant_order_money'];//订单总金额
        }

        $data['merchant_order_callbak_confirm_duein'] = $postData['merchant_order_callbak_confirm_duein'];//服务端返回地址.（POST返回数据）

        $data['merchant_order_callbak_redirect'] = $postData['merchant_order_callbak_redirect'];//页面跳转返回地址（POST返回数据）

        $data['merchant_order_desc'] = $postData['merchant_order_desc'];//商品描述

        $data['merchant_order_sn'] = $postData['merchant_order_sn'];//商品订单号

        $data['merchant_order_count'] = $postData['merchant_order_count'];//商品数量

        $data['merchant_order_name'] = $postData['merchant_order_name'];//商品名称

        $data['create_time'] = time();//创建时间

        $data['order_sn'] = 'P'.rand_order();//生成支付订单号


    }
}