<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\admin\controller;
use app\admin\service\AgentService;
use app\api\swoole\RedisService;
use app\util\Tools;
use app\util\ReturnCode;
use app\model\Agent as AgentModel;
use app\admin\validate\Agent as Agentvalidate;
use app\model\Member as MemberModel;
use think\Db;

class Agent extends Base{

    /**
     * 代理商列表
     * @return array
     * @author
     */
    public function index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $type = $this->request->get('type', '');
        $keywords = $this->request->get('keywords', '');
        $uid = $this->request->get('uid', '');
        $where = [];
        if ($type === '1' || $type === '2') {
            $where['type'] = $type;
        }
        if ($uid){
            $where['uid'] = $uid;
        }
        if ($keywords) {
            $where['mobile|nickname'] = ['like', "%{$keywords}%"];
        }

        $fields = [
            'id',
            'uid',
            'nickname',
            'mobile',
            'account_holder',
            'settlement_money',
            'total_per_money',
            'create_time',
            'used_google_code',
            'usable_limit',
            'type',
            'status'
        ];


        $where['parent_id'] = 0;
        $listObj = (new AgentModel())
            ->field($fields)
            ->where($where)
            ->order('type','ASC')
            ->order('settlement_money','DESC')
            ->order('id','DESC')
            ->paginate($limit, false, ['page' => $start])
            ->toArray();

        $agentService = new AgentService();
        $listInfo = $listObj['data'];
        foreach($listInfo as $key => &$val){
            $value['last_login_time'] = empty($value['last_login_time']) ? '' : date('Y-m-d H:i:s', $value['last_login_time']);

            $yesterdayRateData = $agentService->getAgentYesterdayTurnoverRate($val['id']);
            $todayRateData = $agentService->getAgentTodayTurnoverRate($val['id']);
            $allRateData = $agentService->getAgentTurnoverRate($val['id']);

            $val['yesterdayRate'] = $yesterdayRateData['successOrderNum'].'/'.$yesterdayRateData['allOrderNum'].':'.$yesterdayRateData['rate'];
            $val['todayRate'] = $todayRateData['successOrderNum'].'/'.$todayRateData['allOrderNum'].':'.$todayRateData['rate'];
            $val['allRate'] = $allRateData['successOrderNum'].'/'.$allRateData['allOrderNum'].':'.$allRateData['rate'];
        }
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
        ]);
    }

    public function getAgentInfo(){
        $id = $this->request->get('id', '');
        if (empty($id)) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        $fields = [
            'total_limit',
            'usable_limit',
            'poundage_ratio',
        ];

        $agentInfo = AgentModel::field($fields)
                               ->find($id);
        if (empty($agentInfo)) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }
        return $this->buildSuccess($agentInfo);
    }

    /**
     * 代理商成员列表
     * @return array
     * @author
     */
    public function member_index(){
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $id = $this->request->get('id', '');
        $where = [];
        if (empty($id)) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        }

        $where['agent_id'] = $id;
        $listObj = memberModel::field('password,user_token',true)
                              ->where($where)
                              ->order('create_time DESC')
                              ->paginate($limit, false, ['page' => $start])
                              ->toArray();
        $listInfo = $listObj['data'];
        return $this->buildSuccess([
            'list'  => $listInfo,
            'count' => $listObj['total'],
        ]);
    }

    /**
     * 新增代理商
     * @return array
     * @author
     */
    public function add(){
        $params = $this->request->post();
        /*if(db('agent')->where(['mobile'=>$postData['mobile']])->count('id')>0){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '手机号已存在');
        }
        if(db('agent')->where(['mobile'=>$postData['nickname']])->count('id')>0){
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '昵称已存在');
        }*/
        //参数验证
        $validate = new Agentvalidate();
        $result = $validate->scene('add')->check($params);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $validate->getError());
        }

        $createData = [];
        $createData['mobile'] = $params['mobile'];
        $createData['account_holder'] = $params['account_holder'];
        $createData['nickname'] = $params['nickname'];
        $createData['last_login_ip'] = request()->ip();
        $createData['last_login_time'] = $createData['create_time'] = time();
        $createData['password'] = Tools::userMd5($params['password']);
        $createData['pay_password'] = Tools::userMd5($params['pay_password']);
        $createData['usable_limit'] = $createData['total_limit'] = $params['total_limit'];
        $createData['poundage_ratio'] = $params['poundage_ratio'];
        //UID随机增长
        $max_uid = AgentModel::max('uid');
        if(empty($max_uid)){
            $max_uid = 10000;
        }
        $createData['uid'] = $max_uid + mt_rand(10, 50);
        $res = AgentModel::create($createData);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 代理商编辑
     * @author
     * @return array
     */
    public function edit() {
        $param = $this->request->post();
        $validate = new Agentvalidate();
        $result = $validate->scene('edit')->check($param);
        if ($result !== true) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, $validate->getError());
        }

        Db::startTrans();
        $agentInfo = \app\model\Agent::lock(true)->find($param['id']);
        $agentInfo->nickname = $param['nickname'];
        if($param['password']){
            $agentInfo->password = Tools::userMd5($param['password']);
        }

        if($param['pay_password']){
            $agentInfo->pay_password = Tools::userMd5($param['pay_password']);
        }

        if($agentInfo->poundage_ratio != $param['poundage_ratio']){
            $agentInfo->poundage_ratio = $param['poundage_ratio'];
        }

        //仅总额度与可用额度小于10元时可允许编辑
        if( !empty($param['total_limit']) && $agentInfo->total_limit != $param['total_limit']){
            $diff = $param['total_limit'] - $agentInfo->total_limit;
            $agentInfo->total_limit = $param['total_limit'];
            $agentInfo->usable_limit += $diff;
        }

        $save = $agentInfo->save();
        if($save === false){
            Db::rollback();
            return $this->buildFailed(ReturnCode::UPDATE_FAILED, '修改失败!');
        }
        Db::commit();
        return $this->buildSuccess([]);

    }
    /**
     * 删除代理商
     * @return array
     * @author
     */
   /* public function del() {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        AgentModel::destroy($id);
        AgentModel::destroy(['id' => $id]);

        return $this->buildSuccess([]);

    }*/
    /**
     * 代理商通道开关
     * @return array
     * @author
     */
    public function changeType() {
        $id = $this->request->get('id');
        $type = $this->request->get('type');

        $res = AgentModel::where('id',$id)->update(['type'=>$type]);

        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            //查询代理商子账户也同时修改
            AgentModel::where('parent_id',$id)->update(['type'=> $type]);
            //如果是关闭通道，将关闭自己下级所以会员的通道
            if($type==2){
                \app\model\Member::where('agent_id',$id)->update(['is_pass'=>2]);
            }
            return $this->buildSuccess([]);
        }
    }
    /**
     * 代理商登录开关
     * @return array
     * @author
     */
    public function changeStatus() {
        $id = $this->request->get('id');
        $status = $this->request->get('status');
        $res = AgentModel::where('id',$id)->update(['status'  => $status]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            //查询代理商子账户也同时修改
            AgentModel::where(['parent_id'  => $id])->update(['status'=> $status]);

            $agentInfo = AgentModel::field('mobile')->find($id);

            $agentInfo->status = $status;
            $save = $agentInfo->save();
            if ($save === false) {
                return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
            }

            $mobile = $agentInfo->mobile;
            if($status == 1){
                $keys = RedisService::keys('pay_agentLogin:'.$mobile.':*');
                RedisService::del($keys);
            }

            return $this->buildSuccess([]);
        }
    }

}
