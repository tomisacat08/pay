<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   2019/3/31 23:57
 */

namespace app\api\swoole;


use app\model\Member;

class SwooleLoginService
{

    public $serverFd;

    public static $_instance;

    public static function getInstance()
    {
        if(empty(self::$_instance)){
            self::$_instance = new static();
        }
        return self::$_instance;
    }

    public function clearAll()
    {
        //清空fd映射, 订单,会员在线队列
        $keys = RedisService::keys('*swoole*');
        RedisService::del($keys);
        //所有会员全部重置为未接单状态
        Member::where('is_receipt',1)->update(['is_receipt'=>2]);
    }

    public function clear()
    {
        //仅清理fd与ID间映射关系,方便重连
        $keys = RedisService::keys('pay_swoole_*online_map');
        RedisService::del($keys);
    }

    /**
     * 统一登录入口
     * @param array    $params
     * @param array    $serverFd
     * @return bool
     * @author
     * @date   2019/3/30 15:57
     */
    public function login($params,$serverFd)
    {
        $OP = $params['OP'];
        $token = $params['params']['token'];
        $check = false;
        switch ($OP){
            case 'memberLogin':
                $check = $this->memberLogin($token,$serverFd);
                break;
            case 'merchantLogin':
                $addOrderId = array_key_exists('addOrderId',$params['params']) ? $params['params']['addOrderId'] : '';
                $check = $this->merchantLogin($token,$serverFd,$addOrderId);
                break;
            case 'systemLogin':
                $check = $this->systemLogin($token,$serverFd);
                break;
        }

        return $check;
    }

    /**
     * 用户登录
     *
     * @param $token
     * @param $serverFd
     * @return bool
     * @author
     * @date   2019/3/31 11:56
     */
    public function memberLogin( $token, $serverFd)
    {
        $userInfo = cache('user-token:' . $token);
        if ( !is_array($userInfo) || !isset($userInfo['user_token']) || $userInfo['user_token' ] !== $token ) {
            return false;
        }

        //存储token-fd 关系
        $memberId = $userInfo['id'];
        $this->setMemberLoginCache($memberId,$serverFd);
        return true;
    }

    public function systemLogin($token, $serverFd)
    {
        $systemToken = SwooleClientService::$key;
        if($systemToken !== $token){
            return false;
        }
        $this->setSystemLoginCache($serverFd);
        return true;
    }

    public function setSystemLoginCache( $serverFd)
    {
        //存储token-fd 关系
        //缓存一个登陆保持时间
        cache('swoole_online_map:'.$serverFd,1,60);
    }

    public function setMemberLoginCache( $memberId, $serverFd)
    {
        //存储token-fd 关系
        $expires = config('apiAdmin.ONLINE_TIME');
        //缓存一个登陆保持时间
        $oldFd = cache('swoole_member_online_map:'.$memberId);
        if($oldFd){
            $oldFd = cache('swoole_member_online_map:'.$memberId);
            cache('swoole_online_map:'.$oldFd,null);
        }
        cache('swoole_member_online_map:'.$memberId,$serverFd,$expires);
        cache('swoole_online_map:'.$serverFd,['type'=>'member','id'=>$memberId],$expires);
    }

    /**
     * 商户登录
     *
     * @param $token
     * @param $serverFd
     * @param $addOrderId
     * @return bool
     * @author
     * @date   2019/3/31 11:56
     */
    public function merchantLogin( $token, $serverFd,$addOrderId)
    {
        $merchantInfo = cache('MerchantToken:' . decrypt( $token ));
        if ( empty($merchantInfo) || data_get($merchantInfo,'id',0) == 0  ) {
            return false;
        }

        //存储token-fd 关系
        $this->setOrderIdLoginCache($addOrderId,$serverFd);
        return true;
    }

    public function setOrderIdLoginCache( $addOrderId, $serverFd)
    {
        //存储token-fd 关系
        $expires = config('apiAdmin.ONLINE_TIME');
        //缓存一个登陆保持时间
        $oldFd = cache('swoole_orderId_online_map:'.$addOrderId);
        if($oldFd){
            $oldFd = cache('swoole_orderId_online_map:'.$addOrderId);
            cache('swoole_online_map:'.$oldFd,null);
        }
        //缓存一个登陆保持时间
        cache('swoole_orderId_online_map:'.$addOrderId,$serverFd,$expires);
        cache('swoole_online_map:'.$serverFd,['type'=>'addOrderId','id'=>$addOrderId],$expires);
    }


    public function logout($serverFd)
    {
        $onlineInfo = cache('swoole_online_map:'.$serverFd);
        //系统获取到是0 ,跳过
        if($onlineInfo && is_array($onlineInfo) && array_key_exists('type',$onlineInfo)){
            switch ($onlineInfo['type']){
                case 'member':
                    cache('swoole_member_online_map:'.$onlineInfo['id'],null);
                    Member::where('id',$onlineInfo['id'])->update(['is_receipt'=>2]);//置为未接单状态
//                    PayService::getInstance()->clearMemberAbout($onlineInfo['id']);
                    break;
                case 'addOrderId':
                    cache('swoole_orderId_online_map:'.$onlineInfo['id'],null);
                    break;
            }
        }
        cache('swoole_online_map:'.$serverFd,null);
    }
}