<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\payapi\controller;

use app\admin\service\BucketService;
use app\api\service\AppApiService;
use app\api\service\ChannelService;
use app\api\service\MerchantCallbakService;
use app\api\service\QrCodeService;
use app\api\swoole\PayService;
use app\api\swoole\SwooleClientService;
use app\model\Agent;
use app\model\AlipayBankCard;
use app\model\Member;
use app\model\MemberImages;
use app\model\Merchant;
use app\model\MerchantAddOrder;
use app\model\MerchantBucket;
use app\model\SettlementTask;
use app\payapi\service\AddOrderService;
use app\util\lock\Lock;
use app\util\ReturnCode;
use app\model\MerchantOrder ;
use app\payapi\validate\MerchantOrder as MerchantOrdervalidate;
use app\util\Tools;
use think\Cache;
use think\Db;
use think\Exception;

class Index extends Base
{

    public function order($postData = null)
    {

        $params = $postData ? $postData : $this->request->post();

        //参数验证
        $validate = new MerchantOrdervalidate();
        $result   = $validate->scene( 'add' )->check( $params );
        if ( $result !== true ) {
            return $this->buildFailed( ReturnCode::DB_SAVE_ERROR, $validate->getError() );
        }

        $uid = data_get($params,'merchant_order_uid',0);
        $merchantInfo = Merchant::where('uid', $uid )->find();
        if (empty($merchantInfo)) {
            return $this->buildFailed(ReturnCode::INVALID, '商户UID异常,请核对商户!');
        }

        if ($merchantInfo->status != 1 ||  $merchantInfo->type != 1) {
            return $this->buildFailed(ReturnCode::INVALID, '商户状态异常,请核对商户状态!');
        }

        $merchantArr = $merchantInfo->toArray();

        $filter_checkSign = MerchantCallbakService::getSign($params,$merchantArr['id'],$signStr);

        $sign = data_get($params,'merchant_order_sign');
        if ( $sign != $filter_checkSign ) {
            return json( [ 'code' => '501', 'msg' => 'sign验证失败,请核对signStr差异,严格按照文档操作', 'data' => ['signStr'=>$signStr] ] );
        }

        $postData['merchant_id'] = $merchantArr['id'];
        $postData['merchant_order_sn'] = data_get($params,'merchant_order_sn');
        $postData['merchant_order_name'] = data_get($params,'merchant_order_name','');
        $postData['merchant_order_money'] = data_get($params,'merchant_order_money');
        ////支付通道编码
        $postData['merchant_order_channel'] = data_get($params,'merchant_order_channel',env('channel','alipay_qrcode'));//alipay_qrcode
        if ( !array_key_exists($postData['merchant_order_channel'],ChannelService::$channel_config) ) {
            return json( [ 'code' => '501', 'msg' => '支付渠道编码异常!'] );
        }

        $postData['merchant_order_count'] = data_get($params,'merchant_order_count','');
        $postData['merchant_order_extend'] = data_get($params,'merchant_order_extend','');
        $postData['merchant_order_date'] = data_get($params,'merchant_order_date');
        $postData['merchant_order_desc'] = data_get($params,'merchant_order_desc','');
        //确认收款回调
        $postData['merchant_order_callbak_confirm_duein'] = data_get($params,'merchant_order_callbak_confirm_duein','');
        //创建订单回调
        $postData['merchant_order_callbak_confirm_create'] = data_get($params,'merchant_order_callbak_confirm_create','');
        //跳转地址
        $postData['merchant_order_callbak_redirect'] = data_get($params,'merchant_order_callbak_redirect','');
        $postData['from_system'] = data_get($params,'from_system','');
        $postData['from_system_user_id'] = data_get($params,'from_system_user_id','');

        //验证金额是否在可支付范围内 默认0.01~20000.00
        $order_scope = $merchantArr['order_scope'];
        $scope = explode(',',$order_scope);
        if($scope[0] > $postData['merchant_order_money'] || $scope[1] < $postData['merchant_order_money'] ){
            return json( [ 'code' => '502', 'msg' => '下单金额不在设定金额范围内', 'data' => [] ] );
        }

        //验证下单时,是否有接单员,无人接单,直接不允许下单
        $merchantBucketInfo = MerchantBucket::where('merchant_id',$merchantInfo->id)
                                  ->where('channel',$postData['merchant_order_channel'])
                                  ->find();

        $bucketId = empty($merchantBucketInfo) ? 0 : $merchantBucketInfo->bucket_id;
        //获取接单会员
        $count = PayService::getQrcodeListCount( $bucketId, $postData['merchant_order_channel']);

        $remark = '提交成功';
        $isError = false;
        $status = 0;
        if(empty($count)){
            $isError = true;
            $status = 2;
            $remark = '当前余量不足,请联系客服增量!';
        }

        $lock = new Lock('redis');

        $lockKey = 'addOrder:'.$merchantArr['id'].':'.$postData['merchant_order_sn'];
        $lock->get($lockKey,15);
        //验证商户重复下单
        $addOrderInfo = MerchantAddOrder::where('merchant_id',$merchantArr['id'])
                                       ->where('merchant_order_sn',$postData['merchant_order_sn'])
                                       ->find();
        if(!empty($addOrderInfo)){
            //已存在订单
            $isError = true;
            $status = 2;
            $remark = '商户订单编号已存在,请勿重复下单!';
        }

        Db::startTrans();
        try{
            $postData['remark'] = $remark;
            $postData['status'] = $status;
            $addOrderId = AddOrderService::createOrder($postData);
            Db::commit();

            //有异常提示,直接返回
            if($isError){
                abort(500,$remark);
            }

            //$accessToken = AddOrderService::getAccessTokenByMerchantId($postData['merchant_id']);
            $url = AddOrderService::getOrderUrl($addOrderId);
            $lock->release($lockKey);
            return json( [ 'code' => 200, 'msg' => $remark, 'data' => ['url' => $url] ] );
        }catch(\Exception $e){
            Db::rollback();
            $lock->release($lockKey);
            return [
                'code'=>502,
                'msg'=>$e->getMessage(),
                'data'=>[]
            ];
        }
    }


    private function addOrder($dec_addOrderId)
    {
        $addOrderId = decrypt( $dec_addOrderId );

        if(empty($addOrderId)){
            abort(500,'参数缺失');
        }

        $addOrderModel  = MerchantAddOrder::find($addOrderId);
        if ( empty($addOrderModel) ) {
            abort(500,'提交失败,请重新下单');
        }

        $ip = $this->request->ip();
        $errorMsg = '';

        $lock = new Lock('redis');

        $lockKey = 'addOrder:'.$addOrderId;
        $lock->get($lockKey,15);

        $orderInfo = MerchantOrder::where('add_order_id',$addOrderModel->id)->find();
        if($orderInfo && $orderInfo->get_money_qrcode_img_id){
            $lock->release($lockKey);
            return $orderInfo;
        }

        do{
            $addKey = 'addOrder:'.$ip;
            $addOrderNum = Cache::get($addKey,0);
            $ipMaxConfig = config('ip_max_time') ?: '10/60';
            $config = explode('/',$ipMaxConfig);
            $time = $config[1];
            $max = $config[0];
            if($addOrderNum >= $max){
                $errorMsg = '请求过于频繁,请稍后再试!';
                break;
            }

            //推送下单
            $client = new SwooleClientService();
            $params = [
                'addOrderId'=>$addOrderModel->id,
                'ip'=>$ip,
            ];

            $package = $client->package('addOrder',$params);
            $client->push($package);

            try{
                $rec = $client->rec();
                if(empty($rec)){
                    abort('建单异常,socket连接获取信息失败');
                }
            }catch(\Exception $e){
                $errorMsg = $e->getMessage();
                break;
            }

            $data = $rec['data'];
            $op = $data['OP'];
            switch ($op){
                case 'failed': //失败
                    $errorMsg = $data['msg'];
                    break 2;
                case 'adminPushMsg':
                    $orderInfo = MerchantOrder::where('add_order_id',$addOrderModel->id)->find();
                    break;
            }
        }while(false);



        $addOrderModel->error_msg = $errorMsg;
        $addOrderModel->ip = $ip;
        $addOrderModel->save();

        if(!empty($errorMsg)){
            $lock->release($lockKey);
            abort(500,$errorMsg);
        }

        if($addOrderNum == 0){
            Cache::set($addKey,1,$time);
        }else{
            Cache::inc($addKey);
        }

        //二次验证保证推送成功
        if(empty($orderInfo) || empty($orderInfo->get_money_qrcode_img_id) ){
            $lock->release($lockKey);
            return false;
        }

        $lock->release($lockKey);
        return $orderInfo;
    }

    public function wechat_qrcode($id)
    {
        try{
            $orderInfo = $this->addOrder($id);
            if(empty($orderInfo)){
                return json( [ 'code' => '2', 'msg' => '下单失败,请重试!', 'data' => [] ] );
            }
        }catch(\Exception $e){
            $errorMsg = $e->getMessage();
            return json( [ 'code' => '2', 'msg' => $errorMsg, 'data' => [] ] );
        }

        $apiService = new AppApiService();
        $imgInfo = MemberImages::find($orderInfo->get_money_qrcode_img_id);
        $channelInfo = $apiService->getChannelInfo($imgInfo,$orderInfo);

        $imgUrl = $channelInfo['imgUrl'];

        $viewData = [
            'get_money' => $orderInfo->get_money,
            'voucher_pic' => $imgUrl,
            'merchant_order_sn' => $orderInfo->merchant_order_sn,
        ];

        return view( 'wechat_qrcode' )->assign( $viewData );

    }

    public function alipay_qrcode( $id )
    {
        try{
            $orderInfo = $this->addOrder($id);
            if(empty($orderInfo)){
                return json( [ 'code' => '2', 'msg' => '下单失败,请重试!', 'data' => [] ] );
            }
        }catch(\Exception $e){
            $errorMsg = $e->getMessage();
            return json( [ 'code' => '2', 'msg' => $errorMsg, 'data' => [] ] );
        }

        $apiService = new AppApiService();
        $imgInfo = MemberImages::find($orderInfo->get_money_qrcode_img_id);
        $channelInfo = $apiService->getChannelInfo($imgInfo,$orderInfo);

        $imgUrl = $channelInfo['imgUrl'];
        $text = $channelInfo['text'];
        $jumpButton = config( 'jump_button' );
        $jumpButton = $jumpButton ?: 0;

        $viewData = [
            'get_money' => $orderInfo->get_money,
            'voucher_pic' => $imgUrl,
            'order_sn' => $orderInfo->merchant_order_sn,
            'text' => $text,
            'jumpButton' => $jumpButton,
        ];

        return view( 'alipay_qrcode_new' )->assign( $viewData );
    }

    public function alipay_account($id)
    {
        try{
            $orderInfo = $this->addOrder($id);
            if(empty($orderInfo)){
                return json( [ 'code' => '2', 'msg' => '下单失败,请重试!', 'data' => [] ] );
            }
        }catch(\Exception $e){
            $errorMsg = $e->getMessage();
            return json( [ 'code' => '2', 'msg' => $errorMsg, 'data' => [] ] );
        }

        $apiService = new AppApiService();
        $imgInfo = MemberImages::find($orderInfo->get_money_qrcode_img_id);
        $channelInfo = $apiService->getChannelInfo($imgInfo,$orderInfo);

        $account = $channelInfo['account'];
        $real_name = $channelInfo['real_name'];


        $timeOutConfig = config('time_out');

        $now = time();

        $createTime = $orderInfo->getData('create_time');

        $onlyTime = $createTime + ($timeOutConfig*60) - $now;

        $viewData = [
            'account' => $account,
            'real_name' => $real_name,
            'get_money' => $orderInfo->get_money,
            'merchant_order_sn' => $orderInfo->merchant_order_sn,
            'timeOutOnly' => $onlyTime,
        ];

//        return view( 'alipay_account' )->assign( $viewData );
        return view( 'alipay_account_0611' )->assign( $viewData );
    }

    public function bank_card($id)
    {
        try{
            $orderInfo = $this->addOrder($id);
            if(empty($orderInfo)){
                return json( [ 'code' => '2', 'msg' => '下单失败,请重试!', 'data' => [] ] );
            }
        }catch(\Exception $e){
            $errorMsg = $e->getMessage();
            return json( [ 'code' => '2', 'msg' => $errorMsg, 'data' => [] ] );
        }

        $apiService = new AppApiService();
        $imgInfo = MemberImages::find($orderInfo->get_money_qrcode_img_id);
        $channelInfo = $apiService->getChannelInfo($imgInfo,$orderInfo);
        $channel = $orderInfo->merchant_order_channel;


        $timeOutConfig = config('time_out');

        $now = time();

        $createTime = $orderInfo->getData('create_time');

        $onlyTime = $createTime + ($timeOutConfig*60) - $now;

        $bankCard = $channelInfo['bank_card'];
        $bankAccount = $channelInfo['bank_account'];
        $bankName = $channelInfo['bank_name'];
        $bankDesc = $channelInfo['bank_desc'];

        $viewData = [
            'bank_card' => $bankCard,
            'bank_account' => $bankAccount,
            'bank_name' => $bankName,
            'bank_desc' => $bankDesc,
            'get_money' => $orderInfo->get_money,
            'merchant_order_sn' => $orderInfo->merchant_order_sn,
            'channel' => $channel,
            'timeOutOnly' => $onlyTime,
        ];

//        return view( 'bank_card_new' )->assign( $viewData );
        return view( 'bank_card_0607' )->assign( $viewData );
    }


    public function alipay_command_key($id)
    {
        $id    = decrypt( $id );

        $data  = MerchantAddOrder::get($id);
        if ( !$data ) {
            return json( [ 'code' => '2', 'msg' => '提交失败', 'data' => [] ] );
        }
        $match_time = '';
        $create_time = '';

        //已下单, 断线重连逻辑,刷新页面逻辑
        $orderInfo = MerchantOrder::where('add_order_id',$id)->find();
        if($orderInfo){
            $match_time = $orderInfo->match_time;
            $create_time = $orderInfo->create_time;
        }

        $viewData = [
            'get_money' => $data->get_money,
            'time' => time(),
            'match_time' => $match_time,
            'create_time' => $create_time,
            'merchant_order_sn' => $data->merchant_order_sn,
            'callBakUrl' => $data->merchant_order_callbak_redirect,
            'addOrderId' => $id,
        ];

        return view( 'alipay_command_key' )->assign( $viewData );
    }


    public function alipay_h5($id)
    {
        if(empty($id)){
            return json( [ 'code' => '2', 'msg' => '参数缺失', 'data' => [] ] );
        }

        $addOrderModel  = MerchantAddOrder::find($id);
        if ( !$addOrderModel ) {
            return json( [ 'code' => '2', 'msg' => '提交失败', 'data' => [] ] );
        }

        $qrcodeUrl = '';

        $ip = $this->request->ip();
        $errorMsg = '';
        $apiService = new AppApiService();
        //已下单, 断线重连逻辑,刷新页面逻辑
        $orderInfo = MerchantOrder::where('add_order_id',$id)->find();
        if($orderInfo){
            if($orderInfo->get_money_qrcode_img_id){
                $imgInfo = MemberImages::find($orderInfo->get_money_qrcode_img_id);
                $channelInfo = $apiService->getChannelInfo($imgInfo,$orderInfo);
                $qrcodeUrl = $channelInfo['text'];
            }
        }
        //新增单,防刷
        else{

            do{
                $addKey = 'addOrder:'.$ip;
                $addOrderNum = Cache::get($addKey,0);
                $ipMaxConfig = config('ip_max_time') ?: '2/60';
                $config = explode('/',$ipMaxConfig);
                $time = $config[1];
                $max = $config[0];
                if($addOrderNum >= $max){
                    $errorMsg = '请求过于频繁,请稍后再试!';
                    break;
                }

                if($addOrderNum == 0){
                    Cache::set($addKey,1,$time);
                }else{
                    Cache::inc($addKey);
                }

                //推送下单
                $client = new SwooleClientService();
                $params = [
                    'addOrderId'=>$id,
                    'ip'=>$ip,
                ];

                $package = $client->package('addOrder',$params);
                $client->push($package);

                try{
                    $rec = $client->rec();
                    if(empty($rec)){
                        throw new Exception('建单异常,socket连接获取信息失败');
                    }
                }catch(\Exception $e){
                    $errorMsg = $e->getMessage();
                    break;
                }



                $data = $rec['data'];
                $op = $data['OP'];
                switch ($op){
                    case 'failed': //失败
                        $errorMsg = $data['msg'];
                        break 2;
                    case 'pushImg':
                        $qrcodeUrl = $data['text'];
                        break;
                }
            }while(false);

            $addOrderModel->error_msg = $errorMsg;
            $addOrderModel->ip = $ip;
            $addOrderModel->save();

            if(!empty($errorMsg)){
                return $this->buildFailed(ReturnCode::INVALID, $errorMsg);
            }
        }

//        $headerUrl = 'alipayqr://platformapi/startapp?saId=10000007&qrcode='.$qrcodeUrl;

        return header("refresh:0;url='".$qrcodeUrl."'");
    }


    public function api()
    {
        $text = file_get_contents('./api/markdown/md/api.md');

        $host = env('host','http://'.$_SERVER['HTTP_HOST']);
//        $IP = $_SERVER['SERVER_ADDR'];
        $IP = env('ip',gethostbyname($_SERVER["SERVER_NAME"]));


        $text = str_replace('{{HOST}}',$host,$text);
        $text = str_replace('{{IP}}',$IP,$text);
        $viewData = ['md_txt'=>$text];
//        $parser = new Parser();
//        $html = $parser->makeHtml($text);
//        $viewData = ['md_txt'=>$text,'md_html'=>$html];
        return view( './api/pay/api.html' )->assign( $viewData );
    }


    public function testCallBak()
    {

    }

    /**
     * 支付宝转卡跳转链接
     * @param $id
     * @return bool|\think\response\Json|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function alipayToCard($id){
        if(empty($id)){
            return false;
        }
        $orderId = $this->request->get('id');

        $alipayBankId = decrypt($id);

        $bankInfo = AlipayBankCard::find($alipayBankId);
        if(empty($bankInfo)){
            return json(['code'=>2,'msg'=>"异常二维码数据!",]);
        }

        $money = 0;
        if($orderId){
            $orderInfo = MerchantOrder::find($orderId);
            if(!empty($orderInfo)){
                //扫码超时,失效处理
                if(time() - $orderInfo->upload_time > 60){
                    return json(['code'=>2,'msg'=>"此码已过期,请重新下单",]);
                }

                //已收款订单,不允许支付
                if($orderInfo->pay_status == 2){
                    return json(['code'=>2,'msg'=>"此码已使用过,请重新下单",]);
                }

                $money = $orderInfo->get_money;
            }
        }


        $data = [
            'appId' => '09999988',
            'actionType' => 'toCard',
            'sourceId' => 'bill',
            'cardNo' => $bankInfo->card_no,
            'bankAccount' => $bankInfo->bank_account,
            'money' => $money,
            'amount' => $money,
            'bankMark' => $bankInfo->bank_mark,
            'bankName' => $bankInfo->bank_name,
        ];
        $urlParams = http_build_query($data);
        $codeUrl = "alipays://platformapi/startapp?".$urlParams;
        return header("refresh:0;url='".$codeUrl."'");
    }


    /**
     * 支付宝转卡二维码生成页面
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function createQrCodeIndex(){
        $viewData['list'] = db('bank')->order('id asc')->select();
        return view( 'bank_qrcode' )->assign($viewData);
    }


    /**
     * 生成支付宝转银行卡二维码
     * @return \think\response\Json
     * @throws \think\exception\DbException
     */
    public function createQrCode(){
        $bank = $this->request->post( 'bank' );
        $cardNo = $this->request->post( 'cardNo' );
        $bankAccount = $this->request->post( 'bankAccount' );
        $password = $this->request->post( 'password' );
        $mobile = $this->request->post( 'mobile' );
        if(empty($mobile)){
            return json(['code'=>2,'msg'=>"请输入用户名",]);
        }
        if(empty($password)){
            return json(['code'=>2,'msg'=>"请输入密码",]);
        }
        if(empty($bankAccount)){
            return json(['code'=>2,'msg'=>"请输入收款人姓名",]);
        }
        if(empty($cardNo)){
            return json(['code'=>2,'msg'=>"请选输入银行卡号",]);
        }
        if(empty($bank)){
            return json(['code'=>2,'msg'=>"请选择银行",]);
        }

        $password = Tools::userMd5($password);
        $userInfo = Member::get(['mobile' => $mobile, 'password' => $password]);

        if(!$userInfo){
            return json(['code'=>2,'msg'=>"用户名或密码错误"]);
        }

        $bank = explode('-',$bank);

        //验证重复
        $bankInfo = AlipayBankCard::where('member_id',$userInfo->id)->where('card_no',$cardNo)->find();
        if(empty($bankInfo)){
            //多个收款员用同一张卡,可行
            $createData = [
                'member_id'=>$userInfo->id,
                'bank_name'=>$bank[1],
                'bank_mark'=>$bank[0],
                'card_no'=>$cardNo,
                'bank_account'=>$bankAccount,
                'create_time'=>time(),
            ];

            $bankInfo = AlipayBankCard::create($createData);
        }

        $createId = encrypts($bankInfo->id);

        //生成新的二维码
        $text = env('host',$_SERVER['SERVER_NAME']).'/payapi/Index/alipayToCard/'.$createId;
        $QrCodeService = new QrCodeService();
        $imgUri = $QrCodeService->create($text);
        return json(['code'=>1,'msg'=>"生成二维码成功",'img'=>$imgUri,'url'=>$text]);
    }


    /**
     * 查询网关
     * @return array|\think\response\Json
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @date   11/06 006 16:54
     */
    public function select()
    {
        $uid = $this->request->param('merchant_order_uid',0);
        if(empty($uid)){
            return $this->buildFailed( ReturnCode::INVALID, '商户UID参数缺失!' );
        }

        $sn = $this->request->param('merchant_order_sn',0);
        if(empty($sn)){
            return $this->buildFailed( ReturnCode::INVALID, '商户单号参数缺失!' );
        }

        $merchantInfo = Merchant::where(['uid' => $uid, 'status' => 1,'type'=>1])->find();
        if (empty($merchantInfo)) {
            return $this->buildFailed( ReturnCode::INVALID, '商户状态异常,请核对商户状态!' );
        }

        $merchantArr = $merchantInfo->toArray();

        $params = $this->request->post();
        $filter_checkSign = MerchantCallbakService::getSign($params,$merchantArr['id'],$signStr);

        $sign = $this->request->param('merchant_order_sign');
        if ( $sign != $filter_checkSign ) {
            return json( [ 'code' => '501', 'msg' => 'sign验证失败,请核对signStr是否有差异', 'data' => ['signStr'=>$signStr] ] );
        }

        $addOrderInfo = MerchantAddOrder::where('merchant_id',$merchantArr['id'])
                                     ->where('merchant_order_sn',$sn)
                                     ->find();

        if(empty($addOrderInfo)){
            return json( [ 'code' => '200', 'msg' => '查询失败,订单不存在' ] );
        }

        $msg = '查询成功';
        $payStatus = 2;//未收款
        switch ($addOrderInfo->status){
            case 0:
                $msg = '订单创建中...';
                break;
            case 2:
                $msg = '订单创建失败,支付页面未开启或开启已超时';
                break;
            case 1:
                $msg = '订单录入成功';
                $orderInfo = MerchantOrder::where('merchant_id',$merchantArr['id'])
                                          ->where('merchant_order_sn',$sn)
                                          ->find();
                if(empty($orderInfo)){
                    return json( [ 'code' => '200', 'msg' => '查询异常' ] );
                }

                switch ($orderInfo->pay_status){
                    case 1:
                        $msg .= ' 待收款';
                        break;
                    case 2:
                        $payStatus = 1;//已收款
                        $msg .= ' 已收款';
                        break;
                    case 3:
                        $msg .= ' 收款超时';
                        break;
                    default:
                        $msg .= ' 收款状态异常';
                }
                break;

        }

        return json( [ 'code' => '200', 'msg' => $msg, 'data'=>['pay_status'=>$payStatus] ] );
    }

}
