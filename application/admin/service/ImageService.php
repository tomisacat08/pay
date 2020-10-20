<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   3/07 007 04:30
 */

namespace app\admin\service;


use app\model\MemberImages;
use app\model\MerchantOrder;

class ImageService
{
    //计算出码的优先级
    public static function getScore( $imgId )
    {
        // 1.无任何接单记录的, 优先级10000
        // 2. 按成功率, 成功率一样, 接单次数越多,排名靠前
        $allOrderNum = MerchantOrder::where('get_money_qrcode_img_id',$imgId)
                                    ->count();
        $rate = 1;
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('get_money_qrcode_img_id',$imgId)
                                             ->where('pay_status',2)
                                             ->count();
            $rateFloat = $successOrderNum/$allOrderNum;
            $rate = round($rateFloat,2);
        }

        //新码,五单内,成功率低,给与一定的基础值
        $newOtherScore = 0;
        if($allOrderNum < 5){
            $newOtherScore = 5000;
        }

        $score = 10000 * $rate + $allOrderNum + $newOtherScore;
        return $score;
    }


    public static function checkImageEmpty($imgId)
    {
        //允许码空单次数
        $maxCodeEmptyOrderNumConfig = config('code_max_empty_order_num');
        if($maxCodeEmptyOrderNumConfig) {
            $config                         = explode( '/', $maxCodeEmptyOrderNumConfig );
            $maxCodeEmptyOrderNum           = intval( $config[ 0 ] );
            $maxCodeEmptyOrderNumAfterTime  = isset( $config[ 1 ] ) ? intval( $config[ 1 ] ) : 1;
            $maxCodeEmptyOrderNumAfterTime2 = isset( $config[ 2 ] ) ? intval( $config[ 2 ] ) : 1;

            $imgInfo = MemberImages::find($imgId);
            if($imgInfo->channel_type != 1){
                return true;
            }

            //验证连续3笔订单空单,封通道
            $imgLastOrderList = MerchantOrder::field( 'pay_status,create_time' )
                                             ->where( 'pay_qrcode_url', $imgInfo->pay_qrcode_url )
                                             ->order( 'id', 'desc' )
                                             ->limit( 0, $maxCodeEmptyOrderNum + 2 )
                                             ->select();


            $num = 0;
            foreach ( $imgLastOrderList as $item ) {
                if ( $item->pay_status != 2 ) {
                    $num++;
                    continue;
                }
                break;
            }

            //查验超出几单
            $emptyNum = $num - $maxCodeEmptyOrderNum;
            if ( $emptyNum >= 0 ) {
                $imgLastOrder  = reset( $imgLastOrderList );
                $lastOrderTime = $imgLastOrder->getData( 'create_time' );
                //连续N笔空单
                switch ( $emptyNum ) {
                    case 0:// 第一次空单间隔
                        $timeStartCheck = intval( $lastOrderTime ) + $maxCodeEmptyOrderNumAfterTime * 86400;
                        break;
                    case 1:// 第二次空单间隔
                        $timeStartCheck = intval( $lastOrderTime ) + $maxCodeEmptyOrderNumAfterTime2 * 86400;
                        break;
                    default:
                        $timeStartCheck = intval( $lastOrderTime ) + $maxCodeEmptyOrderNumAfterTime2 * 86400;
                }

                if ( time() < $timeStartCheck ) {
                    return false;
                }
            }
        }

        return true;
    }


}