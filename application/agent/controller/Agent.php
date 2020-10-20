<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/13 0013
 * Time: 20:02
 */

namespace app\agent\controller;

use app\admin\service\GoogleService;
use app\agent\model\Agent as AgentModel;
use app\agent\model\AgentAuthGroup;
use app\agent\model\Bank;
use app\agent\model\BankCard;
use app\agent\model\Member as MemberModel;
use app\agent\model\MerchantOrder;
use app\agent\model\Notice;
use app\model\AgentAuthGroupAccess;
use app\util\GoogleAuthenticator;
use app\util\ReturnCode;
use app\util\Tools;
use think\Db;
use think\Exception;

class Agent extends Base
{

    /**
     * 获取代理商信息 编辑
     * @return array
     */
    public function info()
    {
        //代理商信息
        $field = 'id,uid,nickname,type,balance,poundage_ratio';
        $detail = AgentModel::detail(['id' => $this->agent_id], $field);
        return $this->buildSuccess($detail);
    }

    /**
     * 获取代理商信息 首页
     * @return array
     * @throws \think\exception\DbException
     */
    /**
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function getInfo()
    {
        $MerchantOrderModel = new MerchantOrder();

        //今日新增会员数
        $member_count = MemberModel::memberCount( $this->agent_id);

        //平台公告
        $notice = Notice::all();

        //今日交易信息
        $today_start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $today_end = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
        $tResult = $this->traceOrder($MerchantOrderModel,$today_start,$today_end);


        //昨日交易信息
        $yesterday_start = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $yesterday_end = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
        $yResult = $this->traceOrder($MerchantOrderModel,$yesterday_start,$yesterday_end);


        //代理商信息
        $detail = AgentModel::detail(['id' => $this->agent_id]);
        $detail['member_count'] = $member_count;

        $detail['noSettlement'] = $detail['return_money'];//持有未结算金额（普通收款员返款给代理总额未结算的部分）
        $detail['notice'] = $notice;

        $detail['today'] = [
            'total' => $tResult['totalOrderNum'],
            'success' => $tResult['successOrderNum'],
            'rate' => $tResult['successRate'] . '%',
            'fee_money' => $tResult['agentFeeMoney']
        ];
        $detail['yesterday'] = [
            'total' => $yResult['totalOrderNum'],
            'success' => $yResult['successOrderNum'],
            'rate' => $yResult['successRate'] . '%',
            'fee_money' => $yResult['agentFeeMoney']
        ];
        return $this->buildSuccess($detail);
    }

    /**
     * 统计某个时间段 订单信息
     * @param $MerchantOrderModel
     * @param $startDate
     * @param $endDate
     * @return array
     */
    private function traceOrder($MerchantOrderModel, $startDate, $endDate)
    {
        $map['agent_id'] = $this->agent_id;
        $map['create_time'] = ['>=', $startDate];
        //总订单数
        $TotalOrderNum = $MerchantOrderModel->where($map)->where('create_time', '<=', $endDate)->count();
        //成功订单数
        $map['pay_status'] = 2;
        $SuccessOrderNum = $MerchantOrderModel->where($map)->where('create_time', '<=', $endDate)->count();
        $SuccessRate = 0;
        if (!empty($TotalOrderNum)) {
            $SuccessRate = round(($SuccessOrderNum / $TotalOrderNum) * 100, 2);
        }
        //昨日收益
        $AgentFeeMoney = $MerchantOrderModel->where($map)->where('create_time', '<=', $endDate)->sum('agent_fee_money');
        return [
            'totalOrderNum' => $TotalOrderNum,
            'successOrderNum' => $SuccessOrderNum,
            'successRate' => $SuccessRate,
            'agentFeeMoney' => $AgentFeeMoney,
        ];
    }

    /**
     * 代理商银行卡列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function bankcard()
    {
        $list = BankCard::all([ 'uid' => $this->agent_id, 'type' => 2]);
        return $this->buildSuccess($list);
    }

    /**
     * 添加银行卡
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function addCard()
    {
        //请求参数
        $param = $this->request->post();
        //参数验证
        $validate = new \app\agent\validate\BankCard();
        $results = $validate->scene('add')->check($param);
        if (true !== $results) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, $validate->getError());
        }

        $bankCardModel = new BankCard();
        //查看当前代理商是否有默认的卡
        $has = $bankCardModel->where([ 'uid' => $this->agent_id, 'type' => 2, 'status' => 1])->count();
        if ($has > 0) {
            $param['status'] = 2;
        }
        //只能添加一张银行卡
//        $has = $bankCardModel->where(['uid' => $this->agentInfo['id'], 'type' => 2])->count();
//        if ($has > 0) {
//            return $this->buildFailed(ReturnCode::INVALID, '只能添加一张银行卡更改信息请编辑现有银行卡' );
//        }

        $result = $bankCardModel->add(0, $this->agent_id, 2, $param);
        if ($result) {
            return $this->buildSuccess([]);
        }
        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR,  $bankCardModel->getError());
    }

    /**
     * 更新银行卡
     * @return array
     * @throws \think\exception\DbException
     */
    public function editCard()
    {
        //请求参数
        $param = $this->request->post();
        //参数验证
        $validate = new \app\agent\validate\BankCard();
        $results = $validate->scene('edit')->check($param);
        if (true !== $results) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, $validate->getError());
        }

        $bankCardModel = new BankCard();

        $result = $bankCardModel->add($param['id'], $this->agent_id, 2, $param);
        if ($result) {
            return $this->buildSuccess([]);
        }
        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败：' . $bankCardModel->getError());
    }

    /**
     * 收款账户类型列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function indexCard()
    {
        $list = Bank::all();
        return $this->buildSuccess($list);
    }

    /**
     * 修改银行卡默认状态
     * @return array
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function statusCard()
    {
        $id = $this->request->post('id/d');
        $status = $this->request->post('status/d');
        $bankCardModel = new BankCard();
        $result = $bankCardModel->setStatus($id, $status);
        if ($result) {
            return $this->buildSuccess([]);
        } else {
            return $this->buildFailed(ReturnCode::DB_READ_ERROR, '修改失败：' . $bankCardModel->getError());
        }
    }

    /**
     * 删除银行卡
     * @return array
     * @throws \think\exception\DbException
     */
    public function delCard()
    {
        $id = $this->request->post('id/d');
        $detail = BankCard::get([ 'id' => $id, 'uid' => $this->agent_id, 'type' => 2]);
        if (empty($detail)) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '参数错误');
        }
        $result = $detail->delete();
        if ($result) {
            return $this->buildSuccess([]);
        } else {
            return $this->buildFailed(ReturnCode::DELETE_FAILED, '删除失败');
        }
    }

    /**
     * 代理商修改支付密码
     * @return array
     * @throws \think\exception\DbException
     */
    public function paypwd()
    {
        $params = $this->request->post();
        if (!is_numeric($params['pay_password']) || $params['pay_password'] > 6) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '支付密码必须是六位数字');
        }
        $agentInfo = $this->agentInfo;
        //支付密码
        if ($params['pay_password'] && $params['old_pay_password']) {
            $oldPayPass = Tools::userMd5($params['old_pay_password']);
            unset($params['old_pay_password']);
            if ($oldPayPass === $agentInfo['pay_password']) {
                $params['pay_password'] = Tools::userMd5($params['pay_password']);
            } else {
                return $this->buildFailed(ReturnCode::INVALID, '原始支付密码不正确');
            }
        } else {
            unset($params['pay_password']);
            unset($params['old_pay_password']);
        }
        $params['id'] = $agentInfo['id'];
        $params['update_time'] = time();
        $res = AgentModel::update($params);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }

    }

    /**
     * 代理商修改信息、密码
     * @return array
     * @throws \think\exception\DbException
     */
    public function own()
    {
        $postData = $this->request->post();
        //刷新代理商信息
        $agentInfo = $this->agentInfo;
        //登录密码
        if ($postData['password'] && $postData['old_password']) {
            $oldPass = Tools::userMd5($postData['old_password']);
            unset($postData['old_password']);
            if ($oldPass === $agentInfo['password']) {
                $postData['password'] = Tools::userMd5($postData['password']);
            } else {
                return $this->buildFailed(ReturnCode::INVALID, '原密码不正确');
            }
        } else {
            unset($postData['password']);
            unset($postData['old_password']);
        }

        $postData['id'] = $agentInfo['id'];
        $postData['update_time'] = time();

        $res = AgentModel::update($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {

            return $this->buildSuccess([]);
        }
    }

    /**
     * 新增子账户
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function add()
    {
        $groups = '';
        $postData = $this->request->post();
        $validate = new \app\agent\validate\Agent();
        $result = $validate->scene('add')->check($postData);
        if(true !== $result)
        {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, $validate->getError());
        }
        $postData['last_login_ip'] = '';
        $postData['uid'] = 0;
        $postData['last_login_time'] = '';
        $postData['password'] = Tools::userMd5($postData['password']);
        if ($postData['groupId']) {
//            $groups = trim(implode(',', $postData['groupId']), ',');//多选权限组
            $groups = trim($postData['groupId']);//单选权限组
        }
        $postData['parent_id'] = $this->agent_id;
        unset($postData['groupId']);
        unset($postData['id']);
        Db::startTrans();
        try{
            $result = AgentModel::create($postData);
            AgentAuthGroupAccess::create([
                'uid' => $result->id,
                'groupId' => $groups
            ]);
            Db::commit();
            return $this->buildSuccess([]);
        }catch(\Exception $e){
            Db::rollback();
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败 '.$e->getMessage());
        }
        /*$res = AgentModel::create($postData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            AgentAuthGroupAccess::create([
                'uid' => $res->id,
                'groupId' => $groups
            ]);

            return $this->buildSuccess([]);
        }*/
    }

    /**
     * 子账号列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $limit = $this->request->get('size/d', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $keywords = $this->request->get('keywords', '');
        $status = $this->request->get('status', '');
        //查询条件
        $where['parent_id'] = $this->agent_id;
        if (!empty($status)) {
            $where['status'] = $status;
        }
        if (!empty($keywords)) {
            $where['mobile'] = ['like', "%{$keywords}%"];
        }
        //子账户
        $listObj = AgentModel::getSubList($where, 'create_time desc', $start, $limit, 'id,parent_id,mobile,nickname,last_login_time,last_login_ip,status');
        foreach ($listObj as $key => $value) {
            $value->append(['group_name']);
//            $group_name = '';
//            $group_id = (new AgentAuthGroupAccess())->where(['uid'=>$value['id']])->value('groupId');
//            if(!empty($group_id)){
//                $group_name = (new AgentAuthGroup())->where(['id'=>$group_id,'agent_id'=>$this->agent_id])->value('name');
//            }
//            $listObj[$key]['group_name'] = !empty($group_name) ? $group_name : '';
        }
        $listArray = $listObj->toArray();
        $listInfo = $listArray['data'];
        $idArr = array_column($listInfo, 'id');
        //权限组
        $userGroup = AgentAuthGroupAccess::all(function ($query) use ($idArr) {
            $query->whereIn('uid', $idArr);
        });

        $userGroup = Tools::buildArrFromObj($userGroup);
        $userGroup = Tools::buildArrByNewKey($userGroup, 'uid');

        foreach ($listInfo as $key => $value) {
            if (isset($userGroup[$value['id']])) {
                $listInfo[$key]['groupId'] = explode(',', $userGroup[$value['id']]['groupId']);
            } else {
                $listInfo[$key]['groupId'] = [];
            }
        }
        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj->total()
        ]);
    }

    /**
     * 获取某权限组的全部用户
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function getUsers()
    {
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $page = $this->request->get('page', 1);
        $gid = $this->request->get('gid', 0);
        if (!$gid) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '非法操作');
        }

        $uid = (new AgentAuthGroupAccess())->where('find_in_set("' . $gid . '", `groupId`)')->column('uid');
        $uid = array_unique($uid);
        $where['parent_id'] = $this->agent_id;
        $where['id'] = ['in', $uid];
        $listObj = AgentModel::getSubList($where, 'create_time desc', $page, $limit, 'id,uid,mobile,nickname,last_login_ip,last_login_time,status');
        $listArray = $listObj->toArray();
        $listInfo = $listArray['data'];
        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj->total()
        ]);
    }


    /**
     * 子账号状态编辑
     * @return array
     * @throws \think\exception\DbException
     */
    public function changeStatus()
    {
        $id = $this->request->post('id');
        $status = $this->request->post('status');
        $agentInfo = AgentModel::get(['id' => $id]);
        if (empty($agentInfo)) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '参数错误');
        }
        $res = $agentInfo->save(['status' => $status, 'update_time'], ['id' => $id]);
//        $res = AgentModel::update([
//            'id' => $id,
//            'status' => $status,
//            'update_time' => time()
//        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }

    /**
     * 编辑子账户
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return array
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $groups = '';
        $postData = $this->request->post();
        $validate = new \app\agent\validate\Agent();
        $result = $validate->scene('edit')->check($postData);
        if(true !== $result)
        {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, $validate->getError());
        }
        if ($postData['password'] === '') {
            unset($postData['password']);
        } else {
            $postData['password'] = Tools::userMd5($postData['password']);
        }
        if ($postData['groupId']) {
//            $groups = trim(implode(',', $postData['groupId']), ',');//多选权限组 23,25
            $groups = trim($postData['groupId']);//单选权限组
        }
        $postData['update_time'] = time();
        unset($postData['groupId']);
        Db::startTrans();
        try{
            AgentModel::update($postData);
            $has = AgentAuthGroupAccess::get(['uid' => $postData['id']]);
            if ($has) {
                AgentAuthGroupAccess::update([
                    'groupId' => $groups
                ], [
                        'uid' => $postData['id'],
                ]);
            } else {
                AgentAuthGroupAccess::create([
                    'uid' => $postData['id'],
                    'groupId' => $groups
                ]);
            }
            Db::commit();
            return $this->buildSuccess([]);
        }catch(\Exception $e) {
            Db::rollback();
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR,'编辑失败');
        }
    }

    /**
     * 删除子账户
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function del()
    {
        $id = $this->request->post('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        Db::startTrans();
        try{
            AgentModel::destroy($id);
            AgentAuthGroupAccess::destroy(['uid' => $id]);
            Db::commit();
            return $this->buildSuccess([]);
        }catch(\Exception $e) {
            Db::rollback();
            return $this->buildFailed(ReturnCode::DELETE_FAILED,'删除失败');
        }
    }


    //获取谷歌验证码
    public function getGoogleQrcode()
    {
        $password = $this->request->post('password/s','');
        $agentInfo = $this->agentInfo;
        $secretKey = $agentInfo->google_secret_key;

        if(empty($password)){
            return $this->buildFailed(ReturnCode::INVALID, '请输入密码');
        }

        $password = Tools::userMd5($password);
        if ($password === $agentInfo->password) {
            //第一次生成key,保存到账户信息中
            if(!$secretKey){
                $ga = new GoogleAuthenticator();
                $secretKey = $ga->createSecret();
                $agentInfo->google_secret_key = $secretKey;
                $agentInfo->save();
            }
            $accountName = $agentInfo->mobile;
            $title = env('systemName','').'代理中心';
            //返回谷歌验证图片
            $qrcode = GoogleService::getGoogleQrcode($accountName,$secretKey,$title);
            return $this->buildSuccess(['qrcodeUrl'=>$qrcode]);
        } else {
            return $this->buildFailed(ReturnCode::INVALID, '密码不正确');
        }
    }

    public function addGoogleAuth()
    {
        $code = $this->request->post('code/s');
        if(empty($code)){
            return $this->buildFailed(ReturnCode::INVALID, '请输入验证码');
        }

        $agentInfo = $this->agentInfo;
        $secretKey = $agentInfo->google_secret_key;

        //返回谷歌验证图片
        $getCode = GoogleService::getGoogleCode( $secretKey );

        if($code !== $getCode){
            return $this->buildFailed(ReturnCode::INVALID, '验证失败,请重新输入!');
        }

        $agentInfo->used_google_code = 1;
        $agentInfo->save();

        return $this->buildSuccess([],'验证成功!');
    }

}
