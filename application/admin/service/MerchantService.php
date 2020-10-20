<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14 0014
 * Time: 10:22
 */

namespace app\admin\service;


use app\model\MerchantOrder;

class MerchantService
{
    /**
     * 获取商户当日实时成功率
     * @param $merchantId
     * @throws \think\Exception
     * @date   2/25 025 01:35
     * @return array
     */
    public function getMerchantTodayTurnoverRate($merchantId)
    {
        $todayTimeStampStart = strtotime(date("Y-m-d",time()));
        $allOrderNum = MerchantOrder::where('merchant_id',$merchantId)
                                    ->where('create_time','>',$todayTimeStampStart)
                                    ->count();
        $successOrderNum = 0;
        $rate = '100%';
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('merchant_id',$merchantId)
                                             ->where('create_time','>',$todayTimeStampStart)
                                             ->where('pay_status',2)
                                             ->count();
            $rateFloat = $successOrderNum/$allOrderNum;
            $rate = (round($rateFloat,2)*100).'%';
        }
        $data = ['allOrderNum'=>$allOrderNum,'successOrderNum'=>$successOrderNum,'rate'=>$rate];
        return $data;
    }

    /**
     * 获取商户总成功率
     * @param $merchantId
     * @throws \think\Exception
     * @date   2/25 025 01:35
     * @return array
     */
    public function getMerchantTurnoverRate($merchantId)
    {
        $allOrderNum = MerchantOrder::where('merchant_id',$merchantId)
                                    ->count();
        $successOrderNum = 0;
        $rate = '100%';
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('merchant_id',$merchantId)
                                             ->where('pay_status',2)
                                             ->count();
            $rateFloat = $successOrderNum/$allOrderNum;
            $rate = (round($rateFloat,2)*100).'%';
        }
        $data = ['allOrderNum'=>$allOrderNum,'successOrderNum'=>$successOrderNum,'rate'=>$rate];
        return $data;
    }


    /**
     * 获取商户 昨日成功率
     *
     * @param $merchantId
     * @return array
     *@throws \think\Exception
     * @date   2/25 025 01:35
     */
    public function getMerchantYesterdayTurnoverRate( $merchantId)
    {
        $yesterdayTimeStampStart = strtotime(date("Y-m-d",time()-86400));
        $yesterdayTimeStampEnd = $yesterdayTimeStampStart+86400;
        $allOrderNum = MerchantOrder::where('merchant_id', $merchantId)
                                    ->where('create_time','>=',$yesterdayTimeStampStart)
                                    ->where('create_time','<',$yesterdayTimeStampEnd)
                                    ->count();
        $successOrderNum = 0;
        $rate = '100%';
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('merchant_id', $merchantId)
                                             ->where('create_time','>=',$yesterdayTimeStampStart)
                                             ->where('create_time','<',$yesterdayTimeStampEnd)
                                             ->where('pay_status',2)
                                             ->count();
            $rateFloat = $successOrderNum/$allOrderNum;
            $rate = (round($rateFloat,2)*100).'%';
        }
        $data = ['allOrderNum'=>$allOrderNum,'successOrderNum'=>$successOrderNum,'rate'=>$rate];
        return $data;
    }
}