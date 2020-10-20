<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/5/14 0014
 * Time: 10:22
 */

namespace app\admin\service;


use app\api\service\AppApiService;
use app\model\Member as MemberModel;
use app\model\MerchantOrder;

class AgentService
{
    /**
     * 获取代理当日实时成功率
     * @param $agentId
     * @throws \think\Exception
     * @date   2/25 025 01:35
     * @return array
     */
    public function getAgentTodayTurnoverRate($agentId)
    {
        $todayTimeStampStart = strtotime(date("Y-m-d",time()));
        $allOrderNum = MerchantOrder::where('agent_id',$agentId)
                                    ->where('create_time','>',$todayTimeStampStart)
                                    ->count();
        $successOrderNum = 0;
        $rate = '100%';
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('agent_id',$agentId)
                                             ->where('create_time','>',$todayTimeStampStart)
                                             ->where('pay_status',2)
                                             ->count();
            $rateFloat = $successOrderNum/$allOrderNum;
            $rate = (round($rateFloat,2)*100).'%';
        }
        $data = ['allOrderNum'=>$allOrderNum,'successOrderNum'=>$successOrderNum,'rate'=>$rate];
        return $data;
    }


    public function getAgentTurnoverRate($agentId)
    {
        $allOrderNum = MerchantOrder::where('agent_id',$agentId)
                                    ->count();
        $successOrderNum = 0;
        $rate = '100%';
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('agent_id',$agentId)
                                             ->where('pay_status',2)
                                             ->count();
            $rateFloat = $successOrderNum/$allOrderNum;
            $rate = (round($rateFloat,2)*100).'%';
        }
        $data = ['allOrderNum'=>$allOrderNum,'successOrderNum'=>$successOrderNum,'rate'=>$rate];
        return $data;
    }


    /**
     * 获取代理昨日成功率
     *
     * @param $agentId
     * @return array
     *@throws \think\Exception
     * @date   2/25 025 01:35
     */
    public function getAgentYesterdayTurnoverRate( $agentId)
    {
        $yesterdayTimeStampStart = strtotime(date("Y-m-d",time()-86400));
        $yesterdayTimeStampEnd = $yesterdayTimeStampStart+86400;
        $allOrderNum = MerchantOrder::where('agent_id', $agentId)
                                    ->where('create_time','>=',$yesterdayTimeStampStart)
                                    ->where('create_time','<',$yesterdayTimeStampEnd)
                                    ->count();
        $successOrderNum = 0;
        $rate = '100%';
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('agent_id', $agentId)
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