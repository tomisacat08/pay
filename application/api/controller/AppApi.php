<?php

namespace app\api\controller;

use app\admin\service\MemberService;
use app\api\service\AppApiService;
use app\api\service\AppService;
use app\api\service\QrCodeService;
use app\api\validate\Account;
use app\api\validate\BankCard;
use app\model\AlipayBankCard;
use app\model\Member;
use app\model\MemberDevice;
use app\model\MemberLoginLog;
use app\model\MemberWechat;
use app\util\lock\Lock;
use app\util\ReturnCode;
use app\util\Tools;
use think\Cache;

class AppApi extends Base{

    private $loginNum = 5;//登录错误次数限制
    private $waiteTime = 120; //登录错误达到限制后 n 分钟后才可以登录

    /**
     * 用户登录
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function login() {
        $username = $this->request->post('mobile');
        $password = $this->request->post('password');
        if (!$username) {
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '缺少用户名!');
        }
        if (!$password) {
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '缺少密码!');
        } else {
            $password = Tools::userMd5($password);
        }

        //当前请求ip地址
        $ip = $this->request->ip();
        $loginIpLock = 'memberLogin:'.$username.':'.$ip;
        $loginNum = Cache::get($loginIpLock,0);
        if($loginNum > $this->loginNum){
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '密码输错'.$this->loginNum.'次,请稍后再试');
        }

        $userInfo = Member::where('mobile',$username)->find();
        if (empty($userInfo) || $password != $userInfo->password) {
            $time = $this->waiteTime * 60;
            $loginNum++;
            Cache::set($loginIpLock,$loginNum,$time);

            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '用户名密码不正确');
        }

        if ($userInfo->status == 2) {
            return $this->buildFailed(ReturnCode::LOGIN_ERROR, '用户已被封禁，请联系管理员');
        }

        //查找设备是否绑定
        $appInfo = MemberDevice::$appInfo;
        $memberDeviceInfo = MemberDevice::where('device_id',$appInfo['device_id'])
                    ->where('member_id',$userInfo->id)
                    ->find();

        if(!$memberDeviceInfo){
            //设备绑定了,且设备号从未登录过次账号,!!!存在高风险
            if($userInfo->member_device_id != 0){

                $loginLogData = [
                    'member_id' => $userInfo->id,
                    'device_id' => $appInfo['device_id'],
                    'ip' => $ip,
                    'create_time'=>time(),
                    'type'=>2,
                ];
                MemberLoginLog::create($loginLogData);

                //账号置为 不安全状态
                $userInfo->safe_status = 2;
                $userInfo->save();

                //已被非法设备登录!
                return $this->buildFailed(ReturnCode::LOGIN_ERROR, '非法设备登录,如更换设备登录,请联系代理处理!');
            }

            //保存已登录过的设备,方便下次更换设备
            $createData = [
                'device_id'=>$appInfo['device_id'],
                'member_id'=>$userInfo->id,
                'create_time'=>time()
            ];
            $memberDeviceInfo = MemberDevice::create($createData);
        }

        $loginLogData = [
            'member_id' => $userInfo->id,
            'device_id' => $appInfo['device_id'],
            'ip' => $ip,
            'create_time'=>time(),
            'type'=>1,
        ];
        MemberLoginLog::create($loginLogData);

        //登录成功,清理封禁次数
        Cache::rm($loginIpLock);

        //清空老token节省redis空间
        cache('user-token:' . $userInfo->user_token,null);
        $userToken = md5(uniqid() . time());

        //更新用户数据
        $userInfo->user_token = $userToken;
        $userInfo->last_login_ip = $this->request->ip();
        $userInfo->last_login_time = time();
        $userInfo->member_device_id = $memberDeviceInfo->id;
        $userInfo->save();

        $expires = config('apiAdmin.ONLINE_TIME');
        cache('user-token:' . $userToken,$userInfo->toArray(),$expires);

        $return['uid'] = $userInfo->uid;
        $return['mobile'] = $userInfo->mobile;
        $return['nickname'] = $userInfo->nickname;
        $return['user-token'] = $userToken;

        $usedWechatId = MemberWechat::where(['member_id'=>$userInfo->id,'status'=>1])->value('id');
        if(!$usedWechatId){
            $usedWechatId = 0;
        }
        $return['usedWechatId'] = $usedWechatId;
        return $this->json($return, '登录成功');
    }

    public function logout() {
        $service = new AppApiService();
        $memberId = $this->userInfo['id'];
        $return = $service->logout($memberId);

        return $this->json($return);
    }


    /**
     * 上传收款码/返款凭证   type: 1收款码,   2返款凭证
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:38
     */
    public function uploadImg() {
        ini_set('memory_limit', '2048M');
        $service = new AppApiService();
        $type = $this->request->post('type');
        $orderId = $this->request->post('orderId');
        $userInfo = $this->userInfo;

        switch($type){
//            case 1://上传收款二维码
//                $return = $service->uploadQrCode($orderId,$userInfo);
//                break;
            case 2://上传返款凭证
                $return = $service->uploadReturnImg($orderId,$userInfo);
                break;
            default:
                return $this->buildFailed(ReturnCode::FILE_SAVE_ERROR, 'type参数错误!');
        }

        return $this->json($return);
    }

    /**
     * 上传自动收款码
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/04/15 0015 16:25
     */
    public function uploadAutoQrcode() {
        ini_set('memory_limit', '2048M');
        $service = new AppApiService();
        $userInfo = $this->userInfo;
        $wechatId = $this->request->post('wechatId');

        $return = $service->uploadAutoQrCode($userInfo,$wechatId);
        return $this->json($return);
    }

    /**
     * 添加账户信息
     */
    public function addAccount() {
        $service = new AppApiService();
        $userInfo = $this->userInfo;
        $params = $this->request->post();
        //参数验证
        $validate = new Account();
        $result   = $validate->scene( 'add' )->check( $params );
        if ( $result !== true ) {
            return $this->buildFailed( ReturnCode::DB_SAVE_ERROR, $validate->getError() );
        }


        $wechatId = $this->request->post('wechatId');
        $account = $this->request->post('account');
        $real_name = $this->request->post('real_name');

        $return = $service->addAccount($userInfo,$wechatId,$account,$real_name);
        return $this->json($return);
    }
    /**
     * 添加银行卡
     */
    public function addBankCard() {
        $service = new AppApiService();
        $userInfo = $this->userInfo;
        $params = $this->request->post();
        //参数验证
        $validate = new BankCard();
        $result   = $validate->scene( 'add' )->check( $params );
        if ( $result !== true ) {
            return $this->buildFailed( ReturnCode::DB_SAVE_ERROR, $validate->getError() );
        }
        $wechatId = $this->request->post('wechatId');
        $bankCard = $this->request->post('bank_card');
        $bankName = $this->request->post('bank_name');
        $bankAccount = $this->request->post('bank_account');
        $bankDesc = $this->request->post('bank_desc','');

        $return = $service->addBankCard($userInfo,$wechatId,$bankCard,$bankName,$bankAccount,$bankDesc);
        return $this->json($return);
    }

    /**
     * 开启自动匹配无金额二维码模式
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/04/15 0015 18:15
     */
    public function setAutoQrcodeModel()
    {
        $key = $this->request->get('key'); //1,开启,2关闭

        $is_auto_qrcode = $key == 1 ? 1 : 2;
        $userInfo = $this->userInfo;
        $memberInfo = Member::get($userInfo['id']);

        if($is_auto_qrcode == 1){
            //验证当前激活微信是否有上传图片
            $imgId = MemberWechat::where(['member_id'=>$memberInfo->id,'status'=>1])->value('auto_qrcode_img_id');

            if(!$imgId){
                return $this->buildFailed(ReturnCode::INVALID, '当前微信未上传自动二维码');
            }
        }

        $memberInfo->is_auto_qrcode = $is_auto_qrcode;
        $save = $memberInfo->save();
        return $this->json($save);
    }

    /**
     * 获取微信自动收款码
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function getAutoQrcode(){
        $id = $this->request->get('wechatId');
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->getAutoQrcode($userInfo['id'],$id);
        return $this->json($return);
    }



    /**
     * 获取待收款详情
     */
    public function getDueInInfo()
    {
        $orderId = $this->request->get('orderId');
        $service = new AppApiService();
        $return = $service->getDueInInfo($orderId);
        return $this->json($return);
    }




    /**
     * 确认收款
     */
    public function confirmDueIn()
    {
        $orderId = $this->request->get('orderId');
        $money = $this->request->get('money');
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->confirmDueIn($orderId,$money,$userInfo['id']);
        return $this->json($return);
    }


    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function unSettlementOrderList()
    {
        $page = $this->request->get('page',1);
        $num = $this->request->get('num',20);
        $offset = ($page-1)*$num;
        $service = new AppApiService();
        $userInfo = $this->userInfo;
        $return = $service->getOrderListByStatus($userInfo['id'],function($query){
            $query->where('status',3);
        },$offset,$num);
        return $this->json($return);
    }
    /**
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getOrderList()
    {
        $page = $this->request->get('page',1);
        $num = $this->request->get('num',20);
        $offset = ($page-1)*$num;
        $service = new AppApiService();
        $userInfo = $this->userInfo;
        $return = $service->getOrderListByStatus($userInfo['id'],null,$offset,$num);
        return $this->json($return);
    }

    /**
     * 获取用户信息(缓存)
     * @return \think\response\Json
     * @author
     * @date   2019/3/27 16:44
     */
    public function getUserInfo()
    {
        $memberId = $this->userInfo['id'];
        $userInfo = Member::get($memberId);

        $data = [];
        $data['status'] = $userInfo->status;
        $data['nickname'] = $userInfo->nickname;
        $data['total_limit'] = $userInfo->total_limit;
        $data['usable_limit'] = $userInfo->usable_limit;
        $data['poundage_ratio'] = $userInfo->poundage_ratio;
        $data['is_auto_qrcode'] = $userInfo->is_auto_qrcode;
        $data['slow_order_num'] = $userInfo->slow_order_num;
        $memberService = new MemberService();
        $yesterdayRateData = $memberService->getMemberYesterdayTurnoverRate($memberId);
        $allRateData = $memberService->getMemberTurnoverRate($memberId);
        $todayRateData = $memberService->getMemberTodayTurnoverRate($memberId);

        $yesterdayRate = '昨日成单情况(成单/总单:成功率) | '.$yesterdayRateData['successOrderNum'].'/'.$yesterdayRateData['allOrderNum'].':'.$yesterdayRateData['rate'];
        $todayRate = '今日成单情况(成单/总单:成功率) | '.$todayRateData['successOrderNum'].'/'.$todayRateData['allOrderNum'].':'.$todayRateData['rate'];
        $allRate = '总成单情况(成单/总单:成功率) | '.$allRateData['successOrderNum'].'/'.$allRateData['allOrderNum'].':'.$allRateData['rate'];

        $data['desc'] = $yesterdayRate.' <br />' .$todayRate.'<br /> '.$allRate;
        return $this->json($data);
    }


    /**
     * 修改密码
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function editPassword()
    {
        $old = $this->request->post('old');
        $new = $this->request->post('new');
        $newConfirm = $this->request->post('new_confirm');


        $userInfo = $this->userInfo;
        $service = new AppApiService();

        $return = $service->editPassword($userInfo['id'],$old,$new,$newConfirm);
        return $this->json($return);
    }


    /**
     * 获取返款信息
     * @return \think\response\Json
     * @author
     * @date   2019/3/28 9:37
     */
    public function getRefundInfo()
    {
        $orderId = $this->request->get('orderId');
        $service = new AppApiService();
        $info = $service->getRefundInfo($orderId);
        return $this->json($info);
    }


    /**
     * 确认返款
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:56
     */
    public function confirmRefund()
    {
        $orderId = $this->request->get('orderId');
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->confirmRefund($orderId,$userInfo['id']);
        return $this->json($return);
    }

    public function getNoticeList()
    {
        $num = $this->request->get('num', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $page = $this->request->get('page', 1);

        $offset = ($page-1)*$num;

        $service = new AppApiService();
        $return = $service->getNoticeList('member',$offset,$num);
        return $this->json($return);
    }
    /**
     * 微信账号列表
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function wechatList()
    {
        $num = $this->request->get('num', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $page = $this->request->get('page', 1);
        $offset = ($page-1)*$num;
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->getWechatList($userInfo['id'],$offset,$num);
        return $this->json($return);
    }
    /**
     * 添加微信账号
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function addWechat()
    {
        $title = $this->request->post('title/s');
        $desc = $this->request->post('desc/s','');
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->createWechat($userInfo['id'],$title,$desc);
        return $this->json($return);
    }
    /**
     * 删除微信账号
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function  delWechat(){
        $id = $this->request->get('id');
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->getDelWechat($userInfo['id'],$id);
        return $this->json($return);
    }
    /**
     * 激活微信账号
     * @return \think\response\Json
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 9:37
     */
    public function  usedWechat(){
        $id = $this->request->get('id');
        $status = $this->request->get('status',1);
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->usedWechat($userInfo['id'],$id,$status);
        return $this->json($return);
    }


    public function getImgUrl()
    {
        $id = $this->request->get('id');
        $service = new AppApiService();
        $return = $service->getImgUrlById($id);
        return $this->json($return);
    }


    public function getStatus()
    {
        $userInfo = $this->userInfo;
        $service = new AppApiService();
        $return = $service->getStatus($userInfo);
        return $this->json($return);
    }

    public function addAlipayAppId()
    {
        $userInfo = $this->userInfo;
        $groupId = $this->request->post('wechatId');
        $appId = $this->request->post('appId');

        $service = new AppApiService();
        $return = $service->addAlipayAppId($userInfo['id'],$groupId,$appId);
        return $this->json($return);
    }

    public function addQrcodeByUrl()
    {
        $userInfo = $this->userInfo;
        $groupId = $this->request->post('wechatId');
        $url = $this->request->post('url');

        $service = new AppApiService();
        $return = $service->addQrcodeByUrl($userInfo['id'],$groupId,$url);
        return $this->json($return);
    }


    public function getAppIdImg()
    {
        $appId = $this->request->post('appId');

        $service = new AppApiService();
        $return = $service->createImgByAppId($appId);
        return $this->json($return);
//        return header("refresh:0;url='".$codeUrl."'");
    }

}
