<?php

namespace app\admin\controller;


use app\api\swoole\PayService;
use app\api\swoole\RedisService;
use app\model\AdminUserData;
use app\model\Agent;
use app\model\MemberImages;
use app\model\SettlementTask;
use app\util\ReturnCode;
use app\model\MerchantOrder ;
use app\model\Member as MemberModel;

class Index extends Base {
    //首页
    public function index() {
        //未结算余额
        //$userInfo = $this->userInfo;
        //$platform = db('platform')->where(['id'=>1])->find();
        //$data['balance'] = $platform['money'];//平台可提现余额
        $data['balance'] = db('merchant_order')->whereTime('create_time', 'today')->where(['pay_status'=>2])->sum('platform_fee_money');//当日平台可提现余额
        $data['today_order_num'] = db('merchant_order')->whereTime('create_time', 'today')->count('id');//今日订单数
        $data['today_no_order_num'] = db('merchant_order')->where(['pay_status'=>1])->whereTime('create_time', 'today')->count('id');//今日未支付订单数
        $data['today_over_order_num'] = db('merchant_order')->where(['pay_status'=>2])->whereTime('create_time', 'today')->count('id');//今日完成订单数
        $data['today_order_money'] = sprintf("%.2f",db('merchant_order')->whereTime('create_time', 'today')->sum('start_money'));//今日订单金额
        $data['today_over_order_money'] = sprintf("%.2f",db('merchant_order')->where(['pay_status'=>2])->whereTime('create_time', 'today')->sum('start_money'));//今日完成金额
        $data['order_num'] = db('merchant_order')->count();//订单总数
        $data['no_order_num'] = db('merchant_order')->where(['pay_status'=>1])->count();//未支付订单总数
        $data['over_order_num'] = db('merchant_order')->where(['pay_status'=>2])->count();//订单完成总数
        $data['order_money'] = sprintf("%.2f",db('merchant_order')->sum('start_money'));//订单总金额
        $startMoney = db('merchant_order')->where(['pay_status'=>2])->sum('start_money');
        $data['over_order_money'] = sprintf("%.2f",$startMoney);//订单完成总金额
        $data['agent_num'] = db('agent')->where(['parent_id'=>0])->count('id');//代理商个数
        $data['merchant_num'] = db('merchant')->where(['parent_id'=>0])->count('id');//商户个数
        $data['member_num'] = db('member')->count('id');//会员个数
        $data['merchant_withdraw_num'] = db('merchant_withdraw_audit')->where(['type'=>1])->count('id');//商户未处理提现个数
        $memberReceiptCount = PayService::getQrcodeListCount();
        $data['receipt_num'] = $memberReceiptCount;//当前接单人数
        if($data['order_num'] ==0){
            $data['turnover_rate']='100%';
        }else{
            $data['turnover_rate'] = (sprintf("%.2f",$data['over_order_num']/$data['order_num'])*100).'%';
        }
        //昨日统计
        $data['yesterday_order_num'] = db('merchant_order')->whereTime('create_time', 'yesterday')->count('id');//今日订单数
        $data['yesterday_over_order_num'] = db('merchant_order')->where(['pay_status'=>2])->whereTime('create_time', 'yesterday')->count('id');//今日完成订单数
        $data['yesterday_order_money'] = sprintf("%.2f",db('merchant_order')->whereTime('create_time', 'yesterday')->sum('start_money'));//今日订单金额
        $data['yesterday_over_order_money'] = sprintf("%.2f",db('merchant_order')->where(['pay_status'=>2])->whereTime('create_time', 'yesterday')->sum('start_money'));//今日完成金额
        return json(['code' => '1', 'msg' => '请求成功!', 'data' => $data]);
    }

    /**
     * 验证代理账目是否正确
     */
    public function checkAgentMoney()
    {
        $fields = [
            'id',
            'nickname',
            'mobile',
            'poundage_ratio',
            'settlement_money'
        ];
        $agentList = Agent::field($fields)->where('parent_id',0)->select();

        $data = [];
        foreach ($agentList as $agentInfo){
            $agentId = $agentInfo->id;
            $countMoney = MerchantOrder::where('agent_id',$agentId)
                                       ->where('pay_status',2)
                                       ->sum('get_money');

            $countTaskMoney = SettlementTask::where('agent_id',$agentId)
                                            ->whereIn('status',[1,2])
                                            ->sum('settlement_money');

            $balance = bcmul($countMoney,$agentInfo->poundage_ratio/100,2);

            $settleMoney = bcsub(bcsub($countMoney, $countTaskMoney,2), $balance,2);

            $diff = bcsub($agentInfo->settlement_money,$settleMoney,2);

            if($diff > 10 or $diff < -10 ){
                $data[] = [
                    'id' => $agentInfo->id,
                    '名称' => $agentInfo->nickname,
                    '总量' => $countMoney,
                    '下发总量' => $countTaskMoney,
                    '佣金' => $balance,
                    '预期余额' => $settleMoney,
                    '实际余额' => $agentInfo->settlement_money,
                    '差异' => $diff
                ];
            }
        }

        return json( [ 'code' => '200', 'msg' => '差异账户信息', 'data' => $data ] );
    }
    //假首页
    public function homePage(){
        $data['nickname'] = $this->userInfo->nickname;
        $data['username'] = $this->userInfo->username;
        $data['regTime'] = date('Y-m-d H:i:s',$this->userInfo->regTime);
        $data['loginTimes'] = $this->userInfo->login_times;
        $data['lastLoginIp'] = $this->userInfo->last_login_ip;
        $data['lastLoginTime'] = date('Y-m-d H:i:s',$this->userInfo->last_login_time);
        $data['ip'] = $this->request->ip();
        return json(['code' => '1', 'msg' => '请求成功,', 'data' => $data]);
    }

    public function upload() {
        $path = '/upload/admin/' . date('Ymd', time()) . '/';
        $name = $_FILES['file']['name'];
        $tmp_name = $_FILES['file']['tmp_name'];
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
            die($error_message);
        }
        $arr_name = explode('.', $name);
        $hz = array_pop($arr_name);
        $new_name = md5(time() . uniqid()) . '.' . $hz;
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . $path, 0755, true);
        }
        if (move_uploaded_file($tmp_name, $_SERVER['DOCUMENT_ROOT'] . $path . $new_name)) {
            return $this->buildSuccess([
                'fileName' => $new_name,
                'filePath'  => $path . $new_name,
                'fileUrl'  => $this->request->domain() . $path . $new_name
            ]);
        } else {
            return $this->buildFailed(ReturnCode::FILE_SAVE_ERROR, '文件上传失败');
        }
    }
    public function getImgUrlById()
    {
        $id = $this->request->get('id/d', '');
        if(empty($id)){
            return $this->buildFailed(ReturnCode::FILE_SAVE_ERROR, '参数缺失');
        }

        $imgInfo = MemberImages::find($id);
        if(empty($imgInfo)){
            return $this->buildFailed(ReturnCode::FILE_SAVE_ERROR, '获取图片失败');
        }

        $data = [];
        //收款码
        if($imgInfo->type == 3){
            $data = [
                'type'=>$imgInfo->channel_type,
            ];
            switch ($imgInfo->channel_type){
                case 2://账户,真实姓名转账
                    $data['type_name'] = '账号收款';
                    $data['account'] = $imgInfo->account;
                    $data['real_name'] = $imgInfo->real_name;
                    break;
                case 3://银行卡转卡
                    $data['type_name'] = '银行卡收款';
                    $data['bank_card'] = $imgInfo->bank_card;
                    $data['bank_name'] = $imgInfo->bank_name;
                    $data['bank_account'] = $imgInfo->bank_account;
                    $data['bank_desc'] = $imgInfo->bank_desc;
                    break;
                case 4://手机号
                    $data['type_name'] = '手机号收款';
                    $data['mobile'] = $imgInfo->account;
                    break;
                case 1://自动二维码
                default:
                    $data['type_name'] = '二维码收款';
                    $data['imgUrl'] = $imgInfo->img;
                    $data['pay_qrcode_url'] = $imgInfo->pay_qrcode_url;
            }
        }
        //返款凭证 2
        else{
            $data['type_name'] = '返款凭证';
            $data['imgUrl'] = $imgInfo->img;
        }

        return $this->buildSuccess($data);
    }
}
