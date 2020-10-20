<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   2019/04/18 0018 14:09
 */

namespace app\api\service;



use app\model\Merchant;
use app\model\MerchantAddOrder;
use app\model\MerchantCallbakLog;
use app\model\MerchantOrder;
use app\model\MerchantWithdrawAudit;

class MerchantCallbakService
{

    public static function confirmCreateOrder($addOrderId,$isCreated = true)
    {
        $addOrderInfo = MerchantAddOrder::get($addOrderId);

        $data = [
            'merchant_order_sn'=>$addOrderInfo->merchant_order_sn,
        ];

        $data['sign'] = self::getSign($data,$addOrderInfo->merchant_id);

        //添加成功
        $postData = [
            'code'=>200,
            'msg'=>'success',
            'data'=>$data
        ];

        //添加失败
        if( empty($isCreated) ){
            //超时保存
            $addOrderInfo->status = 2;
            $addOrderInfo->remark = '创建订单超时,页面未打开!';
            $addOrderInfo->update_time = time();
            $addOrderInfo->save();

            $postData = [
                'code'=>500,
                'msg'=>'add order timeout',
                'data'=>$data
            ];
        }

        //未设置回调,直接返回
        if(empty($addOrderInfo->merchant_order_callbak_confirm_create)){
            return false;
        }

        $postReturn = curl_post_json($addOrderInfo->merchant_order_callbak_confirm_create,$postData);

        $isSuccess = 0;
        if( is_string($postReturn) && strtolower($postReturn) == 'success'){
            $isSuccess = 1;
        }else{
            $returnData = json_decode($postReturn,true);
            if($returnData && is_array($returnData) && array_key_exists('code',$returnData) && $returnData['code'] == 200 ){
                $isSuccess = 1;
            }
        }

        //记录日志
        $logData = [
            'order_id' => $addOrderId,
            'merchant_id'=>$addOrderInfo->merchant_id,
            'type'=>1,//确认创建单号成功
            'url'=>$addOrderInfo->merchant_order_callbak_confirm_create,//确认收款回调
            'params'=>json_encode($postData),
            'return' => addslashes(substr($postReturn,0,400)),
            'create_time' => time(),
            'is_success' => $isSuccess,
        ];
        MerchantCallbakLog::create($logData);
    }


    public static function confirmDueIn($orderId)
    {
        $orderInfo = MerchantOrder::get($orderId);

        if($orderInfo->pay_status != 2){
            return false;
        }

        if(empty($orderInfo->merchant_order_callbak_confirm_duein)){
            return false;
        }

        $now = time();

        if( $orderInfo->pay_status != 2 &&  $orderInfo->status != 2){
            return false;
        }

        //已经回调成功,无需再回调
        $callBakInfo = MerchantCallbakLog::where(['order_id'=>$orderId,'type'=>2,'is_success'=>1])->find();
        if($callBakInfo){
            return false;
        }

        $data = [
            'order_id' => $orderId,
            'order_money' => $orderInfo->start_money,
            'merchant_order_sn' => $orderInfo->merchant_order_sn
        ];

        $data['sign'] = self::getSign($data,$orderInfo->merchant_id);

        $postData = [
            'code'=>200,
            'msg'=>'确认收款成功!',
            'data'=>$data
        ];

        $postReturn = curl_post_json($orderInfo->merchant_order_callbak_confirm_duein,$postData);

        $isSuccess = 0;
        if( is_string($postReturn) && strtolower($postReturn) == 'success'){
            $isSuccess = 1;
        }else{
            $returnData = json_decode($postReturn,true);
            if($returnData && is_array($returnData) && array_key_exists('code',$returnData) && $returnData['code'] == 200 ){
                $isSuccess = 1;
            }
        }

        //记录日志
        $logData = [
            'order_id' => $orderId,
            'merchant_id'=>$orderInfo->merchant_id,
            'type'=>2,//确认收款
            'url'=>$orderInfo->merchant_order_callbak_confirm_duein,//确认收款回调
            'params'=>json_encode($postData),
            'return' => addslashes(substr($postReturn,0,400)),
            'create_time' => $now,
            'is_success' => $isSuccess,
        ];
        MerchantCallbakLog::create($logData);

        return $postReturn;
    }

    public static function withdrawCallback($withdrawId)
    {
        $withdrawInfo = MerchantWithdrawAudit::find($withdrawId);

        if(empty($withdrawInfo->callback)){
            return false;
        }

        //已经回调成功,无需再回调
        $callBakInfo = MerchantCallbakLog::where(['order_id'=>$withdrawId,'type'=>3,'is_success'=>1])->find();
        if($callBakInfo){
            return false;
        }

        $data = [
            'money' => $withdrawInfo->money,
            'sn' => $withdrawInfo->withdraw_sn
        ];

        $data['sign'] = self::getSign($data,$withdrawInfo->merchant_id);

        $postData = [
            'code'=>200,
            'msg'=>'打款成功!',
            'data'=>$data
        ];

        $postReturn = curl_post_json($withdrawInfo->callback,$postData);
        $isSuccess = 0;
        if( is_string($postReturn) && strtolower($postReturn) == 'success'){
            $isSuccess = 1;
        }else{
            $returnData = json_decode($postReturn,true);
            if($returnData && is_array($returnData) && array_key_exists('code',$returnData) && $returnData['code'] == 200 ){
                $isSuccess = 1;
            }
        }

        //记录日志
        $logData = [
            'order_id' => $withdrawId,
            'merchant_id'=>$withdrawInfo->merchant_id,
            'type'=>3,//确认下发成功
            'url'=>$withdrawInfo->callback,
            'params'=>json_encode($postData),
            'return' => addslashes(substr($postReturn,0,400)),
            'create_time' => time(),
            'is_success' => $isSuccess,
        ];
        MerchantCallbakLog::create($logData);

        return $postReturn;
    }


    public static function getSign($data,$merchantId,&$md5SignStr = '')
    {
        //获取商户apikey
        $salt = Merchant::where('id',$merchantId)->value('apikey');
        if(empty($salt)){
            abort(500,'获取商户秘钥失败!');
        }

        if(isset($data['sign'])){
            unset($data['sign']);
        }

        if(isset($data['merchant_order_sign'])){
            unset($data['merchant_order_sign']);
        }

        ksort( $data );
        $str = '';
        foreach($data as $key=>$value){
            $str .= $key.'='.$value.'&';
        }
        $md5str = strtoupper($str . "apikey=" . $salt);
        $md5SignStr = strtoupper($str . "apikey=*");
        //签名验证，查询数据是否被篡改
        return md5($md5str);
    }
}