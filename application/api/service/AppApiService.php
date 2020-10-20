<?php

namespace app\api\service;

use app\admin\service\ImageService;
use app\agent\service\MemberService;
use app\agent\service\MoneyService;
use app\api\handle\Swoole;
use app\api\swoole\PayService;
use app\api\swoole\RedisService;
use app\api\swoole\SwooleClientService;
use app\api\swoole\SwooleLoginService;
use app\model\Agent;
use app\model\BankCard;
use app\model\Config;
use app\model\Member;
use app\model\MemberConfirmLog;
use app\model\MemberImages;
use app\model\Merchant;
use app\model\MerchantCallbakLog;
use app\model\MerchantOrder;
use app\model\MemberWechat;
use app\model\Notice;
use app\util\lock\Lock;
use app\util\ReturnCode;
use app\util\Tools;
use think\Db;
use think\Image;
use think\Request;

class AppApiService {

    public function logout($memberId)
    {
        $memberInfo = Member::find($memberId);
        $userToken = $memberInfo->user_token;
        cache('user-token:' . $userToken,null);

        //清理swoole在线情况
        $memberFd = cache('swoole_member_online_map:'.$memberId);
        if($memberFd) {
            //手动清理登录状态
            SwooleLoginService::getInstance()->logout( $memberFd );
        }

        return [
            'code'=>ReturnCode::SUCCESS,
            'msg'=>'登出成功'
        ];
    }

    public function uploadAutoQrCode($userInfo,$wechatId)
    {
        $wechatModel = MemberWechat::where('id',$wechatId)
                                   ->where('member_id',$userInfo['id'])
                                   ->find();
        //验证微信ID是否匹配
        if(!$wechatModel){
            return false;
        }

        $memberInfo = Member::find($userInfo['id']);

        //有码,且分组处于激活状态, 禁止覆盖上传二维码
        if($wechatModel->auto_qrcode_img_id){
            if($memberInfo->is_receipt == 1 && $wechatModel->status == 1){
                return ['code'=>ReturnCode::FILE_SAVE_ERROR,'msg'=>'请停工后再替换原二维码','data'=>[]];
            }
        }



        $tmp_name = $_FILES['file']['tmp_name'];
        $uploadFileName = $_FILES['file']['name'];
        if(!isImage($tmp_name)){
            return ['code'=>ReturnCode::FILE_SAVE_ERROR,'msg'=>'请上传jpg,png,bmp格式文件','data'=>[]];
        }

        $path = '/upload/api/auto_qrcode/';
        $newFileName = $this->saveFile($tmp_name,$path,$uploadFileName);
        $filePath = $path.$newFileName;
        $fullFilePath = $_SERVER['DOCUMENT_ROOT'].$path.$newFileName;


        $QrCodeService = new QrCodeService();
        $text = $QrCodeService->read($fullFilePath,1);

        //是否允许原图上传,并展示
        $allowOrigin = config('allow_origin');
        if($text != false){
            //能识别,走识别
        }elseif($allowOrigin){
            $text = '';
        }else{
            @unlink($fullFilePath);//识别失败,删除原图
            return [
                'code'=>ReturnCode::FILE_SAVE_ERROR,
                'msg'=>'二维码无法识别,请尽量裁切掉其他无用部分,提高识别率!',
                'data'=>[]
            ];
        }


        //原图
        if($text === ''){
            $systemQrCodePath = $filePath;
        }else{

            $urlInfo = parse_url($text);
            if(empty($urlInfo)){
                return false;
            }
            //过滤query 参数
            /*
            $a = 'wxp://f2f0obpFdKamARU5K5qLNDxoJSNCcHm2kiOw';
            $b = 'https://qr.alipay.com/fkx12561gbzx5wtaodvlw38?t=1583981373694';

            var_dump(parse_url($a),parse_url($b));
            array(2) {
             ["scheme"]=>
             string(3) "wxp"
                        ["host"]=>
             string(36) "f2f0obpFdKamARU5K5qLNDxoJSNCcHm2kiOw"
            }
            array(4) {
                    ["scheme"]=>
             string(5) "https"
                    ["host"]=>
             string(13) "qr.alipay.com"
                    ["path"]=>
             string(24) "/fkx12561gbzx5wtaodvlw38"
                    ["query"]=>
             string(15) "t=1583981373694"
            }*/

            $scheme = strtolower($urlInfo['scheme']);
            //重组text真实地址, 用于优化搜索
            switch ($scheme){
                case 'wxp'://微信
                    $text = $urlInfo['scheme'].'://'.$urlInfo['host'];
                    break;
                case 'https'://支付宝
                    $text = $urlInfo['scheme'].'://'.$urlInfo['host'].$urlInfo['path'];
                    break;
            }

            //验证二维码是否已经在系统中使用过,禁止重复上传
            $exists = MemberImages::where('pay_qrcode_url',$text)
                                  ->where('member_id',$userInfo['id'])
                                  ->where('delete_at',0)
                                  ->find();
            if($exists){
                @unlink($fullFilePath);//识别失败,删除原图
                //查询出已存在的码分组,给与提示...让他处理
                $wechatId = $exists->wechat_id;
                $wechatInfo = MemberWechat::find($wechatId);
                return [
                    'code'=>ReturnCode::FILE_SAVE_ERROR,
                    'msg'=>'分组['.$wechatInfo->title.']中已存在此码,请勿重复上传!',
                    'data'=>[]
                ];
            }

            //生成缩略图
            $thumbClass = Image::open($fullFilePath);
            // 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
            $thumbClass->thumb(250, 250,1)->save($fullFilePath);//直接把缩略图覆盖原图

            //生成新图片 QRcode
            $systemQrCodeDir = '/upload/api/system/qrcode/';
            $QrCodeService->create($text,$newFileName,$systemQrCodeDir);
            $systemQrCodePath = $systemQrCodeDir.$newFileName;
        }

        Db::startTrans();

        if($wechatModel->auto_qrcode_img_id){
            //将原二维码标注成已删除
            MemberImages::where('id',$wechatModel->auto_qrcode_img_id)->update(['delete_at'=>time()]);
        }

        //保存图片地址记录
        $imgId = $this->createImgInfo($userInfo['id'],3,$filePath,0,$wechatId,0,$systemQrCodePath,$text);
        //计算码优先级
        $wechatModel->auto_qrcode_img_id = $imgId;
        $wechatModel->save();
        Db::commit();

        return $imgId;
    }


    public function uploadOnceQrCode($userInfo)
    {

        $tmp_name = $_FILES['file']['tmp_name'];
        $uploadFileName = $_FILES['file']['name'];
        if(!isImage($tmp_name)){
            return ['code'=>ReturnCode::FILE_SAVE_ERROR,'msg'=>'请上传jpg,png,bmp格式文件','data'=>[]];
        }

        $path = '/upload/api/once_qrcode/';
        $newFileName = $this->saveFile($tmp_name,$path,$uploadFileName);
        $filePath = $path.$newFileName;
        $fullFilePath = $_SERVER['DOCUMENT_ROOT'].$path.$newFileName;


        $QrCodeService = new QrCodeService();
        $text = $QrCodeService->read($fullFilePath,1);

        if($text == false){
            @unlink($fullFilePath);//识别失败,删除原图
            return [
                'code'=>ReturnCode::FILE_SAVE_ERROR,
                'msg'=>'请上传正确图片!',
                'data'=>[]
            ];
        }

        //生成缩略图
        $thumbClass = Image::open($fullFilePath);
        // 按照原图的比例生成一个最大为150*150的缩略图并保存为thumb.jpg
        $thumbClass->thumb(250, 250,1)->save($fullFilePath);//直接把缩略图覆盖原图


        //生成新图片 QRcode
        $systemQrCodeDir = '/upload/api/system/qrcode/';
        $QrCodeService->create($text,$newFileName,$systemQrCodeDir);
        $systemQrCodePath = $systemQrCodeDir.$newFileName;



        Db::startTrans();
        //保存图片地址记录
        $imageModel = new MemberImages();
        $imageModel->type = 1;//1二维码2支付凭证3自动二维码
        $imageModel->wechat_id = 0;
        $imageModel->member_id = $userInfo['id'];
        $imageModel->img = $filePath;
        $imageModel->system_img = $systemQrCodePath;
        $imageModel->pay_qrcode_url = $text;
        $imageModel->channel_type = 5;//固定金额模式
        $imageModel->channel_data = $filePath;
        $imageModel->save();
        $imgId = $imageModel->id;

        Db::commit();

        return ['img_id'=>$imgId,'filePath'=>Request::instance()->domain().$systemQrCodePath];
    }



    public function getUploadFileError(){
        $error = $_FILES['file']['error'];
        //过滤错误
        if ($error) {
            switch ($error) {
                case 1 :
                    $error_message = '您上传的文件超过了PHP.INI配置文件中UPLOAD_MAX-FILESIZE的大小';
                    break;
                case 2 :
                    $error_message = '您上传的文件超过了PHP.INI配置文件中的post_max_size的大小';
                    break;
                case 3 :
                    $error_message = '文件只被部分上传';
                    break;
                case 4 :
                    $error_message = '文件不能为空';
                    break;
                default :
                    $error_message = '未知错误';
            }
            return $error_message;
        }

        return false;
    }


    /**
     * 上传返款凭证
     * @param $orderId
     * @param $userInfo
     * @return mixed
     * @throws \think\exception\DbException
     * @author
     * @date   2019/04/18 0018 11:42
     */
    public function uploadReturnImg($orderId,$userInfo)
    {
        $orderInfo = MerchantOrder::get($orderId);
        if(empty($orderInfo)){
            return [
                'code'=>ReturnCode::PARAM_INVALID,
                'msg'=>'订单号异常!',
                'data'=>[]
            ];
        }

        $hasError = $this->getUploadFileError();
        if($hasError){
            return ['code'=>ReturnCode::FILE_SAVE_ERROR,'msg'=>$hasError,'data'=>[]];
        }

        $tmp_name = $_FILES['file']['tmp_name'];
        if(!isImage($tmp_name)){
            return ['code'=>ReturnCode::FILE_SAVE_ERROR,'msg'=>'请上传jpg,png,bmp格式文件','data'=>[]];
        }


        $filePath = $this->uploadFile('return_img');
        if(!$filePath){
            return false;
        }

        $fullFilePath = $_SERVER['DOCUMENT_ROOT'].$filePath;

        //生成缩略图
        $thumbClass = Image::open($fullFilePath);
        //等比例缩放
        $thumbClass->thumb(500, 500,1)->save($fullFilePath);//直接把缩略图覆盖原图

        //保存图片地址记录
        $imgId = $this->createImgInfo($userInfo['id'],2,$filePath,$orderId);

        $orderInfo->return_money_img_id = $imgId;
        $orderInfo->return_time = time();
        $orderInfo->save();

        return true;
    }



    /**
     * 上传图片
     * @param string $dir 目录地址
     * @return mixed
     */
    public function uploadFile($dir = null) {
        $dir = $dir ?: 'uploadFile';
        $path = '/upload/api/'.$dir.'/'.date('Ymd', time()). '/';
        $name = $_FILES['file']['name'];
        $tmp_name = $_FILES['file']['tmp_name'];

        $newFileName = $this->saveFile($tmp_name,$path,$name);
        $filePath = $path.$newFileName;
        if($filePath){
            return $filePath;
        }
        return false;
    }

    /**
     * @param $tmpFullPath
     * @param $path
     * @param $name
     * @return mixed
     * @author
     * @date   2019/04/19 0019 11:36
     */
    public function saveFile( $tmpFullPath, $path, $name)
    {
        $arr_name = explode('.', $name);
        $hz = array_pop($arr_name);
        $new_name = date('YmdHis') .'_'. uniqid() . '.' . $hz;
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . $path, 0755, true);
        }
        if (move_uploaded_file($tmpFullPath, $_SERVER['DOCUMENT_ROOT'] . $path . $new_name)) {
            return $new_name;
        } else {
            return false;
        }
    }

    /**
     * 创建用户上传图片记录
     * @param int $memberId 用户ID
     * @param int $type 上传文件类型 : 1,收款码   2,返款凭证
     * @param int $orderId 订单主键ID
     * @param string $imgUrl 图片全路径地址
     * @param int $wechat_id 微信ID
     * @param float $money 待收款金额
     * @param string $systemQrCodeImgUrl 系统生成的二维码路径
     * @param string $payQrCodeUrl 第三方原始码地址
     * @return boolean
     * @author
     * @date   2019/3/27 14:18
     */
    public function createImgInfo(
        $memberId,
        $type,
        $imgUrl,
        $orderId = 0,
        $wechat_id = 0,
        $money = 0.00,
        $systemQrCodeImgUrl = '',
        $payQrCodeUrl = '',
        $account = '',
        $real_name = ''
    )
    {
        $imageModel = new MemberImages();
        $imageModel->type = $type;
        $imageModel->wechat_id = $wechat_id;
        $imageModel->member_id = $memberId;
        $imageModel->img = $imgUrl;
        $imageModel->money = $money;
        $imageModel->order_id = $orderId;
        $imageModel->system_img = $systemQrCodeImgUrl;
        $imageModel->pay_qrcode_url = $payQrCodeUrl;
        $imageModel->account = $account;
        $imageModel->real_name = $real_name;
        $imageModel->save();

        return $imageModel->id;
    }

    /**
     * 获取待收款信息
     * @param int $orderId
     * @return array
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/27 11:28
     */
    public function getDueInInfo(int $orderId)
    {
        $rowData = MerchantOrder::get($orderId);
        if(empty($rowData)){
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>'查询未命中',
                'data'=>[]
            ];
        }
        $data = [];
        $data['merchant_id'] = $rowData->merchant_id;
        $data['order_sn'] = $orderId;
        $data['merchant_sn'] = $rowData->merchant->uid;
        $data['merchant_order_sn'] = $rowData->merchant_order_sn;
        $channel_name = ChannelService::$channel_config[$rowData->merchant_order_channel]['name'];
        $data['channel_name'] = $channel_name;
        $data['channel_data'] = $rowData->merchant_order_extend;
        $data['create_time'] = $rowData->create_time;
        $data['get_money'] = $rowData->get_money;
        return [
            'code'=>ReturnCode::SUCCESS,
            'msg'=>'获取成功',
            'data'=>$data
        ];
    }

    /**
     * 确认收款
     * @param int $orderId
     * @param  float   $money
     * @param  int   $memberId
     * @return array
     * @throws \think\exception\DbException
     */
    public function confirmDueIn(int $orderId,$money,int $memberId)
    {
        $lockKey = 'confirmDueIn:'.$orderId;
        $socketLock = new Lock('redis',['namespace'=>'confirmDueIn']);
        $socketLock->get($lockKey);
        try{
            $orderInfo = MerchantOrder::find($orderId);

            if(empty($orderInfo) || $orderInfo->member_id != $memberId){
                abort(500,'参数错误');
            }

            if($orderInfo->get_money != $money){
                abort(500,'确认金额有误,请核对!');
            }

            $now = time();

            $memberService = new MemberService();
            $result = $memberService->confirm($orderId);
            if($result === false){
                abort(500,$memberService->getError());
            }

            $memberInfo = Member::find($memberId);

            $confirmLogData = [
                'order_id'=>$orderId,
                'member_id'=>$memberId,
                'ip'=> request()->ip(),
                'member_device_id'=>$memberInfo->member_device_id,
                'create_time'=>$now
            ];

            $addLog = MemberConfirmLog::create($confirmLogData);
            if(empty($addLog->id)){
                abort(500,'写入日志失败');
            }

            $socketLock->release($lockKey);
            return [
                'status'=>ReturnCode::SUCCESS,
                'msg'=>'收款成功!',
                'data'=>[]
            ];
        }catch (\Exception $e){
            $socketLock->release($lockKey);
            return [
                'status'=>ReturnCode::DB_SAVE_ERROR,
                'msg'=>$e->getMessage(),
                'data'=>[]
            ];
        }
    }

    /**
     * 获取订单列表
     * @param int $memberId
     * @param mixed $where
     * @param int $offset
     * @param int $length
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/27 15:10
     */
    public function getOrderListByStatus(int $memberId,$where = null,int $offset = 0,int $length = 20)
    {
        $model = MerchantOrder::where('member_id',$memberId);
        if ($where){
            $model->where($where);
        }
        $list = $model->limit($offset,$length)->order('create_time','desc')->select();

        if(empty($list)){
            return [];
        }

        $data = [];

        $merchantOrderModel = new MerchantOrder();
        foreach($list as $item){
            $row['id'] = $item->id;
            $row['merchant_id'] = $item->merchant_id;
            $row['order_sn'] = $item->id;
            $row['create_time'] = $item->create_time;
            $row['merchant_order_sn'] = $item->merchant_order_sn;
            $channel_name = '';
            if($item->merchant_order_channel){
                $channel_name = array_key_exists($item->merchant_order_channel,ChannelService::$channel_config) ? ChannelService::$channel_config[$item->merchant_order_channel]['name'] : '';
            }

            $groupName = '系统补单';//补单无图片ID
            if($item->get_money_qrcode_img_id){
                $imgInfo = MemberImages::find($item->get_money_qrcode_img_id);
                $groupInfo = MemberWechat::find($imgInfo->wechat_id);
                $groupName = empty($groupInfo) ? '分组已删除' : $groupInfo->title;
            }
            $row['channel_name'] = $channel_name;
            $row['channel_data'] = $item->merchant_order_extend;
            $row['get_money'] = $item->get_money;//支付金额
            $row['return_money'] = $item->return_money;
            $row['status_name'] = $item->orderStatus . ' - ['. $groupName  .']';

            $merchantOrderModel->appendButtonInfo($item,$row);
            $data[] = $row;
        }

        return $data;
    }


    /**
     * 修改密码
     * @param $memberId
     * @param $old
     * @param $new
     * @param $newConfirm
     * @return array
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/27 17:39
     */
    public function editPassword($memberId,$old,$new,$newConfirm)
    {
        if ($new !== $newConfirm){
            return ['code'=>ReturnCode::UPDATE_FAILED, 'msg'=>'新密码确认不一致!','data'=>[]];
        }

        $userInfo = Member::get($memberId);
        $oldPassword = Tools::userMd5($old);
        $currentPassword = $userInfo->password;
        if($oldPassword != $currentPassword){
            return ['code'=>ReturnCode::UPDATE_FAILED, 'msg'=>'旧密码错误!','data'=>[]];
        }

        $newPassword = Tools::userMd5($new);
        if($oldPassword == $newPassword){
            return ['code'=>ReturnCode::UPDATE_FAILED, 'msg'=>'新旧密码不能重复!','data'=>[]];
        }
        $userInfo->password = $newPassword;
        //恢复安全级别
        $userInfo->safe_status = 1;

        $save = $userInfo->save();
        if($save){
            $this->logout($memberId);
        }
        return [];
    }

    public function getRefundInfo(int $orderId)
    {
        $rowData = MerchantOrder::get($orderId);

        $data = [];
        $data['id'] = $rowData->id;
        $data['order_sn'] = $rowData->id;
        $data['create_time'] = $rowData->create_time;
        $data['start_money'] = $rowData->start_money;
        $data['return_money'] = $rowData->return_money;

        $agentId = $rowData->agent_id;//代理商ID
        $bankData = BankCard::where('type',2)->where('uid',$agentId)->where('status',1)->find();

        $bankName = '';
        $bankCard = '';
        $ownerName = '';
        if(!empty($bankData)){
            $bankName = $bankData->bank_name;
            $bankCard = $bankData->card;
            $ownerName = $bankData->name;
        }

        $data['bank_name'] = $bankName;//开户行
        $data['card'] = $bankCard;    //银行卡号
        $data['card_owner'] = $ownerName;//开户人

        return $data;
    }


    /**
     * 确认返款
     * @param int $orderId
     * @param int $memberId
     * @return array|bool
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/28 10:17
     */
    public function confirmRefund(int $orderId,int $memberId)
    {
        $orderInfo = MerchantOrder::get($orderId);

        if($orderInfo->member_id != $memberId){
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>'订单ID参数错误!',
                'data'=>[]
            ];
        }

        //是否需要上传返款凭证
        $hasRefundImg = config( 'has_refund_img' );
        if($hasRefundImg && empty($orderInfo->return_money_img_id)){
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>'请上传返款凭证',
                'data'=>[]
            ];
        }

        //验证
        switch ($orderInfo->status) {
            case 3://正常状态
                break;
            case 4:
                return [
                    'code'=>ReturnCode::UPDATE_FAILED,
                    'msg'=>'订单已返款,请勿重复操作!',
                    'data'=>[]
                ];
            default:
                return [
                    'code'=>ReturnCode::UPDATE_FAILED,
                    'msg'=>'订单状态异常!',
                    'data'=>[]
                ];
        }

        //验证
        if ($orderInfo->pay_status != 2) {
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>'订单支付状态异常!',
                'data'=>[]
            ];
        }

        $data = ['status'=>4,'return_time'=>time()];
        $update = MerchantOrder::where('id',$orderId)
                               ->where('status',3)
                               ->where('pay_status',2)
                               ->update($data);

        if($update){
           return true;
        }

        return [
            'code'=>ReturnCode::UPDATE_FAILED,
            'msg'=>'更新失败,请重试!',
            'data'=>[]
        ];
    }

    /**
     * 公告列表
     * @param string $role 角色名称
     * @param int    $offset
     * @param int    $length
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author
     * @date   2019/3/29 10:43
     */
    public function getNoticeList($role = 'member',int $offset = 0,int $length = 20)
    {

        switch ($role){
            case 'member':
                $roleStatus = 'member_status';
                break;
            case 'merchant':
                $roleStatus = 'merchant_status';
                break;
            case 'agent':
                $roleStatus = 'agent_status';
                break;
            default:
                return [];
        }

        $model = Notice::where($roleStatus,1);

        $list = $model->limit($offset,$length)
                      ->order('is_top')
                      ->order('create_time','desc')
                      ->select();

        if(empty($list)){
            return [];
        }

        $data = [];
        foreach($list as $item){
            $row['id'] = $item->id;
            $row['title'] = $item->title;
            $row['content'] = $item->content;
            $row['create_time'] = $item->create_time;
            $row['is_top'] = $item->is_top;
            $data[] = $row;
        }

        return $data;
    }

    /**
     * 微信账号列表
     * @param int $member_id 用户ID
     * @return array
     * @author tgq
     * @date   2019/3/29 10:43
     */
    public function getWechatList(int $member_id,int $offset = 0,int $length = 20){
        $model = MemberWechat::where('member_id',$member_id)->where('delete_at',0);

        $list = $model->limit($offset,$length)
            ->order('create_time','desc')
            ->select();
        if(empty($list)){
            return [];
        }
        $data = [];
        foreach($list as $item){
            $row['id'] = $item->id;
            $row['title'] = $item->title;
            $row['create_time'] = $item->create_time;
            $row['status'] = $item->status;
            $data[] = $row;
        }
        return $data;
    }

    /**
     * 添加会员微信账号
     * @param int $member_id 用户ID
     * @param int    $status 状态
     * @param string    $title 标题
     * @param string    $desc 标题
     * @return array
     * @author tgq
     * @date   2019/3/29 10:43
     */
    public function createWechat(int $member_id,string $title,$desc =''){
        $data = [
            'status'        => 0,
            'member_id'     => $member_id,
            'title'         => $title,
            'desc'         => $desc,
            'create_time'   => time()
        ];
        $res = MemberWechat::create($data);
        if ($res === false) {
            return [
                'code'=>ReturnCode::DB_SAVE_ERROR,
                'msg'=>'添加失败,请重试!',
                'data'=>[]
            ];
        } else {
            return [];
        }
    }
    /**
     * 删除会员账号分组
     * @param int $member_id 会员ID
     * @param int $id 微信账号ID
     * @return array
     * @date   2019/3/29 10:43
     */
    public function getDelWechat($member_id,$id){
        $res = MemberWechat::find($id);
        $member_ids = $res->member_id;
        if($member_ids != $member_id){
            return [
                'code'=>ReturnCode::DELETE_FAILED,
                'msg'=>'删除参数错误',
                'data'=>[]
            ];
        }
        if($res->status == 1){
            return [
                'code'=>ReturnCode::DELETE_FAILED,
                'msg'=>'激活的微信账号无法删除',
                'data'=>[]
            ];
        }

        $now = time();
        Db::startTrans();
        $res->delete_at = $now;
        $return = $res->save();

        if($res->auto_qrcode_img_id){
            MemberImages::where('id',$res->auto_qrcode_img_id)->update(['delete_at'=>$now]);
        }

        if ($return === false) {
            Db::rollback();
            return [
                'code'=>ReturnCode::DELETE_FAILED,
                'msg'=>'删除失败,请重试!',
                'data'=>[]
            ];
        } else {
            Db::commit();
            return [];
        }
    }

    /**
     * 获取自动二维码
     * @param $member_id
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author
     * @date   2019/04/15 0015 17:09
     */
    public function getAutoQrcode($member_id,$id){
        $imgId = MemberWechat::where('id',$id)
                           ->where('member_id',$member_id)
                           ->value('auto_qrcode_img_id');
        if (empty($imgId)) {
            return [
                'code'=>ReturnCode::EMPTY_PARAMS,
                'msg'=>'参数异常!',
                'data'=>[]
            ];
        }
        $imgInfo = MemberImages::find($imgId);
        $data = [];
        if($imgInfo){
            switch ($imgInfo->channel_type){
                case 1: //自动二维码
                    $data = [
                        'type' => $imgInfo->channel_type,
                        'id' => $imgInfo->id,
                        'url' => Request::instance()->domain().$imgInfo->img,
                    ];
                    break;
                case 2: //转账信息
                    $data = [
                        'type' => $imgInfo->channel_type,
                        'id' => $imgInfo->id,
                        'account' => $imgInfo->account,
                        'real_name' => $imgInfo->real_name,
                    ];
                    break;
                case 3: //银行卡
                    $data = [
                        'type' => $imgInfo->channel_type,
                        'id' => $imgInfo->id,
                        'bank_card' => $imgInfo->bank_card,
                        'bank_name' => $imgInfo->bank_name,
                        'bank_account' => $imgInfo->bank_account,
                        'bank_desc' => $imgInfo->bank_desc,
                    ];
                    break;
            }
        }
        return $data;
    }

    /**
     * 激活微信账号
     * @param int $member_id 会员ID
     * @param int $id 微信账号ID
     * @return array
     * @author tgq
     * @date   2019/3/29 10:43
     */
    public function usedWechat($member_id,$id,$status = 1){

        $res = MemberWechat::find($id);
        $memberId = $res->member_id;
        if($memberId != $member_id){
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>'更新参数错误',
                'data'=>[]
            ];
        }
        $memberInfo = Member::find($member_id);

        //检测自动模式开启下, 激活分组内是否无图
        if($status == 1){
            if(empty($res->auto_qrcode_img_id)){
                return [
                    'code'=>ReturnCode::UPDATE_FAILED,
                    'msg'=>'此分组未添加二维码,请添加后再激活!',
                    'data'=>[]
                ];
            }

            $imgInfo = MemberImages::find($res->auto_qrcode_img_id);

            if($imgInfo->channel_type == 1){
                //追加码到队列中
                $check = ImageService::checkImageEmpty($res->auto_qrcode_img_id);
                if($check == false){
                    return [
                        'code'=>ReturnCode::UPDATE_FAILED,
                        'msg'=>'激活失败,此码连续空单次数过多!',
                        'data'=>[]
                    ];
                }
            }


            //配置允许多分组接单
            $config = config('allow_more_qrcode_get_order');
            if(empty($config)){
                $count = MemberWechat::where('member_id',$member_id)->where('status',1)->count();
                if($count > 0){
                    return [
                        'code'=>ReturnCode::UPDATE_FAILED,
                        'msg'=>'系统禁止多账户同时接单,请禁用其他分组后,激活此分组',
                        'data'=>[]
                    ];
                }
            }else{
                if($memberInfo->is_receipt == 1){
                    $client = new SwooleClientService();
                    $params = [
                        'addQrcodeId'=>$res->auto_qrcode_img_id,
                        'memberId'=>$member_id,
                        'agentId'=>$memberInfo->agent_id
                    ];

                    $package = $client->package('addQrcode',$params);
                    $client->push($package);
                }
            }
        }
        //取消激活状态
        else{

            $config = config('allow_more_qrcode_get_order');
            if(empty($config)){
                if($memberInfo->is_receipt == 1){
                    return [
                        'code'=>ReturnCode::UPDATE_FAILED,
                        'msg'=>'请停工后再修改激活状态!',
                        'data'=>[]
                    ];
                }
            }else{
                if($memberInfo->is_receipt == 1){
                    $client = new SwooleClientService();
                    $params = [
                        'qrcodeId'=>$res->auto_qrcode_img_id,
                        'agentId'=>$memberInfo->agent_id
                    ];

                    $package = $client->package('delQrcode',$params);
                    $client->push($package);
                }
            }
        }

        Db::startTrans();
        $res->status = $status;
        $res->save();
        $score = ImageService::getScore($res->auto_qrcode_img_id);
        MemberImages::where('id',$res->auto_qrcode_img_id)->update(['score'=>$score]);

        if ($res === false) {
            Db::rollback();
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>'更新失败,请重试!',
                'data'=>[]
            ];
        } else {
            Db::commit();
            return [];
        }
    }


    /**
     * @param string $imgId 图片表主键ID
     * @param MerchantOrder $orderInfo
     * @return mixed
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author
     * @date   2019/4/4 15:43
     */
    public function updateImgAfter($imgId,$orderInfo)
    {
        $imgInfo = MemberImages::find($imgId);
        $imgUrl = data_get($imgInfo,'pay_qrcode_url','');
        //计算出还款金额
        $moneyService = new MoneyService();
        $arr = $moneyService->reckonMoney($orderInfo->get_money,$orderInfo->merchant_id,$orderInfo->agent_id,$orderInfo->member_id);
        $orderInfo->get_money_qrcode_img_id = $imgId;
        $orderInfo->pay_qrcode_url = $imgUrl;
        $orderInfo->return_money = $arr['return_money'];
        $orderInfo->member_fee_money = $arr['member_fee_money'];
        $orderInfo->agent_fee_money = $arr['agent_fee_money'];
        $orderInfo->platform_fee_money = $arr['platform_fee_money'];
        $orderInfo->merchant_money = $arr['merchant_money'];
        $orderInfo->upload_time = time();
        $orderInfo->status = 2;
        $orderInfo->save();
    }

    public function getImgUrlById($id)
    {
        $imgInfo = MemberImages::find($id);
        if($imgInfo){
            return $imgInfo->img;
        }
        return false;
    }

    public function getStatus($member_id)
    {
        //获取用户排队rank状态
        $memberInfo = Member::find($member_id);

        $rank = false;
        if($memberInfo->is_receipt == 1){
            $rank = true;
        }

        return [
            'isRank' => $rank
        ];
    }


    public function addAlipayAppId($memberId,$groupId,$appId)
    {
        if(empty($memberId) || empty($groupId) || empty($appId)){
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>'params error!',
                'data'=>[]
            ];
        }

        Db::startTrans();
        try{

            $fileName = uniqid(mt_rand(0,1000), true).'.png';
            $systemQrCodeDir = '/upload/api/system/qrcode/';
            $systemQrCodePath = $systemQrCodeDir.$fileName;

            //生成新图片 QRcode
            $imgInfo = $this->createImgByAppId($appId,0.1,'样例',$fileName,$systemQrCodeDir);


            $imageModel = new MemberImages();
            $imageModel->type = 3;//1二维码2支付凭证3自动二维码
            $imageModel->wechat_id = $groupId;
            $imageModel->member_id = $memberId;
            $imageModel->img = $systemQrCodePath;
            $imageModel->system_img = $systemQrCodePath;
            $imageModel->pay_qrcode_url = $imgInfo['text'];
            $imageModel->channel_type = 2;
            $imageModel->channel_data = $appId;
            $imageModel->save();
            $imgId = $imageModel->id;

            $updateData = [
                'auto_qrcode_img_id'=>$imgId
            ];
            MemberWechat::where('id',$groupId)->where('member_id',$memberId)->update($updateData);

            Db::commit();
            return [];
        }catch(\Exception $e){
            Db::rollback();
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>$e->getMessage(),
                'data'=>[]
            ];
        }

    }

    public function addQrcodeByUrl($memberId,$groupId,$url)
    {
        if(empty($memberId) || empty($groupId) || empty($url)){
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>'params error!',
                'data'=>[]
            ];
        }


        Db::startTrans();
        try{

            $fileName = uniqid(mt_rand(0,1000), true).'.png';
            $systemQrCodeDir = '/upload/api/system/qrcode/';
            $systemQrCodePath = $systemQrCodeDir.$fileName;

            $QrCodeService = new QrCodeService();

            //生成新图片 QRcode
            $QrCodeService->create($url,$fileName,$systemQrCodeDir);

            $imageModel = new MemberImages();
            $imageModel->type = 3;//1二维码2支付凭证3自动二维码
            $imageModel->wechat_id = $groupId;
            $imageModel->member_id = $memberId;
            $imageModel->img = $systemQrCodePath;
            $imageModel->system_img = $systemQrCodePath;
            $imageModel->pay_qrcode_url = $url;
            $imageModel->channel_type = 1;
            $imageModel->channel_data = $url;
            $imageModel->save();
            $imgId = $imageModel->id;

            $updateData = [
                'auto_qrcode_img_id'=>$imgId
            ];
            MemberWechat::where('id',$groupId)->where('member_id',$memberId)->update($updateData);

            Db::commit();
            return [];
        }catch(\Exception $e){
            Db::rollback();
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>$e->getMessage(),
                'data'=>[]
            ];
        }

    }



    public function createWechatImgById($orderId)
    {
        $codeUrl = env('host').'/payapi/Index/wechat_qrcode/?id='.$orderId;
        $QrCodeService = new QrCodeService();

        //生成新图片 QRcode
        $imgUri = $QrCodeService->create($codeUrl);

        return ['imgUri'=>$imgUri,'text'=>$codeUrl];
    }

    public function createAlipayImgById($orderId)
    {
        $orderInfo = MerchantOrder::find($orderId);

//        $codeUrl = env('host'.'/payapi/Index/wechat_qrcode/'.$orderId);
        $url = 'alipays://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode=https://render.alipay.com/p/h5/shebei/index.html?ct=ZJXL&__webview_options__=transparentTitle%3Dalways';
        $codeUrl = 'alipays://platformapi/startapp?appId=20000123&actionType=scan&biz_data=';  //固码二维码参数url

        $QrCodeService = new QrCodeService();

        //生成新图片 QRcode
        $imgUri = $QrCodeService->create($codeUrl);

        return ['imgUri'=>$imgUri,'text'=>$codeUrl];
    }

    public function createImgByAppId($appId,$money= '0.1',$remark = '测试码',$fileName = null,$path = null)
    {
        $data = [];
        $data['s'] = 'money'; //支付宝参数: 支付
        $data['u'] = $appId;  //appid
        $data['a'] = $money;  //支付金额  固码模式
        $data['m'] = $remark; //备注
        $data = json_encode($data);
        $codeUrl = 'alipays://platformapi/startapp?appId=20000123&actionType=scan&biz_data='.$data;  //固码二维码参数url

        $QrCodeService = new QrCodeService();

        //生成新图片 QRcode
        $imgUri = $QrCodeService->create($codeUrl,$fileName,$path);

        return ['imgUri'=>$imgUri,'text'=>$codeUrl];
    }

    public function getChannelInfo($imgInfo,$orderInfo)
    {
        $host = env('host','http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER["SERVER_PORT"]);
        switch ($imgInfo->channel_type){
            case 1://自动二维码
                //获取自动码
                $text = $imgInfo->pay_qrcode_url;
                $imgUrl = $host.$imgInfo->system_img;
                return ['imgUrl'=>$imgUrl,'text'=>$text];
            case 2://账户,真实姓名转账
                $account = $imgInfo->account;
                $realName = $imgInfo->real_name;
                return ['account'=>$account,'real_name'=>$realName];

                /*$imgInfo = $this->createImgByAppId($imgInfo->channel_data,$orderInfo->get_money,$orderInfo->merchant_order_sn);
                $text = $imgInfo['text'];
                $imgUrl = $imgInfo['imgUri'];//base64
                return ['imgUrl'=>$imgUrl,'text'=>$text];*/
            case 3://银行卡转卡
                //判断是否是支付宝转银行卡，是的话带上订单ID
                //重新生成新的二维码
                // 改成直接扫码转账
                /*$url = 'alipays://platformapi/startapp?appId=20000067&url=';
                $text = $imgInfo->pay_qrcode_url.'?id='.$this->orderId;
                $QrCodeService = new QrCodeService();
                $imgUrl = $QrCodeService->create($text);*/
                if(in_array($orderInfo->merchant_order_channel,['alipay_card','wechat_card','union_card'])){
                    $bankCard = $imgInfo->bank_card;
                    $bankAccount = $imgInfo->bank_account;
                    $bankName = $imgInfo->bank_name;
                    $bankDesc = $imgInfo->bank_desc;
                    return [
                        'text'=>$bankCard,
                        'bank_card'=>$bankCard,
                        'bank_account'=>$bankAccount,
                        'bank_name'=>$bankName,
                        'bank_desc'=>$bankDesc,
                    ];
                }
                return false;
            case 4://手机号
                break;
            case 5://固定金额模式
                $text = $imgInfo->pay_qrcode_url;
                $imgUrl = $host.$imgInfo->system_img;
                return ['imgUrl'=>$imgUrl,'text'=>$text];
        }


        return false;
    }


    public function addBankCard( $userInfo, $groupId, $bankCard, $bankName, $bankAccount, $bankDesc)
    {

        $wechatModel = MemberWechat::where('id',$groupId)
                                   ->where('member_id',$userInfo['id'])
                                   ->find();
        //验证微信ID是否匹配
        if(!$wechatModel){
            return false;
        }

        $memberInfo = Member::find($userInfo['id']);

        //有码,且分组处于激活状态, 禁止覆盖上传二维码
        if($wechatModel->auto_qrcode_img_id){
            if($memberInfo->is_receipt == 1 && $wechatModel->status == 1){
                return ['code'=>ReturnCode::FILE_SAVE_ERROR,'msg'=>'请停工后再替换原账号信息','data'=>[]];
            }
        }

        $lock = new Lock('redis');

        $lockKey = 'addBankCard:'.$userInfo['id'];
        $lock->get($lockKey,15);

        $exists = MemberImages::where('member_id',$userInfo['id'])->where('channel_type',3)->where('bank_card',$bankCard)->where('delete_at',0)->find();
        if($exists){
            $lock->release($lockKey);
            return ['code'=>ReturnCode::FILE_SAVE_ERROR,'msg'=>'银行卡信息已存在','data'=>[]];
        }

        Db::startTrans();
        try{
            if($wechatModel->auto_qrcode_img_id){
                //将原二维码标注成已删除
                MemberImages::where('id',$wechatModel->auto_qrcode_img_id)->update(['delete_at'=>time()]);
            }

            $imageModel = new MemberImages();
            $imageModel->type = 3;//1二维码2支付凭证3自动二维码
            $imageModel->wechat_id = $groupId;
            $imageModel->member_id = $userInfo['id'];
            $imageModel->img = '';
            $imageModel->system_img = '';
            $imageModel->pay_qrcode_url = '';
            $imageModel->channel_type = 3;
            $imageModel->channel_data = '';
            $imageModel->bank_card = $bankCard;//卡号
            $imageModel->bank_name = $bankName;//银行名称
            $imageModel->bank_account = $bankAccount;//开户人
            $imageModel->bank_desc = $bankDesc;//开户行
            $imageModel->save();
            $imgId = $imageModel->id;

            $updateData = [
                'auto_qrcode_img_id'=>$imgId
            ];

            MemberWechat::where('id',$groupId)->where('member_id',$userInfo['id'])->update($updateData);

            Db::commit();
            $lock->release($lockKey);
            return ['id'=>$imgId];
        }catch(\Exception $e){
            Db::rollback();
            $lock->release($lockKey);
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>$e->getMessage(),
                'data'=>[]
            ];
        }
    }


    public function addAccount( $userInfo, $groupId, $account,$real_name)
    {

        $wechatModel = MemberWechat::where('id',$groupId)
                                   ->where('member_id',$userInfo['id'])
                                   ->find();
        //验证微信ID是否匹配
        if(!$wechatModel){
            return false;
        }

        $memberInfo = Member::find($userInfo['id']);

        //有码,且分组处于激活状态, 禁止覆盖上传二维码
        if($wechatModel->auto_qrcode_img_id){
            if($memberInfo->is_receipt == 1 && $wechatModel->status == 1){
                return ['code'=>ReturnCode::FILE_SAVE_ERROR,'msg'=>'请停工后再替换原账号信息','data'=>[]];
            }
        }

        $lock = new Lock('redis');

        $lockKey = 'addAccount:'.$userInfo['id'];
        $lock->get($lockKey,15);

        $exists = MemberImages::where('member_id',$userInfo['id'])->where('channel_type',3)->where('account',$account)->where('delete_at',0)->find();
        if($exists){
            $lock->release($lockKey);
            return ['code'=>ReturnCode::FILE_SAVE_ERROR,'msg'=>'账号信息已存在','data'=>[]];
        }

        Db::startTrans();
        try{

            if($wechatModel->auto_qrcode_img_id){
                //将原二维码标注成已删除
                MemberImages::where('id',$wechatModel->auto_qrcode_img_id)->update(['delete_at'=>time()]);
            }

            $imageModel = new MemberImages();
            $imageModel->type = 3;//1二维码2支付凭证3自动二维码
            $imageModel->wechat_id = $groupId;
            $imageModel->member_id = $userInfo['id'];
            $imageModel->img = '';
            $imageModel->system_img = '';
            $imageModel->pay_qrcode_url = '';
            $imageModel->channel_type = 2;
            $imageModel->channel_data = '';
            $imageModel->account = $account;//账号
            $imageModel->real_name = $real_name;//真实验证姓名
            $imageModel->save();
            $imgId = $imageModel->id;

            $updateData = [
                'auto_qrcode_img_id'=>$imgId
            ];

            MemberWechat::where('id',$groupId)->where('member_id',$userInfo['id'])->update($updateData);

            Db::commit();
            $lock->release($lockKey);
            return ['id'=>$imgId];
        }catch(\Exception $e){
            Db::rollback();
            $lock->release($lockKey);
            return [
                'code'=>ReturnCode::UPDATE_FAILED,
                'msg'=>$e->getMessage(),
                'data'=>[]
            ];
        }
    }

}
