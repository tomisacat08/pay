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

class MemberService
{
    public static function changeStatus($memberId,$status)
    {
        switch ($status){
            case 1://开启登录
                $res = MemberModel::update([
                    'id' => $memberId,
                    'status' => $status,
                    'member_device_id' => 0,
                ]);
                break;
            case 2://禁用登录
                $res = MemberModel::update([
                    'id' => $memberId,
                    'status' => $status
                ]);
                //并移出redis队列
                $service = new AppApiService();
                $service->logout($memberId);
                break;
            default:
                return abort(500,'参数异常');
        }

        return $res !== false;
    }

    /**
     * 获取会员实时成功率
     * @param $memberId
     * @throws \think\Exception
     * @date   2/25 025 01:35
     * @return array
     */
    public function getMemberTurnoverRate($memberId)
    {
        $allOrderNum = MerchantOrder::where('member_id',$memberId)->count();
        $successOrderNum = 0;
        $rate = '100%';
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('member_id',$memberId)->where('pay_status',2)->count();
            $rateFloat = $successOrderNum/$allOrderNum;
            $rate = (round($rateFloat,2)*100).'%';
        }
        $data = ['allOrderNum'=>$allOrderNum,'successOrderNum'=>$successOrderNum,'rate'=>$rate];
        return $data;
    }

    /**
     * 获取会员当日成功率
     * @param $memberId
     * @throws \think\Exception
     * @date   2/25 025 01:35
     * @return array
     */
    public function getMemberTodayTurnoverRate($memberId)
    {
        $todayTimeStampStart = strtotime(date("Y-m-d",time()));
        $allOrderNum = MerchantOrder::where('member_id',$memberId)
                                    ->where('create_time','>',$todayTimeStampStart)
                                    ->count();
        $successOrderNum = 0;
        $rate = '100%';
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('member_id',$memberId)
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
     * 获取会员昨日成功率
     * @param $memberId
     * @throws \think\Exception
     * @date   2/25 025 01:35
     * @return array
     */
    public function getMemberYesterdayTurnoverRate($memberId)
    {
        $yesterdayTimeStampStart = strtotime(date("Y-m-d",time()-86400));
        $yesterdayTimeStampEnd = $yesterdayTimeStampStart+86400;
        $allOrderNum = MerchantOrder::where('member_id',$memberId)
                                    ->where('create_time','>=',$yesterdayTimeStampStart)
                                    ->where('create_time','<',$yesterdayTimeStampEnd)
                                    ->count();
        $successOrderNum = 0;
        $rate = '100%';
        if($allOrderNum){
            $successOrderNum  = MerchantOrder::where('member_id',$memberId)
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