<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   2019/05/24 0024 18:10
 */

namespace app\payapi\service;


use app\api\swoole\SwooleClientService;
use app\model\Merchant;
use app\model\MerchantAddOrder;
use app\util\Strs;

class AddOrderService
{

    public static function createOrder($postData)
    {
        $orderData[ 'start_money' ] = data_get($postData,'merchant_order_money',0);//初始金额
        $orderData[ 'merchant_order_sn' ] = data_get($postData,'merchant_order_sn','');//商品订单
        $orderData[ 'merchant_order_name' ]  = data_get($postData,'merchant_order_name','');//商品名称
        $orderData[ 'merchant_order_money' ]  = data_get($postData,'merchant_order_money',0);//商品名称
        $orderData[ 'merchant_order_count' ]   = data_get($postData,'merchant_order_count',0);//商品数量
        $orderData[ 'merchant_order_date' ]    = data_get($postData,'merchant_order_date');//商品名称
        $orderData[ 'merchant_order_desc' ]  = data_get($postData,'merchant_order_desc','');//商品描述
        $orderData[ 'merchant_order_callbak_confirm_duein' ]    = data_get($postData,'merchant_order_callbak_confirm_duein','');//服务端返回地址
        $orderData[ 'merchant_order_callbak_confirm_create' ]    = data_get($postData,'merchant_order_callbak_confirm_create','');//确认创建
        $orderData[ 'merchant_order_callbak_redirect' ]  = data_get($postData,'merchant_order_callbak_redirect','');//页面跳转返回地址（POST返回数据）
        $orderData[ 'remark' ]  = data_get($postData,'remark','正常订单');//备注信息
        $orderData[ 'status' ]  = data_get($postData,'status',0);//订单状态,0,未创建, 1,创建成功 2,创建失败

        $orderData['from_system'] = data_get($postData,'from_system',1);
        $orderData['from_system_user_id'] = data_get($postData,'from_system_user_id',0);
        $orderData['merchant_order_channel'] = data_get($postData,'merchant_order_channel','alipay_qrcode');
        $orderData['merchant_order_extend'] = data_get($postData,'merchant_order_extend','');//扩展字段


        $now = time();
        $orderData[ 'create_time' ]      = $now;//创建时间
        $orderData[ 'update_time' ]      = $now;//创建时间

        $orderData[ 'merchant_id' ] = $postData['merchant_id'];//商户ID

        $randomConfig = config( 'random_money' );
        $orderData[ 'money' ] = 0;//订单总金额
        $orderData[ 'get_money' ] = $orderData[ 'merchant_order_money' ];//订单总金额
        if ( $randomConfig ) {
            $config = explode( '~', $randomConfig );
            $min = $config[ 0 ] * 100;
            $max = $config[ 1 ] * 100;//20
            //金额小于或等于随机立减的最大金额时不立减
            if ( $orderData[ 'merchant_order_money' ] > $max ) {
                $orderData[ 'money' ] = rand( $min, $max ) / 100;
                $orderData[ 'get_money' ] = $orderData[ 'merchant_order_money' ] - $orderData[ 'money' ];
            }
        }

        $res = MerchantAddOrder::create( $orderData );
        $addOrderId = $res->id;
        return $addOrderId;
    }

    public static function getOrderUrl($addOrderId)
    {
        $id    = encrypts( $addOrderId );
//        $token    = encrypts( $accessToken );

        $addOrderInfo = MerchantAddOrder::find($addOrderId);
        $channelCode = $addOrderInfo->merchant_order_channel;
        $host = env('host','http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"]);
        switch ($channelCode){
            case 'alipay_qrcode':
                $pathUrl = '/payapi/Index/alipay_qrcode/' . $id;
                break;
            case 'alipay_account'://支付宝转账
                $pathUrl = '/payapi/Index/alipay_account/' . $id;
                break;
            case 'wechat_qrcode':
                $pathUrl = '/payapi/Index/wechat_qrcode/' . $id;
                break;
            case 'alipay_once':
                $pathUrl = '/payapi/Index/index/' . $id;
                break;
            case 'alipay_command_key':
                $pathUrl = '/payapi/Index/alipay_command_key/' . $id;
                break;
            case 'alipay_h5':
                $pathUrl = '/payapi/Index/alipay_h5/' . $id;
//                $data = ['qrcode'=>$pathUrl];
//                $headerUrl = 'alipayqr://platformapi/startapp?saId=10000007&'.http_build_query($data);
                break;
            case 'alipay_card':
            case 'wechat_card':
            case 'union_card':
                $pathUrl = '/payapi/Index/bank_card/' . $id;
                break;
            default:
                abort(500, '暂不支持此通道编码');
        }

        //swoole_websocket_server推送
        $client = new SwooleClientService();
        $params = [
            'addOrderId'=>$addOrderId,
        ];
        $package = $client->package('checkCreateOrder',$params);

        $client->push($package);

        $url = $host.$pathUrl;
        return $url;
    }

    public static function getAccessToken( $merchantArr)
    {
        $expires = config('apiAdmin.ACCESS_TOKEN_TIME_OUT');
        $accessToken = cache('MerchantToken_uid:' . $merchantArr[ 'uid']);
        if ($accessToken) {
            cache('MerchantToken:' . $accessToken, $merchantArr,$expires);
            cache('MerchantToken_uid:' . $merchantArr[ 'uid'], $accessToken,$expires);
        }else{
            $accessToken = self::buildAccessToken( $merchantArr[ 'uid'], $merchantArr[ 'apikey']);
            cache('MerchantToken:' . $accessToken, $merchantArr, $expires);
            cache('MerchantToken_uid:' . $merchantArr[ 'uid'], $accessToken, $expires);
        }

        return ['expires_in'=>$expires,'access_token'=>$accessToken];

    }

    /**
     * 计算出唯一的身份令牌
     * @param $appId
     * @param $appSecret
     * @return string
     */
    public static function buildAccessToken($appId, $appSecret) {
        $preStr = $appSecret . $appId . time() . Strs::keyGen();

        return md5($preStr);
    }
}