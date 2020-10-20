<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/14 0014
 * Time: 18:01
 */

namespace app\agent\controller;

use app\admin\service\MemberService as AdminMemberService;
use app\agent\model\Config;
use app\agent\model\MerchantOrder;
use app\agent\model\MerchantWithdraw;
use app\agent\service\MemberService;
use app\agent\model\Member as MemberModel;
use app\agent\model\MemberGroup;
use app\api\service\AppApiService;
use app\api\swoole\PayService;
use app\util\ReturnCode;
use app\util\Tools;
use think\Db;
use think\Request;


class Member extends Base
{

    /**
     * 普通会员列表
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $page = $this->request->get('page', 1);
        $limit = $this->request->get('size', 15);

        //帅选条件
        $where['agent_id'] = $this->agent_id;
        //帅选过滤
        $keyword = $this->request->get('keywords', '');

        $isReceipt = $this->request->get('is_receipt/d', 0);
        $status = $this->request->get('status/d', 0);
        $isLeader = $this->request->get('is_leader/d', 0);
        $uid = $this->request->get('uid', '');
//        $groupId = $this->request->get('group_id','');
        !empty($isReceipt) && $where['is_receipt'] = $isReceipt;
        !empty($status) && $where['status'] = $status;
        !empty($isLeader) && $where['is_leader'] = $isLeader;
        !empty($uid) && $where['uid'] = $uid;

        if (!empty($keyword)) {
            is_numeric($keyword) ? (check_mobile($keyword) ? $where['mobile'] = $keyword : $where['id'] = $keyword) : $where['nickname'] = ['like', '%' . trim($keyword) . '%'];
        }
        $memberModel = new MemberModel();
        $memberService = new AdminMemberService();

        $list = $memberModel->getMemberList( $where, "*", 'id desc', $page, $limit);
        foreach ($list as $k => &$val) {
            $val['poundage_ratio'] = bcadd($val['poundage_ratio'], "0.00",2);

            $allRateData = $memberService->getMemberTurnoverRate($val['id']);
            $todayRateData = $memberService->getMemberTodayTurnoverRate($val['id']);

            $val['todayRate'] = $todayRateData['successOrderNum'].'/'.$todayRateData['allOrderNum'].':'.$todayRateData['rate'];
            $val['allRate'] = $allRateData['successOrderNum'].'/'.$allRateData['allOrderNum'].':'.$allRateData['rate'];

        }
        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

    /**
     * 新增会员
     * @return array
     */
    public function add()
    {
        $params = $this->request->post();
        //参数验证
        $validate = new \app\agent\validate\Member();
        $result = $validate->scene('add')->check($params);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, $validate->getError());
        }

        //限定死,只允许开普通收款员

        $agentId = $this->agent_id;
        $agentInfo = \app\model\Agent::find($agentId);

        $type = 1;

        //手续费比例验证
        /*
        $member_ratio = (new Config())->where('varname', '=', 'member_ratio')->value('value');
        $member_ratio = explode(',', $member_ratio);
        if ($data['poundage_ratio'] > $member_ratio[1] || $data['poundage_ratio'] <= $member_ratio[0]) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '手续费比例范围必须在：' . $member_ratio[0] . '~' . $member_ratio[1] . '间');
        }*/

        //开放手续费比率,允许设置范围,小于代理即可
        if($params['poundage_ratio'] > $agentInfo->poundage_ratio){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '手续费比例不得大于所属代理!');
        }

        $data['poundage_ratio'] = bcadd($params['poundage_ratio'], "0.00",2);
        //插入数据
        $data['agent_id'] = $agentId;
        $data['usable_limit'] = $data['total_limit'] = $params['total_limit'];
        $data['nickname'] = $params['nickname'];
        $data['mobile'] = $params['mobile'];
        $data['group_id'] = $params['group_id'];
        $data['type'] = $type;
        $data['max_return_num'] = $params['max_return_num'];

        //UID随机增长
        $max_uid = MemberModel::max('uid');
        if (empty($max_uid)) {
            $max_uid = 10000;
        }
        $data['uid'] = $max_uid + mt_rand(5, 15);
        $data['create_time'] = time();
        $data['password'] = Tools::userMd5($params['password']);
        $memberModel = new MemberModel();
        $result = $memberModel->add($data);
        if ($result !== false) {
            $service = new AppApiService();
            $service->createWechat($result,'默认账户');
            return $this->buildSuccess([]);
        }
        return $this->buildFailed(ReturnCode::ADD_FAILED, $memberModel->getError());
    }

    /**
     * 编辑会员
     * @return array
     * @throws \think\exception\DbException
     */
    public function edit()
    {
        $param = $this->request->post();
        //参数验证
        $validate = new \app\agent\validate\Member();
        $result = $validate->scene('edit')->check($param);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, $validate->getError());
        }

        /*if((int)$param['usable_limit'] > (int)$param['total_limit']) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '可用额度不得大于总额度');
        }*/

        //获取代理点位
        $agentId = $this->agent_id;
        $agentInfo = \app\model\Agent::find($agentId);

        //验证手续费比率, 必须不大于代理比率
        if($param['poundage_ratio'] > $agentInfo->poundage_ratio){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '手续费比例不得大于所属代理');
        }


        /*//手续费比例验证
        $member_ratio = (new Config())->where('varname', '=', 'member_ratio')->value('value');
        $member_ratio = explode(',', $member_ratio);
        if ($param['poundage_ratio'] > $member_ratio[1] || $param['poundage_ratio'] < $member_ratio[0]) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '手续费比例范围必须在：' . $member_ratio[0] . '~' . $member_ratio[1] . '间');
        }*/
        $param['poundage_ratio'] = bcadd($param['poundage_ratio'], "0.00", 2);
        $memberId = $param['id'];
        Db::startTrans();
        $memberInfo = \app\model\Member::lock(true)->find($memberId);
        if($memberInfo->poundage_ratio != $param['poundage_ratio']){
            $memberInfo->poundage_ratio = $param['poundage_ratio'];
        }

        $memberInfo->group_id = $param['group_id'];
        $memberInfo->nickname = $param['nickname'];
        $memberInfo->update_time = time();
        $memberInfo->max_return_num = data_get($param,'max_return_num',0);

        if (!empty($param['password'])) {
            $memberInfo->password = Tools::userMd5($param['password']);
        }

        //仅总额度与可用额度相等时可允许编辑
        if( !empty($param['total_limit']) && $param['total_limit'] != $memberInfo->total_limit){
            $diff = $param['total_limit'] - $memberInfo->total_limit;
            $memberInfo->total_limit = $param['total_limit'];
            $memberInfo->usable_limit += $diff;
        }

        $save = $memberInfo->save();
        if($save === false){
            Db::rollback();
            return $this->buildFailed(ReturnCode::UPDATE_FAILED, '修改失败!');
        }
        Db::commit();
        return $this->buildSuccess([]);
    }

    /**
     * 删除会员
     * @return array
     * @throws \think\exception\DbException
     */
    public function del()
    {
        return $this->buildFailed(ReturnCode::DELETE_FAILED, '删除失败');
    }

    /**
     * 修改收款员状态
     * @return array
     * @throws \think\exception\DbException
     */
    public function changeStatus()
    {
        $member_id = $this->request->get('id/d');
        $param = $this->request->get();
        $member = MemberModel::find(['id' => $member_id, 'agent_id' => $this->agent_id]);
        if (empty($member)) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '参数错误');
        }
        $result = false;

        if (array_key_exists('status', $param)) {
            $status = $param['status'];
            $result = \app\admin\service\MemberService::changeStatus($member_id,$status);
        }
        if (array_key_exists('is_pass', $param)) {
            //验证代理通道是否被关闭
            if($param['is_pass'] == 1){
                $agentInfo = \app\model\Agent::find( $this->agent_id);
                if( $agentInfo->type == 2){
                    return $this->buildFailed(ReturnCode::INVALID, '代理通道关闭,禁止操作!');
                }

                //打开通道,重置最后一单记录,用于连续3单空单限制
                $lastOrderInfo = \app\model\MerchantOrder::where('member_id',$member_id)->order('id','desc')->find();
                $lastOrderId = empty($lastOrderInfo) ? 0 : $lastOrderInfo->id;
                $member->last_empty_order_id = $lastOrderId;
                $member->current_slow_order_num = 0;
                $member->save();

            }else{
                (new PayService())->stopOrder(['id'=>$member_id]);
            }

            //通道状态
            $result = MemberModel::update([
                'id' => $member_id,
                'is_pass' => $param['is_pass']
            ]);

        }

        if (array_key_exists('is_leader', $param)) {
            //组长状态
            $result = MemberModel::update([
                'id' => $member_id,
                'is_leader' => $param['is_leader']
            ]);
        }

        if ($result) {
            return $this->buildSuccess([]);
        }
        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '修改失败');
    }

    /**
     * 分组列表
     * @return array
     */
    public function group()
    {
        $keyword = $this->request->get('name/s', '');
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $keyword = trim($keyword);
        $where = [];
        $where['agent_id'] = $this->agent_id;
        $where['delete_time'] = 0;
        if (!empty($keyword)) {
            $where['name'] = ['like', "%" . $keyword . "%"];
        }
        //分组列表
        $list = MemberGroup::where($where)
                     ->paginate($limit, false, [
                         'page' => $page,
                         'query' => Request::instance()->query()
                     ]);

        //获取当日开始
        $todayStartTime = strtotime(date('Y-m-d'));

        foreach ($list as $k => &$v) {
            //组成交累计量
            $money = MerchantOrder::getMoneyByMemberGroupId($v->id);
            $v->cumulative_money = round($money, 2);

            //组内成员总量
            $v->append(['member_count']);

            //获取当日成交总量
            $todayMoney = MerchantOrder::getMoneyByMemberGroupId($v->id,['create_time'=>['>',$todayStartTime]]);
            $v->today_cumulative_money = $todayMoney;

            //获取当日成交总量
            $todayMoney = MerchantOrder::getReturnMoneyByMemberGroupId($v->id);
            $v->return_money = $todayMoney;

        }
        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

    public function getAllGroup()
    {
        $where['agent_id'] = $this->agent_id;
        $where['delete_time'] = 0;
        //分组列表
        $list = MemberGroup::where($where)->select();

        return $this->buildSuccess(['list' => $list]);
    }

    /**
     * 组成员列表
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function groupMember()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        //组成员id
        $group_id = $this->request->get('group_id/d',0);
        $memberModel = new MemberModel();

        $listData = $memberModel
            ->where('group_id','=',$group_id)
            ->where('agent_id', '=', $this->agent_id)
            ->order('id desc')
            ->paginate($limit, false, [
                'page' => $page,
                'query' => Request::instance()->query()
            ]);

        $list = $listData->items();
        //获取当日开始
        $todayStartTime = strtotime(date('Y-m-d'));
        foreach($list as &$item){
            //成交总金额
            $item->total_money = MerchantOrder::where('member_id',$item->id)
                                              ->where('pay_status',2)
                                              ->sum('start_money');

            //未返总金额
            $item->return_money = MerchantOrder::where('member_id',$item->id)
                                               ->where('pay_status',2)
                                               ->where('is_clear',2)
                                               ->sum('return_money');
            //当日成交总金额
            $item->today_money = MerchantOrder::where('member_id',$item->id)
                                               ->where('pay_status',2)
                                               ->where('create_time','>=',$todayStartTime)
                                               ->sum('start_money');

        }

        return $this->buildSuccess(['list' => $list,'count' => $listData->total()]);
    }

    /**
     * 从指定组中删除指定用户
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function delMember()
    {
        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '禁止删除会员');
    }

    /**
     * 删除分组
     * @return array
     * @throws \think\exception\DbException
     */
    public function delGroup()
    {
        $group_id = $this->request->get('id/d');
        $detail = MemberGroup::get(['id' => $group_id, 'agent_id' => $this->agent_id]);
        if (empty($detail)) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '参数错误');
        }
        $userIds = MemberModel::getMemberId($this->agent_id,$group_id);
        if(count($userIds) > 0){
            return $this->buildFailed(ReturnCode::DELETE_FAILED, '组中已有成员无法删除');
        }
        $delete = $detail->update('delete_time',time());
        if ($delete === false ) {
            return $this->buildSuccess([]);
        }
        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
    }

    /**
     * 新增分组
     * @return array
     */
    public function addGroup()
    {
        $param = $this->request->post();
        if (empty($param['name'])) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '分组名称不能为空');
        }
        $param['name'] = trim($param['name']);
        $param['agent_id'] = $this->agent_id;
        $param['create_time'] = time();
        $param['update_time'] = time();
        if (MemberGroup::create($param)) {
            return $this->buildSuccess([]);
        }
        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
    }

    /**
     * 编辑分组
     * @return array
     * @throws \think\exception\DbException
     */
    public function editGroup()
    {
        $param = $this->request->post();
        if (empty($param['name'])) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '分组名称不能为空');
        }
        $param['name'] = trim($param['name']);
        $detail = MemberGroup::get(['id' => $param['id'], 'agent_id' => $this->agent_id]);
        $param['update_time'] = time();
        if ($detail->save($param)) {
            return $this->buildSuccess([]);
        }
        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
    }

    /**
     * 分组状态
     * @return array
     * @throws \think\exception\DbException
     */
    public function changeStatusGroup()
    {
        //组id
        $group_id = $this->request->post('id/d');
        //组状态
        $group_status = $this->request->post('status/d');
        $memberService = new MemberService();
        $result = $memberService->changeStatusGroup($group_id, $group_status, $this->agent_id);
        if ($result) {
            return $this->buildSuccess([]);
        }
        return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $memberService->getError());
    }

    /**
     * 查看会员收款记录
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function receipt()
    {
        $member_id = $this->request->get('id/d');

        $data = [
            'member_id' => $member_id,
            'agent_id' => $this->agent_id,
            'confirm_time' => ['>', 0],
            'pay_status' => 2,
            'status' => 3
        ];
//        $field = 'id,order_sn,member_id,agent_id,confirm_time,pay_status,status,start_money';
        $order = 'confirm_time desc';

        $list = (new MerchantOrder())->with('merchant')->where($data)->order($order)->select();
        return $this->buildSuccess(['list' => $list, 'count' => count($list)]);
    }

    /**
     * 普通会员返款记录
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function refundRecord()
    {
        $member_id = $this->request->get('id/d');

        $data = [
            'member_id' => $member_id,
            'agent_id' => $this->agent_id,
            'confirm_time' => ['>', 0],
            'pay_status' => 2,
            'status' => 4,
            'is_clear' => 1
        ];
//        $field = 'id,order_sn,member_id,agent_id,confirm_time,pay_status,status,start_money,is_clear';
        $order = 'confirm_time desc';

        $list = (new MerchantOrder())->with('merchant')->where($data)->order($order)->select();
        return $this->buildSuccess(['list' => $list, 'count' => count($list)]);
    }

}
