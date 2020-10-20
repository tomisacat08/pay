<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   2019/3/30 18:28
 */

namespace app\api\swoole;


use app\api\service\AppApiService;
use app\api\service\QrCodeService;
use app\model\Agent;
use app\model\Config;
use app\model\Member;
use app\model\MemberImages;
use app\model\MemberWechat;
use app\model\MerchantOrder;
use app\util\lock\Lock;
use app\util\ReturnCode;

class SendOrderService
{
    public $payService;
    public $swooleService;
    public $memberId;
    public $qrcodeId;
    public $memberFd;
    public $memberInfo;
    public $orderFd;
    public $orderId;
    public $orderInfo;
    public $bucketId;
    public $qrcodeListKey;
    public $lock;

    public $stopRankTimes = 20;//授信额度不足时,尝试派单最大次数
    public $hasRelease = false;//是否释放


    public function __construct( $bucketId,$channel = 'alipay_qrcode' )
    {
//        $this->lock = new Lock('cache',['namespace'=>'swooleSend','single'=>true]);
//        $this->lock = new Lock('file',['dir'=>'swooleSend','single'=>true]);
//        $this->lock->get('sendLock_'.$bucketId);
        $this->lock = new Lock('redis',['namespace'=>'swooleSend']);
        $this->lock->get('sendLock_'.$bucketId);

        $this->payService     = PayService::getInstance();
        $this->swooleService  = SwooleService::getInstance();
        $this->bucketId       = $bucketId;
        $this->qrcodeListKey  = $this->payService::getQrcodeListKey($bucketId,$channel);
    }


    public function release()
    {
        if($this->hasRelease){
            return false;
        }
        $this->lock->release('sendLock_'.$this->bucketId);

        $this->hasRelease= true;
        //$this->refreshRank();//暂停广播
    }

    /**
     * 匹配会员二维码
     * @param $orderInfo
     * @param $fd
     * @return bool
     */
    public function matchQrcode( $orderInfo, $fd )
    {
        $now = time();
        //获取商家,句柄
        $this->orderInfo = $orderInfo;
        $this->orderId = $orderInfo->id;
        $this->orderFd = $fd;

        do{
            //匹配常规队列
            $getQrcode = $this->getQrcode();
            //常规未匹配到 - 尝试清空订单队列数据
            if(!$getQrcode){
                $return = false;
                break;
            }

            $this->orderInfo->member_id = $this->memberId;
            $this->orderInfo->member_group_id = $this->memberInfo->group_id;
            $this->orderInfo->agent_id = $this->memberInfo->agent_id;
            $this->orderInfo->match_time = $now;
            $this->orderInfo->save();


            $this->remQrcode();
            $this->memberInfo->save();

            //特殊推送 - 自动配图模式
            $return = $this->matchImg($this->memberInfo,$this->orderInfo);

        }while(false);

        return $return;

        /*//正常推送 2019年11月27日10:52:04 废除手动模式传码
        //向商家推送已匹配到会员
        RedisService::hDel(PayService::$memberHashKey,$this->memberId);
        $this->swooleService->push($this->orderFd,['OP'=>'matchMember','id'=>$this->orderId]);

        //向会员推送订单
        $this->swooleService->push($this->memberFd,['OP'=>'sendOrder','id'=>$this->orderId,'get_money'=>$this->orderInfo->get_money,'type'=>'waitUplodImg']);

        //定时器 -设置超时时间,如果未上传图片, 推送已超时
        SwooleAfterService::getInstance(['timer','checkUploadImgTimeOut',[$this->orderId]]);*/

    }

    private function getQrcode()
    {
        //获取二维码列表
        $qrcodeList = RedisService::zRange($this->qrcodeListKey,0,-1,true);
        if(empty($qrcodeList)){
            return false;
        }

        //取出所有排队二维码,轮询
        foreach($qrcodeList as $id=>$score){
            $this->qrcodeId = $id;
            $imgInfo = MemberImages::field('member_id')->find($id);
            $this->memberId = $imgInfo->member_id;
            $this->memberInfo = Member::find($this->memberId);
            //获取会员,句柄
            $this->memberFd = $this->payService->getMemberFdById($this->memberId);

            //离线,丢出排队行列
            if(!$this->memberFd){
                $offlineKey = PayService::getQrcodeOfflineHashKey($this->memberId);
                RedisService::hSet($offlineKey,$this->qrcodeId,$score);
                $this->remQrcode();
                $this->memberInfo->save();
                continue;
            }


            //接单权限检查 - 请出队列,跳过
            if( $this->memberInfo->is_pass == 2 ){
                $this->remQrcode();
                $this->memberInfo->save();
                $this->swooleService->push($this->memberFd,['OP'=>'stopRank','msg'=>'接单通道关闭!']);
                continue;
            }

            //代理通道接单权限检查 - 请出队列,跳过
            $agentInfo = Agent::find($this->memberInfo->agent_id);
            if( $agentInfo->type == 2 ){
                $this->remQrcode();
                $this->memberInfo->save();
                $this->swooleService->push($this->memberFd,['OP'=>'stopRank','msg'=>'代理接单通道关闭!']);
                continue;
            }

            $checkUsable = $this->checkUsable($this->memberInfo,$agentInfo,500);

            switch ($checkUsable){
                case -1:
                    $agentInfo->is_pass = 2;//代理接单通道关闭
                    $agentInfo->save();
                    $this->remQrcode();
                    $this->memberInfo->is_receipt = 2;//置为未接单状态
                    $this->memberInfo->save();
                    $this->swooleService->push($this->memberFd,['OP'=>'stopRank','msg'=>'代理授信额度不足,提醒恢复额度!']);
                    continue;
                case -2:
                    $this->remQrcode();
                    $this->memberInfo->is_pass = 2;//接单通道关闭
                    $this->memberInfo->save();
                    $this->swooleService->push($this->memberFd,['OP'=>'stopRank','msg'=>'会员可用额度不足,请联系代理补足额度!']);
                    continue;
            }
            //验证代理授信额度

            /*$imgId = $groupInfo->auto_qrcode_img_id;
            if(!empty($imgId)){
                //获取当前激活的码,是否在最近 [1分半内] 接过同金额单
                $last = MerchantOrder::where('member_id',$this->memberId)
                                     ->where('get_money',$this->orderInfo->get_money)
                                     ->where('create_time','>',time()-90)
                                     ->where('get_money_qrcode_img_id',$imgId)
                                     ->order('create_time','desc')
                                     ->find();

                if( $last ){
                    continue;
                }
            }*/

            $checkUsableMoney = $this->checkUsable($this->memberInfo,$agentInfo,$this->orderInfo->get_money);

            //多次指派未成功
            //授信额度检查 - 跳过,请出队列
            if( $checkUsableMoney < 0 ){ //根据实际授信额度判断
                $n = RedisService::hGet(PayService::$qrcodeHashKey,$this->qrcodeId);
                if(!empty($n) && $n > $this->stopRankTimes){
                    //超过尝试派单次数, 禁用并不允许派单
                    //清空计数次数
                    RedisService::hDel(PayService::$qrcodeHashKey,$this->qrcodeId);
                    $this->remQrcode();
                    $this->swooleService->push($this->memberFd,['OP'=>'stopRank','msg'=>'尝试派单次数:['.$this->stopRankTimes.']未派出,请确保会员和代理额度充足,以免错失大单!']);
                    continue;
                }

                //计数+1
                RedisService::hIncrBy(PayService::$qrcodeHashKey,$this->qrcodeId,1);
                continue;
            }

            return true;
        }

        return false;
    }

    public function checkUsable($memberInfo,$agentInfo,$money = 500)
    {
        //验证代理授信额度
        if( $agentInfo->usable_limit < $money ){
            return -1;
        }

        //验证会员授信额度
        if( $memberInfo->usable_limit < $money ) {
           return -2;
        }

        return 1;
    }

    /**
     * 刷新rank排名
     * @author
     * @date   2019/04/17 0017 11:55
     */
    private function refreshRank()
    {
        //获取所有排队人员
        $list = RedisService::zRange($this->qrcodeListKey,0,-1);
        $rank = 0;
        //广播变更排名
        foreach($list as $qrcodeId){
            $memberInfo = MemberImages::field('member_id')->find($qrcodeId);
            $fd = $this->payService->getMemberFdById($memberInfo->member_id);
            if(!$fd){
                continue;
            }
            //向会员推送当前排名变更
            $this->swooleService->push($fd,['OP'=>'memberRank','rank'=>$rank]);
            $rank++;
        }
    }


    private function remQrcode()
    {
        //清出队列
        RedisService::zRem($this->qrcodeListKey,$this->qrcodeId);

        $config = config('allow_more_qrcode_get_order');
        if($config){
            $count = MemberWechat::where('member_id',$this->memberId)->where('status',1)->count();
            if(!empty($count) && $count == 1){
                $this->memberInfo->is_receipt = 2;//置为未接单状态
                $this->swooleService->push($this->memberFd,['OP'=>'stopRank','msg'=>'订单处理后,请重新开工!']);
                return false;
            }
            MemberWechat::where('auto_qrcode_img_id',$this->qrcodeId)->update(['status'=>0]);
            //检查当前会员是否还有码可派,无码, 置为停工
        }else{
            $this->memberInfo->is_receipt = 2;//置为未接单状态
            $this->swooleService->push($this->memberFd,['OP'=>'stopRank','msg'=>'订单处理后,请重新开工!']);
        }

    }



    /**
     * 获取匹配图片
     * @param $memberInfo
     * @param $orderInfo
     * @return bool
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author
     * @date   2019/05/17 0017 19:01
     */
    private function matchImg($memberInfo,$orderInfo)
    {
        $imgInfo = MemberImages::where('id',$this->qrcodeId)
                               ->where('member_id',$memberInfo->id)
                               ->find();

        $apiService = new AppApiService();



        $apiService->updateImgAfter($imgInfo->id,$orderInfo);

        //验证确认收款状态,超时置为 超时状态 status = 3
        SwooleAfterService::getInstance(['timer','checkConfirmDueInTimeOut',[$orderInfo->add_order_id]]);

        //向会员推送自动匹配到单
        RedisService::hDel(PayService::$qrcodeHashKey,$this->memberId);
        $groupInfo = MemberWechat::find($imgInfo->wechat_id);
        $groupName = isset($groupInfo->title) ? $groupInfo->title : '分组已删除';
        $this->swooleService->push($this->memberFd,['OP'=>'pushMsg','msg'=>'新订单提醒 - 账号:[ '.$groupName.' ] | 编号:[ '.$this->orderId.' ] | 金额 :[ '.$this->orderInfo->get_money.' ]']);


        //直接向商家推送付款码
//        $this->swooleService->push($this->orderFd,['OP'=>'pushImg','imgUrl'=>$imgUrl,'orderId'=>$this->orderId,'text'=>$text,'account'=>$account,'real_name'=>$realName]);
        $this->swooleService->push($this->orderFd,['OP'=>'adminPushMsg','imgId'=>$imgInfo->id]);

        return true;

    }
}