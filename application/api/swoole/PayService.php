<?php
namespace app\api\swoole;

use app\admin\service\ImageService;
use app\api\handle\Swoole;
use app\api\service\MerchantCallbakService;
use app\model\Agent;
use app\model\AgentBucket;
use app\model\Bucket;
use app\model\Config;
use app\model\Member;
use app\model\MemberImages;
use app\model\MemberWechat;
use app\model\Merchant;
use app\model\MerchantAddOrder;
use app\model\MerchantBucket;
use app\model\MerchantOrder;
use app\util\lock\Lock;
use think\Cache;
use think\Db;

class PayService
{

    //通用队列
    public static $qrcodeListKey = 'swoole_qrcode_list';
    //离线数据存储
    public static $qrcodeOfflineHashKeyPre = 'swoole_qrcode_offline_hash:';
    //桶队列前缀
    public static $qrcodeListBucketKeyPre = 'swoole_qrcode_list_';
    //通用订单队列
    public static $orderListKey = 'swoole_order_list';
    //桶订单队列
    public static $orderListBucketKeyPre = 'swoole_order_list_';
    //码尝试派单最大次数
    public static $qrcodeHashKey = 'swoole_qrcode_match_failed_times_hash';

    public        $serverFd;

    public static $_instance;

    public static function getInstance($serverFd = null)
    {
        if(empty(self::$_instance)){
            self::$_instance = new static($serverFd);
        }
        return self::$_instance->init($serverFd);
    }

    public function init($serverFd){
        if($serverFd){
            $this->serverFd = $serverFd;
        }
        return $this;
    }

    public function __construct($serverFd = null)
    {
        $this->init($serverFd);
    }

    public function getUserInfo($fd)
    {
        $info = cache('swoole_online_map:'.$fd);
        return $info;
    }

    public static function getQrcodeListKey( $bucketId,$channel = 'alipay_qrcode' )
    {
        $key = self::$qrcodeListKey.':'.$channel;
        if($bucketId){
            $bucketInfo = Bucket::find($bucketId);
            $channel = $bucketInfo->channel;

            $key = self::$qrcodeListBucketKeyPre.$channel.':'.$bucketId;
        }
        return $key;
    }

    /**
     * 获取掉线用户key
     * @param int $memberId
     * @return string
     * @date   11/25 025 15:16
     */
    public static function getQrcodeOfflineHashKey( $memberId,$channel = 'alipay_qrcode' )
    {
        $key = self::$qrcodeOfflineHashKeyPre.':'.$channel.':'.$memberId;
        return $key;
    }


    public static function getQrcodeListCount( $bucketId = 0, $channel = 'alipay_qrcode')
    {
        $key = self::getQrcodeListKey($bucketId,$channel);
        $memberCount = RedisService::zCount($key,0,time());
        return $memberCount;
    }

    public static function getOrderBucketKey($bucketId,$channel = 'alipay_qrcode')
    {
        $key = self::$orderListKey.':'.$channel;
        if($bucketId){
            $bucketInfo = Bucket::find($bucketId);
            $key = self::$orderListBucketKeyPre.':'.$bucketInfo->channel.':'.$bucketId;
        }
        return $key;
    }

    public function getMemberId($fd){
        $info = $this->getUserInfo($fd);

        if(!$info || $info['type'] != 'member'){
            return false;
        }

        return $info['id'];
    }

    public function getOrderIdFdById( $addOrderId )
    {
        $orderFd = cache('swoole_orderId_online_map:'.$addOrderId);
        if($orderFd){
            $isAlive = Swoole::$server->isEstablished($orderFd);
            if(!$isAlive){
                //手动下下
                if(!Swoole::$server->close($orderFd)){
                    //手动下线失败,手动清理登录状态
                    SwooleLoginService::getInstance()->logout($orderFd);
                }
                return false;
            }
        }
        return $orderFd;
    }

    public function getMemberFdById( $memberId )
    {
        $memberFd = cache('swoole_member_online_map:'.$memberId);
        if($memberFd){
            $isAlive = Swoole::$server->isEstablished($memberFd);
            if(!$isAlive){
                /*if(!Swoole::$server->close($memberFd)){
                    //手动下线失败,手动清理登录状态
                    SwooleLoginService::getInstance()->logout($memberFd);
                }*/
                return false;
            }
        }
        return $memberFd;
    }

    //查询是否已在队列中
    public function getRank( $key, $id )
    {
        return RedisService::zRank($key,$id);
    }

    //查询会员排序
    public function getQrcodeScore( $key, $id)
    {
        return RedisService::zScore($key,$id);
    }

    private function addQueue($agentId,$memberId,array $imgIds)
    {
        $imgList = MemberImages::whereIn('id',$imgIds)->select();
        $memberInfo = Member::find($memberId);
        $agentInfo = Agent::find($agentId);

        // 将所有二维码按类型分组...

        $imgTypeArr = [];
        foreach ($imgList as $imgInfo) {
            $imgTypeArr[$imgInfo->channel_type][] = $imgInfo->id;
        }

        $agentBucketList = AgentBucket::where('agent_id',$memberInfo->agent_id)->select();

        $agentChannelConfig = [];
        foreach($agentBucketList as $agentBucketInfo){
            $agentChannelConfig[$agentBucketInfo->channel] = $agentBucketInfo->bucket_id;
        }

        $channelTypeList = array_keys($imgTypeArr);

        //建立 通道编码-通道type-桶ID  关联数组

        //说明: 商户一个通道只能使用一个桶,费率不同不要一个商户开多个不同通道的桶,
        //  代理一个扫码通道只能使用一个, 不能同时使用,并关联两个不同通道的同类型桶...比如 扫码, 微信扫码和支付宝扫码,不能同时使用...切记!!!
        //   这里使用哪个类型的通道, 由桶关联代理决定...

        //代理...仅支持单通道... 多通道临时解决方案 - 使用标识

        $channelTypeBucket = [];

        //微信
        foreach($channelTypeList as $channelType){
            $channel = $this->getAgentChannelByType($channelType,$agentInfo->channel_type);
            $channelTypeBucket[$channelType] = $channel;
        }

        $min = 1000;

        foreach($imgTypeArr as $channelType => $channelImgIdList){
            //获取离线排队推单的码
            $channel = $channelTypeBucket[$channelType];

            $offlineKey = PayService::getQrcodeOfflineHashKey($memberId,$channel);
            $offlineArr = RedisService::hGetAll($offlineKey);

            $bucketId = isset($agentChannelConfig[$channel]) ? $agentChannelConfig[$channel] : 0;
            $key = self::getQrcodeListKey($bucketId,$channel);

            //更新每个码的优先级
            foreach ($channelImgIdList as $imgId){
                if($offlineArr && array_key_exists($imgId,$offlineArr)){
                    $timestamp = $offlineArr[$imgId];
                    //入队
                    RedisService::zAdd($key,$timestamp,$imgId);
                }

                $rank = $this->getRank($key,$imgId);
                if($rank === false){
                    $timestamp = time();

                    //获取识别优先,插队
                    if($memberInfo->is_vip == 1){
                        $firstList = RedisService::zRange($key,0,2,true);
                        if(count($firstList) == 3){
                            $timestamp = end($firstList) - 1;
                        }
                    }
                    //入队
                    RedisService::zAdd($key,$timestamp,$imgId);
                    $rank = $this->getRank($key,$imgId);
                }

                $min = $rank < $min ? $rank : $min;
            }

            //清空离线排队数据
            RedisService::del($offlineKey);
        }

        return $min;
    }


    private function getAgentChannelByType($channelType,$agentType = 1){
        //支付宝
        if($agentType == 1){
            switch($channelType){
                case 1:
                    $channel = 'alipay_qrcode';
                    break;
                case 2:
                    $channel = 'alipay_account';
                    break;
                case 3:
                    $channel = 'alipay_card';
                    break;
                case 4:
                    $channel = 'mobile';
                    break;
                default:
                    $channel = 'alipay_qrcode';
            }
        }

        //微信
        if($agentType == 2){
            switch($channelType){
                case 1:
                    $channel = 'wechat_qrcode';
                    break;
                case 2:
                    $channel = 'wechat_account';
                    break;
                case 3:
                    $channel = 'wechat_card';
                    break;
                case 4:
                    $channel = 'mobile';
                    break;
                default:
                    $channel = 'wechat_qrcode';
            }
        }

        return $channel;
    }

    public function addQrcode($params = []){
        $addQrcodeId = data_get($params,'addQrcodeId',0);
        $agentId = data_get($params,'agentId',0);
        $memberId = data_get($params,'memberId',0);
        if(empty($addQrcodeId)){
            return false;
        }

        $this->addQueue($agentId,$memberId,[$addQrcodeId]);
        return true;
    }

    public function delQrcode($params = []){
        $addQrcodeId = data_get($params,'qrcodeId',0);
        $agentId = data_get($params,'agentId',0);
        if(empty($addQrcodeId)){
            return false;
        }

        $agentInfo = Agent::field('channel_type')->find($agentId);
        $imgInfo = MemberImages::find($addQrcodeId);
        $channel = $this->getAgentChannelByType($imgInfo->channel_type,$agentInfo->channel_type);

        $bucketInfo = AgentBucket::where('agent_id',$agentId)->where('channel',$channel)->find();

        $bucketId = empty($bucketInfo) ? 0 :$bucketInfo->bucket_id;
        $key = self::getQrcodeListKey($bucketId,$channel);
        //入队
        RedisService::zRem($key,$addQrcodeId);
        return true;
    }

    /**
     * 会员接单
     * @param array $params
     * @return bool
     * @throws \think\exception\DbException
     * @author
     * @date   2019/4/4 20:07
     */
    public function getOrder($params = [])
    {
        //获取用户ID,fd,加入队列
        $id = $this->getMemberId($this->serverFd);
        if(!$id){
            return false;
        }

        $memberInfo = Member::get($id);
        if(empty($memberInfo)){
            return false;
        }

        $swooleService = SwooleService::getInstance();

        //代理通道接单权限检查 - 请出队列,跳过
        $agentInfo = Agent::find($memberInfo->agent_id);
        if( $agentInfo->type == 2 ){
            $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'代理接单通道关闭!']);
            $memberInfo->is_receipt = 2;
            $memberInfo->save();
            return false;
        }

        //验证代理授信额度
        if( $agentInfo->usable_limit < 500 ){
            $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'代理授信额度不足,请提醒代理处理下发,恢复额度!']);
            $memberInfo->is_receipt = 2;
            $memberInfo->save();
            return false;
        }

        //允许掉单次数
        $maxSlowTimes = config( 'max_slow_order_num' );
        if($maxSlowTimes && $memberInfo->current_slow_order_num >= $maxSlowTimes){
            $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'您已累计有<'.$maxSlowTimes.'>笔掉单记录,请联系代理开启通道!']);
            $memberInfo->is_receipt = 2;
            $memberInfo->save();
            return false;
        }


        //允许最大空单次数
        $maxEmptyOrderNum = config('max_empty_order_num');
        if($maxEmptyOrderNum){
            //验证连续3笔订单空单,封通道
            $memberLastOrderList = MerchantOrder::field('pay_status')
                                                ->where('id', '>' ,$memberInfo->last_empty_order_id)
                                                ->where('member_id',$id)
                                                ->limit(0,$maxEmptyOrderNum)
                                                ->select();
            $num = 0;
            foreach($memberLastOrderList as $lastOrderInfo){
                //未收款or收款超时,  已上传二维码,等待支付
                if($lastOrderInfo->pay_status != 2){
                    $num++;
                }
            }

            //连续N笔空单,封通道
            if( $num >= $maxEmptyOrderNum ) {
                $memberInfo->is_pass = 2;//接单通道 关闭
                $memberInfo->save();
                $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'连续<'.$maxEmptyOrderNum.'>笔空单情况,请优化后联系代理开启通道!']);
                return false;
            }
        }

        //是否允许接单
        if( $memberInfo->is_pass == 2 ){
            $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'用户接单通道关闭!']);
            $memberInfo->is_receipt = 2;
            $memberInfo->save();
            return false;
        }

        //验证授信额度
        if($memberInfo->usable_limit < 500){
            $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'授信额度不足,请返款并督促代理处理返款记录!']);
            $memberInfo->is_receipt = 2;
            $memberInfo->save();
            return false;
        }

        //检测账号高风险,存在密码泄露
        if($memberInfo->safe_status != 1){
            $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'检测到账号存在密码泄露风险,请修改密码!']);
            $memberInfo->is_receipt = 2;
            $memberInfo->save();
            return false;
        }

        //检查激活分组,分组内是否有码
        $groupList = MemberWechat::where(['member_id'=>$id,'status'=>1])->select();
        if(!$groupList){
            $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'请激活分组后开始接单!']);
            $memberInfo->is_receipt = 2;
            $memberInfo->save();
            return false;
        }

        //获取图片列表
        $imgIds = [];
        foreach ($groupList as $groupInfo){
            if($groupInfo->auto_qrcode_img_id){
                //检测码空单情况
                $check = ImageService::checkImageEmpty($groupInfo->auto_qrcode_img_id);
                if($check == false){
                    $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>$groupInfo->title.'账号验证空单次数过多,已自动禁用激活!']);
                    //直接将此码分组禁用
                    $groupInfo->status = 0;
                    $groupInfo->save();
                    continue;
                }
                $imgIds[] = $groupInfo->auto_qrcode_img_id;
            }
        }

        if(empty($imgIds)){
            $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'激活账号内未上传二维码!']);
            $memberInfo->is_receipt = 2;
            $memberInfo->save();
            return false;
        }

        $config = config('allow_more_qrcode_get_order');
        if(!$config){
            $imgNum = count($imgIds);
            if($imgNum > 1){
                $memberInfo->is_receipt = 2;//置为未接单状态
                $memberInfo->save();
                $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'仅允许激活一个账号,请保留一个激活分组后再开工!']);
                return false;
            }
        }

        $imgList = MemberImages::whereIn('id',$imgIds)
                               ->where('member_id',$id)
                               ->select();
        if(!$imgList){
            $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'账号内二维码异常!']);
            $memberInfo->is_receipt = 2;
            $memberInfo->save();
            return false;
        }

        //允许几单一返
        $refundTimes = config( 'refund_times' );

        $refundTimes = $memberInfo->max_return_num == 0 ? $refundTimes : $memberInfo->max_return_num;

        if($refundTimes > 0){
            $hasNoReturnOrderCount = MerchantOrder::where('member_id',$id)
                                                  ->where('pay_status',2)
                                                  ->where('is_clear',2)
                                                  ->count();
            if($refundTimes < $hasNoReturnOrderCount){
                $swooleService->push($this->serverFd,['OP'=>'stopRank','msg'=>'您有<'.$refundTimes.'>笔单未确认返款,请返款并确认后再接单!']);
                $memberInfo->is_receipt = 2;
                $memberInfo->save();
                return false;
            }
        }

        $min = $this->addQueue($memberInfo->agent_id,$id,$imgIds);

        $memberInfo->is_receipt = 1;
        $memberInfo->save();

        $time = ceil($min/10);

        $swooleService->push($this->serverFd,['OP'=>'memberRank','rank'=>$min,'msg'=>'预计等待时间:'.$time.'分钟']);

        /*//执行派单
        $class = new SendOrderService($bucketId);
        $class->handel();
        unset($class);*/

        return true;
    }

    /**
     * 停止接单
     * @param $params
     * @return bool
     * @throws \think\exception\DbException
     * @author
     * @date   2019/04/12 0012 17:02
     */
    public function stopOrder($params)
    {
        //修改会员状态
        $id = isset($params['id']) ? $params['id'] : $this->getMemberId($this->serverFd);
        if(!$id){
            return false;
        }

        $memberInfo = Member::find($id);
        if(empty($memberInfo)){
            return false;
        }

        $groupList = MemberWechat::where('member_id',$id)->where('status',1)->select();

        //代理通道清除排队
        $agentInfo = Agent::find($memberInfo->agent_id);

        $imgIdList = [];
        foreach($groupList as $groupInfo){
            $imgIdList[] = $groupInfo->auto_qrcode_img_id;
        }

        //查询所有接单的号的通道...逐个通道清空
        $imgList = MemberImages::whereIn('id',$imgIdList)->select();

        // 将所有二维码按类型分组...
        $imgTypeArr = [];
        foreach ($imgList as $imgInfo) {
            $imgTypeArr[$imgInfo->channel_type][] = $imgInfo->id;
        }

        $agentBucketList = AgentBucket::where('agent_id',$memberInfo->agent_id)->select();

        $agentChannelConfig = [];
        foreach($agentBucketList as $agentBucketInfo){
            $agentChannelConfig[$agentBucketInfo->channel] = $agentBucketInfo->bucket_id;
        }

        $channelTypeList = array_keys($imgTypeArr);

        //建立 通道编码-通道type-桶ID  关联数组

        //说明: 商户一个通道只能使用一个桶,费率不同不要一个商户开多个不同通道的桶,
        //  代理一个扫码通道只能使用一个, 不能同时使用,并关联两个不同通道的同类型桶...比如 扫码, 微信扫码和支付宝扫码,不能同时使用...切记!!!
        //   这里使用哪个类型的通道, 由桶关联代理决定...

        //代理...仅支持单通道... 多通道临时解决方案 - 使用标识

        $channelTypeBucket = [];

        //微信
        foreach($channelTypeList as $channelType){
            $channel = $this->getAgentChannelByType($channelType,$agentInfo->channel_type);
            $channelTypeBucket[$channelType] = $channel;
        }

        foreach($imgTypeArr as $channelType => $channelImgIdList){
            //获取离线排队推单的码
            $channel = $channelTypeBucket[$channelType];

            $offlineKey = PayService::getQrcodeOfflineHashKey($id,$channel);
            RedisService::del($offlineKey);

            $bucketId = isset($agentChannelConfig[$channel]) ? $agentChannelConfig[$channel] : 0;
            $key = self::getQrcodeListKey($bucketId,$channel);

            //更新每个码的优先级
            foreach ($channelImgIdList as $imgId){
                //出队
                RedisService::zRem($key,$imgId);
            }
        }

        $memberInfo->is_receipt = 2;//置为未接单状态
        $memberInfo->save();
        return true;
    }

    /**
     * 商家下单
     * @param      $params
     * @param null $time
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author
     * @date   2019/4/4 20:09
     */
    public function addOrder($params)
    {
        $now = time();
        //订单ID
        $addOrderId = data_get($params,'addOrderId',0);
        if(empty($addOrderId)){
            return false;
        }

        $swooleService = SwooleService::getInstance();

        $addOrderInfo = MerchantAddOrder::find($addOrderId);

        if(empty($addOrderInfo)){
            $swooleService->push($this->serverFd,['OP'=>'failed','id'=>$addOrderId,'msg'=>'预录单未创建!']);
            return false;
        }

        $lock = new Lock('redis');

        $lockKey = 'swooleAddOrder:'.$addOrderId;
        $lock->get($lockKey,15);
        //插入派单记录
        $orderModel = MerchantOrder::where('add_order_id',$addOrderId)->find();
        //建单
        if(empty($orderModel)){
            $orderData = [
                'merchant_id'=>$addOrderInfo->merchant_id,
                'start_money'=>$addOrderInfo->start_money,
                'money'=>$addOrderInfo->money,
                'get_money'=>$addOrderInfo->get_money,
                'create_time'=>$now,
                'update_time'=>$now,
                'merchant_order_callbak_confirm_duein'=>$addOrderInfo->merchant_order_callbak_confirm_duein,
                'merchant_order_callbak_confirm_create'=>$addOrderInfo->merchant_order_callbak_confirm_create,
                'merchant_order_callbak_redirect'=>$addOrderInfo->merchant_order_callbak_redirect,
                'merchant_order_date'=>$addOrderInfo->merchant_order_date,
                'merchant_order_name'=>$addOrderInfo->merchant_order_name,
                'merchant_order_desc'=>$addOrderInfo->merchant_order_desc,
                'merchant_order_extend'=>data_get($params,'extend',''),
                'merchant_order_channel'=>$addOrderInfo->merchant_order_channel,
                'merchant_order_sn'=>$addOrderInfo->merchant_order_sn,
                'merchant_order_count'=>$addOrderInfo->merchant_order_count,
                'from_system'=>$addOrderInfo->from_system,
                'from_system_user_id'=>$addOrderInfo->from_system_user_id,
                'add_order_id'=>$addOrderId,
                'status'=>1,
                'ip'=>data_get($params,'ip','')
            ];
            Db::startTrans();
            $orderModel = MerchantOrder::create($orderData);
            if(empty($orderModel) || empty($orderModel->id)){
                $swooleService->push($this->serverFd,['OP'=>'failed','msg'=>'建单失败!']);
                Db::rollback();
                $lock->release($lockKey);
                return false;
            }

            $addOrderInfo->status = 1;
            $addOrderInfo->update_time = $now;
            $addOrderInfo->save();
            Db::commit();
            if(!empty($addOrderInfo->merchant_order_callbak_confirm_create)){
                MerchantCallbakService::confirmCreateOrder($addOrderId,true);
            }
        }
        $orderId = $orderModel->id;

        $merchantBucketInfo = MerchantBucket::where('merchant_id',$orderModel->merchant_id)
                                      ->where('channel',$orderModel->merchant_order_channel)
                                      ->find();
        $bucketId =  empty($merchantBucketInfo) ? 0 : $merchantBucketInfo->bucket_id;
//        $key = self::getOrderBucketKey($bucketId,$addOrderInfo->merchant_order_channel);

        //订单已完成派单...
        if(!empty($orderModel->member_id)){
            $swooleService->push($this->serverFd,['OP'=>'failed','id'=>$orderId,'msg'=>'已经派单成功!']);
            $lock->release($lockKey);
            return false;
        }

        $class = new SendOrderService($bucketId,$orderModel->merchant_order_channel);
        $match = $class->matchQrcode($orderModel,$this->serverFd);
        $class->release();
        unset($class);

        //匹配失败,不在推单
        if($match === false){
            //RedisService::zAdd($key,$now,$orderId);
            $swooleService->push($this->serverFd,['OP'=>'failed','id'=>$orderId,'msg'=>'系统繁忙,请稍后再下!']);
        }
        $lock->release($lockKey);
        return true;
    }


    /**
     * 推送图片
     * @param $params
     * @author
     * @date   2019/3/31 19:08
     */
    public function pushImg($params)
    {
        //拿到商家ID,获取FD
        $addOrderId = $params['addOrderId'];
        $imgUrl = $params['imgUrl'];
        $orderFd = $this->getOrderIdFdById($addOrderId);

        //验证确认收款状态,超时置为 超时状态 status = 3
        SwooleAfterService::getInstance(['timer','checkConfirmDueInTimeOut',[$addOrderId]]);
        SwooleService::getInstance()->push($orderFd,['OP'=>'pushImg','imgUrl'=>$imgUrl,'addOrderId'=>$addOrderId]);
    }

    /**
     * 确认收款
     * @param $params
     * @author
     * @date   2019/3/31 19:19
     */
    public function confirmDueIn($params)
    {
        $orderId = $params['orderId'];

        //验证回调是否成功,补发一次
        SwooleAfterService::getInstance(['timer','checkConfirmDueInCallBakTimeOut',[$orderId]]);
    }

    /**
     * 验证下发通知
     * @param $params
     */
    public function withdrawCallback($params)
    {
        $withdrawId = $params['withdrawId'];
        //验证回调是否成功,补发一次
        SwooleAfterService::getInstance(['timer','checkWithdrawCallBak',[$withdrawId]]);
    }


    /**
     * 确认创建订单验证
     * @param $params
     * @return bool
     * @author
     * @date   2019/06/06 0006 11:54
     */
    public function checkCreateOrder($params)
    {
        if(empty($params['addOrderId'])){
            return false;
        }

        SwooleAfterService::getInstance(['timer','checkConfirmCreateOrderTimeOut',[$params['addOrderId']]]);
    }

    /**
     * 群发公告推送
     * @param $params
     */
    public function pushMsg($params)
    {
        $swooleService = SwooleService::getInstance();
        $msg = $params['msg'];

        //获取所有排队人员
        $list = RedisService::zRange(PayService::$qrcodeListKey,0,-1);
        //广播变更排名
        foreach($list as $memberId){
            $fd = $this->getMemberFdById($memberId);
            if(!$fd){
                continue;
            }
            //群发
            $swooleService->push($fd,['OP'=>'pushMsg','msg'=>$msg]);
        }
    }

}