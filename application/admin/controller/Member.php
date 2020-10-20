<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/11 0011
 * Time: 16:53
 */

namespace app\admin\controller;

use app\agent\service\MemberService as AgentMemberService;
use app\admin\service\MemberService as AdminMemberService;
use app\agent\service\MoneyService;
use app\api\service\AppApiService;
use app\api\swoole\PayService;
use app\model\Agent;
use app\util\ReturnCode;
use app\model\Member as MemberModel;
use app\model\MemberImages as MemberImagesModel;
use think\Cache;
use think\Db;
use think\Exception;


class Member extends Base
{
    public function index()
    {
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $keywords = $this->request->get('keywords', 1);
        $is_receipt = $this->request->get('is_receipt', '');
        $uid = $this->request->get('uid', '');
        $agentMobile = $this->request->get('agent_mobile', '');
        $where = [];

        if ($is_receipt === '1' || $is_receipt === '2') {
            $where['is_receipt'] = $is_receipt;
        }

        if ($agentMobile) {
            $agentInfo = Agent::field('id')->where('mobile',$agentMobile)->find();
            if(!$agentInfo){
                return $this->buildSuccess([
                    'list'  => [],
                    'count' => 0
                ]);
            }

            $where['agent_id'] = $agentInfo->id;
        }

        if ($uid) {
            $where['uid'] = $uid;
        }
        if ($keywords) {
            $where['mobile|nickname'] = ['like', "%{$keywords}%"];
        }
        $listObj = (new MemberModel())->where($where)->order('id', 'DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $memberService = new AdminMemberService();
        foreach ($listObj['data'] as $key => &$val) {
            $val['last_login_time'] = date('Y-m-d H:i:s', $val['last_login_time']);
            $val['agent_mobile'] = db('agent')->where(['id' => $val['agent_id']])->value('mobile');

            $yesterdayRateData = $memberService->getMemberYesterdayTurnoverRate($val['id']);
            $allRateData = $memberService->getMemberTurnoverRate($val['id']);
            $todayRateData = $memberService->getMemberTodayTurnoverRate($val['id']);

            $val['yesterdayRate'] = $yesterdayRateData['successOrderNum'].'/'.$yesterdayRateData['allOrderNum'].':'.$yesterdayRateData['rate'];
            $val['todayRate'] = $todayRateData['successOrderNum'].'/'.$todayRateData['allOrderNum'].':'.$todayRateData['rate'];
            $val['allRate'] = $allRateData['successOrderNum'].'/'.$allRateData['allOrderNum'].':'.$allRateData['rate'];
        }
        return $this->buildSuccess([
            'list' => $listObj['data'],
            'count' => $listObj['total']
        ]);
    }
    /**
     * 新增会员
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    /* public function add(){
         $postData = $this->request->post();
         //参数验证
         $validate = new Member();
         $result = $validate->scene('add')->check($postData);
         if ($result !== true) {
             return $this->buildFailed(ReturnCode::PARAM_INVALID, $validate->getError());
         }
         if(db('agent')->where(['mobile'=>$postData['agent_mobile']])->count('id')==0){
             return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '代理商不存在');
         }
         if(db('memeber')->where(['mobile'=>$postData['mobile']])->count('id')>0){
             return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '手机号已存在');
         }
         if(db('member')->where(['mobile'=>$postData['nickname']])->count('id')>0){
             return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '昵称已存在');
         }
         $postData['last_login_time'] = $postData['create_time'] = time();
         $postData['last_login_ip'] = request()->ip();
         $postData['agent_id'] = db('agent')->where(['mobile'=>$postData['agent_mobile']])->value('id');
         $postData['password'] = Tools::userMd5($postData['password']);
         unset($postData['agent_mobile']);
         //UID随机增长
         $max_uid = MemberModel::max('uid');
         if($max_uid == ""){
             $max_uid = 100000;
         }
         $postData['uid'] = $max_uid + mt_rand(66, 255);
         $res = MemberModel::create($postData);
         if ($res === false) {
             return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
         } else {
             return $this->buildSuccess([]);
         }
     }*/
    /**
     * 配置编辑
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return array
     */
    /*public function edit() {
        $postData = $this->request->post();
        if(db('member')->where(['mobile'=>$postData['nickname']])->count('id')>1){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '昵称已存在');
        }
        $postData['password'] = Tools::userMd5($postData['password']);
        $res = MemberModel::update($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }*/
    /**
     * 删除配置
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    /*public function del() {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        MemberModel::destroy($id);
        MemberModel::destroy(['id' => $id]);
        return $this->buildSuccess([]);
    }*/
    /**
     * 登录禁止开关
     * @return array
     * @author
     */
    public function changeStatus()
    {
        $id = $this->request->get('id');
        $status = $this->request->get('status');

        $res = AdminMemberService::changeStatus($id,$status);

        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        return $this->buildSuccess([]);
    }

    /**
     * 会员通道开关
     * @return array
     * @author
     */
    public function changeReceipt()
    {
        $id = $this->request->get('id');
        $is_pass = $this->request->get('is_pass');
        $res = MemberModel::update([
            'id' => $id,
            'is_pass' => $is_pass,
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        //关闭通道,移出队列
        if ($is_pass == 2) {
            (new PayService())->stopOrder(['id'=>$id]);
        }else{
            //打开通道,重置最后一单记录,用于连续3单空单限制
            $memberInfo = \app\model\Member::find($id);
            $lastOrderInfo = \app\model\MerchantOrder::where('member_id',$id)->order('id','desc')->find();
            $lastOrderId = empty($lastOrderInfo) ? 0 : $lastOrderInfo->id;
            $memberInfo->last_empty_order_id = $lastOrderId;
            $memberInfo->current_slow_order_num = 0;
            $memberInfo->save();
        }
        return $this->buildSuccess([]);

    }

    /**
     * 会员补单
     * @return array
     * @author
     */
    public function memberReplacement_back()
    {
        $data = $this->request->post();
        $merchant_id = $data['merchant_id'];
        $merchant_order_sn = $data['merchant_order_sn'];
        $member_id = $data['id'];
        $merchant = db('merchant')->where(['uid' => $merchant_id])->find();
        if (!$merchant) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '商户不存在');
        }
        $merchant_order = db('merchant_order')->where(['order_sn' => $merchant_order_sn, 'member_id' => $member_id])->find();
        if (!$merchant_order) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '商户订单不存在');
        }
        $merchant_order['create_time'] = $merchant_order['update_time'] = $merchant_order['match_time'] = $merchant_order['match_time'] = time();
        $merchant_order['replacement_order'] = 1;
        $merchant_order['is_clear'] = 2;
        $merchant_order['pay_status'] = 3;
        $merchant_order['status'] = 2;
        $merchant_order['order_sn'] = 'P' . rand_order();
        $merchant_order['initiator'] = $this->userInfo['username'];
        unset($merchant_order['id']);
        if (db('merchant_order')->insert($merchant_order) !== false) {
            return $this->buildSuccess([]);
        } else {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '补单成功，等待会员确认收款');
        }
    }

    /**
     * 补单 造单
     * @return array
     * @throws \think\exception\DbException
     */
    public function memberReplacement()
    {
        $expire = 10;
        $value = 'lock_value';
        $key = 'Admin:lock_'.$this->userInfo['id'];
        $lock = Cache::has($key);
        if(!empty($lock)){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '请勿重复操作！');
        }
        Cache::set($key,$value,$expire);
        $memberId = $this->request->post('member_id/d', 0);//会员ID
        $merchantUid = $this->request->post('merchant_uid/d', 0);//商户编号
        $price = $this->request->post('price', '');//订单金额
        $remark = $this->request->post('remark', '');
        $channel = $this->request->post('channel', '');

        $channel = empty($channel) ? 'alipay_qrcode' : $channel;

        //参数验证
        $merchant = \app\model\Merchant::get(['uid' => $merchantUid]);
        if (empty($merchant)) return $this->buildFailed(ReturnCode::RECORD_NOT_FOUND, '商户不存在');
        $member = MemberModel::get($memberId);
        if (empty($member)) return $this->buildFailed(ReturnCode::RECORD_NOT_FOUND, '收款员不存在');
        if (empty($price)) return $this->buildFailed(ReturnCode::PARAM_INVALID, '金额不得为空');

        //写入数据
        $time = time();
        $insertData = [
            'order_sn' => 'P' . rand_order(),
            'merchant_id' => $merchant->id,
            'agent_id' => $member->agent_id,
            'member_id' => $member->id,
            'start_money' => $price,
            'get_money' => $price,
            'create_time' => $time,
            'update_time' => $time,
            'match_time' => $time,
            'upload_time' => $time,
            'member_group_id' => $member->group_id,
            'merchant_order_date' => date('Y-m-d H:i:s', $time),
            'merchant_order_name' => '平台补单',
            'merchant_order_channel' => $channel,
            'merchant_order_sn' => $remark,
            'merchant_order_count' => 1,
            'merchant_order_desc' => '平台补单',
            'remark' => $remark,
            'status' => 2,
            'pay_status' => 1,
            'replacement_order' => 1,
            'initiator' => $this->userInfo['username']
        ];
        //计算资金
        $moneyService = new MoneyService();
        $money = $moneyService->reckonMoney($price,$merchant->id,$member->agent_id,$memberId);
        //更新数据
        $insertData['return_money'] = $money['return_money'];
        $insertData['platform_fee_money'] = $money['platform_fee_money'];
        $insertData['agent_fee_money'] = $money['agent_fee_money'];
        $insertData['member_fee_money'] = $money['member_fee_money'];
        $insertData['merchant_money'] = $money['merchant_money'];

        Db::startTrans();
        try {
            $merchantOrder = \app\model\MerchantOrder::create($insertData);

            $orderId = $merchantOrder->id;
            //订单模型对象
            //执行确认收款
            $memberService = new AgentMemberService();
            $return = $memberService->confirm($orderId);
            if(!$return){
                throw new Exception($memberService->getError(),500);
            }
            Db::commit();
            return $this->buildSuccess([]);
        } catch(\Exception $e) {
            Db::rollback();
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR,'添加失败 '.$e->getMessage());
        }
    }

    /**
     * 会员查看二维码
     * @return array
     * @author
     */
    public function checkQrcode()
    {
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $id = $this->request->get('id', 1);
        $where = [];
        $where ['member_id'] = $id;
        $where ['type'] = 1;
        $listObj = (new MemberImagesModel())->where($where)->order('id', 'DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        return $this->buildSuccess([
            'list' => $listObj['data'],
            'count' => $listObj['total']
        ]);
    }

    /**
     * 会员删除二维码
     * @return array
     * @author
     */
    public function delQrcode()
    {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        MemberImagesModel::destroy($id);
        return $this->buildSuccess([]);
    }
}
