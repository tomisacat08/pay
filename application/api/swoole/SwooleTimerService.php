<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   2019/06/05 0005 19:12
 */

namespace app\api\swoole;

use app\api\service\MerchantCallbakService;
use app\model\Config;
use app\model\MerchantCallbakLog;
use app\model\MerchantOrder;

class SwooleTimerService
{
    private static $_instance;

    public static function getInstance()
    {
        if(empty(self::$_instance)){
            self::$_instance = new static();
        }
        return self::$_instance;
    }


    public function run($callBackFunc,$params)
    {
        call_user_func_array([$this,$callBackFunc],$params);
    }

    /**
     * 检查确认收款超时
     * @param $addOrderId
     * @author
     * @date   2019/06/06 0006 14:42
     */
    public function checkConfirmDueInTimeOut(int $addOrderId)
    {

        //验证确认收款状态,超时置为 超时状态 status = 3
        $timeOut = Config::where('varname','time_out')->value('value');
        $timeOutMs = !empty($timeOut) ? $timeOut*60000 : 3*60000;
        //乐观锁机制
        swoole_timer_after($timeOutMs, function($addOrderId){
            MerchantOrder::where('add_order_id',$addOrderId )
                         ->where('pay_status',1)
                         ->update(['pay_status'=>3]);
        },$addOrderId);

    }


    /**
     * 检查创建订单超时
     * @param $addOrderId
     * @author
     * @date   2019/06/06 0006 14:42
     */
    public function checkConfirmCreateOrderTimeOut(int $addOrderId)
    {
        //设定1分钟下单超时验证
        $timeOutMs = 60000;
        swoole_timer_after($timeOutMs, function($addOrderId){
            $orderId = MerchantOrder::where('add_order_id',$addOrderId)->value('id');

            if(!$orderId){
                (new MerchantCallbakService())->confirmCreateOrder($addOrderId,false);
            }

        },$addOrderId);
    }

    /**
     * 检查上传图片超时
     * @param $orderId
     * @author
     * @date   2019/06/06 0006 14:42
     */
    public function checkUploadImgTimeOut(int $orderId){
        $timeOut = Config::where('varname','upload_time')->value('value');
        $timeOutMs = !empty($timeOut) ? $timeOut*60000 : 60000;
        swoole_timer_after($timeOutMs, function($orderId){
            //检查图片是否有上传
            $orderModel = MerchantOrder::get($orderId);
            if(empty($orderModel->get_money_qrcode_img_id)){
                $orderModel->status = -1;//传码超时
                $orderModel->save();

                $data = [
                    'OP'=>'uploadImgTimeOut',
                    'id'=>$orderId,
                ];

                $orderFd = PayService::getInstance()->getOrderIdFdById($orderModel->add_order_id);

                SwooleService::getInstance()->push($orderFd,$data);
            }
        },$orderId);
    }

    /**
     * 检查回调是否成功
     * @param int $orderId
     */
    public function checkConfirmDueInCallBakTimeOut(int $orderId){
        $timeOutMs = 30000;//30s
        cache('checkConfirmDueInCallBakTimeOut:'.$orderId,1,180);
        //放入定时器, 跳出同步通知
        swoole_timer_after(5000,function () use($orderId){
            MerchantCallbakService::confirmDueIn($orderId);
        });
        swoole_timer_tick($timeOutMs, function($timerId,$orderId){
            $time = cache('checkConfirmDueInCallBakTimeOut:'.$orderId);
            if(empty($time)){
                swoole_timer_clear($timerId);
                return false;
            }

            //检查是否已经确认成功
            $callBakInfo = MerchantCallbakLog::where(['order_id'=>$orderId,'type'=>2,'is_success'=>1])->find();
            if($callBakInfo){
                swoole_timer_clear($timerId);
                return false;
            }

            if($time < 4){
                cache('checkConfirmDueInCallBakTimeOut:'.$orderId,$time+1,35);
            }else{
                cache('checkConfirmDueInCallBakTimeOut:'.$orderId,null);
            }
            MerchantCallbakService::confirmDueIn($orderId);
        },$orderId);
    }

    /**
     * 检查下发回调是否发送成功
     * @param int $orderId
     */
    public function checkWithdrawCallBak(int $orderId){
        $timeOutMs = 30000;//30s
        cache('checkWithdrawCallBak:'.$orderId,1,180);
        swoole_timer_tick($timeOutMs, function($timerId,$orderId){
            $time = cache('checkWithdrawCallBak:'.$orderId);
            if(empty($time)){
                swoole_timer_clear($timerId);
                return false;
            }

            //检查是否已经确认成功
            $callBakInfo = MerchantCallbakLog::where(['order_id'=>$orderId,'type'=>3,'is_success'=>1])->find();
            if($callBakInfo){
                swoole_timer_clear($timerId);
                return false;
            }

            if($time < 4){
                cache('checkWithdrawCallBak:'.$orderId,$time+1,35);
            }else{
                cache('checkWithdrawCallBak:'.$orderId,null);
            }
            MerchantCallbakService::withdrawCallback($orderId);
        },$orderId);
    }


}