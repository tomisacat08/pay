<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/14 0014
 * Time: 19:02
 */

namespace app\agent\controller;


use app\agent\model\AgentAllotLog;
use app\agent\model\AgentMoneyLog;
use app\agent\model\Config;
use app\agent\model\MerchantWithdraw;
use app\agent\model\MerchantWithdrawAudit;
use app\agent\model\PlatformWithdraw;
use app\agent\model\SettlementTask;
use app\agent\model\Agent;
use app\api\service\MerchantCallbakService;
use app\api\swoole\SwooleClientService;
use app\model\Merchant;
use app\util\lock\Lock;
use app\util\ReturnCode;
use think\Db;
use think\Request;

class Balance extends Base
{
    protected $error = '';

    public function index()
    {

    }


    /**
     * 结算任务
     * @return array
     */
    public function task()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $settlement_sn = $this->request->get('settlement_sn', '');
        $status = $this->request->get('status/d');
        $daterange = $this->request->get('daterange/a','');
        $merchant_uid = $this->request->get('merchant_uid','');//商户编号
        //帅选条件
        $where = [
            'agent_id' => $this->parentAgentInfo->id,
        ];
        if (!empty($settlement_sn)) {
            $where['settlement_sn'] = ['like', '%' . trim($settlement_sn) . '%'];
        }
        if(!empty($status)){
            $where['status'] = $status;
        }
        //日期
        if(!empty($daterange)){
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['create_time',[$start_time,$end_time]];
        }
        //商户编号
        if(!empty($merchant_uid)){
            $where['pm_uid'] = $merchant_uid;
        }
        $list = SettlementTask::getTaskList($where, 'id desc', '*', $page, $limit);
        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

    /**
     * 结算分配信息
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function allotInfo()
    {
        $settlement_task_id = $this->request->get('id/d');
        //结算任务信息
        $settlement_task = SettlementTask::where('id',$settlement_task_id)
                                         ->where('agent_id',$this->parentAgentInfo->id)
                                         ->find();

        if (empty($settlement_task) || $settlement_task->getData('status') != 2) {
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '任务状态异常,请稍后再试');
        }

        //获取最新的代理商信息
        $agentInfo = Agent::where('id', $this->parentAgentInfo->id)
                                  ->field('id as agent_id,uid,nickname,mobile,return_money')
                                  ->find();
        $agentInfo['value'] = "0.00";

        return $this->buildSuccess([
            'settlement' => $settlement_task,
            'agent' => [$agentInfo],
        ]);

    }


    /**
     * 结算分配
     * @return array
     * @throws \think\exception\DbException
     */
    public function allot()
    {
        $lock = new Lock('redis');
        $lockKey = 'Agent:lock:'.$this->parentAgentInfo->id;
        $lock->get($lockKey);

        //请求参数
        $param = $this->request->post();
        Db::startTrans();
        try{
            //结算任务信息
            $settlement_task = SettlementTask::lock(true)->where('id',$param['id'])
                                             ->where('agent_id',$this->parentAgentInfo->id)
                                             ->find();

            if (empty($settlement_task) || $settlement_task->getData('status') != 2) {
                abort(ReturnCode::PARAM_INVALID, '任务状态异常,请稍后再试');
            }

            //修改结算任务状态
            $settlement_task->status = 1;
            $settlement_task->save();

            //配置信息
            $config = new Config();
            //代理分配结算，每单金额最低
            $min_money = $config->where('varname','allot_min_money')->value('value');

            //代理商结算分配金额
            $agent_allot_money = 0.00;

            //代理商分配结算任务数据
            $agentInsertData = [];
            $now = time();

            //代理商
            if (!empty($param['agentdataList']) && bccomp($param['agentdataList'][0]['value'], "0.00") !== 0) {//金额不等于零
                if (bccomp($param['agentdataList'][0]['value'], $min_money) === -1) {
                    abort(ReturnCode::PARAM_INVALID, '分配结算每单金额不得低于' . $min_money . '元');
                }
                //分配金额
                $agent_allot_money = bcadd($agent_allot_money, $param['agentdataList'][0]['value'],2);
                //结算数据
                $agentInsertData[] = [
                    'agent_id' => $this->parentAgentInfo->id,
                    'member_id' => 0,
                    'type' => 1,
                    'settlement_id' => $param['id'],
                    'order_sn' => 'MW' . rand_order(),
                    'money' => $param['agentdataList'][0]['value'],
                    'bank_card' => $settlement_task->bank_card,
                    'create_time' => $now
                ];
            }

            //参数校验
            if (bccomp($settlement_task->settlement_money, $agent_allot_money) !== 0) {
                abort(ReturnCode::INVALID, '分配的结算金额不正确');
            }

            $res = $this->allotInsert($agentInsertData, $settlement_task);
            if ($res !== true) {
                abort(ReturnCode::DB_SAVE_ERROR, '分配失败');
            }

            Db::commit();
            $lock->release($lockKey);
            return $this->buildSuccess([]);

        }catch(\Exception $e){
            $lock->release($lockKey);
            Db::rollback();
            $errorMsg = '分配失败：' . $e->getMessage();
            return $this->buildFailed(500,$errorMsg);
        }

    }


    /**
     * 写入数据
     * @param $agentInsertData
     * @param $settlementTask
     * @return bool
     */
    private function allotInsert($agentInsertData, $settlementTask)
    {
        Db::startTrans();
        try {
            if (!empty($agentInsertData)) {//代理商
                //生成结算订单
                //扣除账户待结算金额
                //记录日志
                foreach ($agentInsertData as $key => $vo) {
                    MerchantWithdraw::create($vo);
                }
            }

            //结算操作日志
            $allot_log = [
                'allot_id' => $settlementTask->id,
                'allot_sn' => $settlementTask->settlement_sn,
                'merchant_uid' => $settlementTask->pm_uid,
                'settlement_money' => $settlementTask->settlement_money,
                'bank_card' => $settlementTask->bank_card,
                'agent_id' => $this->parentAgentInfo->id,
                'action_id' => $this->agent_id,
                'action_agent' => $this->parentAgentInfo->mobile,
                'create_time' => time()
            ];
            AgentAllotLog::create($allot_log);
            Db::commit();
            return true;
        } catch (\Exception $e) {
            Db::rollback();
            $this->error = '分配失败：' . $e->getMessage();
            return false;
        }
    }

    /**
     * 结算分配金额 大于平台设定 max_money=1000时 拆分 4300.12 => 1000,1000,1000,1000,300.12
     * @param $money
     * @param $max_money
     * @return array
     */
    private function buildMoney($money, $max_money)
    {
        //求余数 4300.12 => 300.12
        $modMoney = fmod(floatval($money), $max_money);;
        //减法 4300.12 - 300.12 = 4000
        $subMoney = bcsub($money, $modMoney,2);
        //除法  4000%1000 = 4
        $num = bcdiv($subMoney, $max_money, 0);
        $arr = [];
        for ($x = 0; $x < $num; $x++) {
            $arr[$x] = $max_money;
        }
        if (!empty($modMoney)) {
            $arr[] = $modMoney;
        }
        return $arr;
    }

    //我的订单
    public function myOrder()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $order_sn = $this->request->get('order_sn', '');
        $status = $this->request->get('status/d');

        //帅选条件
        $where = [
            'agent_id' => $this->parentAgentInfo->id,
        ];
        if (!empty($order_sn)) {
            $where['order_sn'] =  ['like', '%' . trim($order_sn) . '%'];
        }
        if(!empty($status)){
            $where['status'] = $status;
        }
        $list = MerchantWithdraw::getOrderList($where, '*', 'id desc', $page, $limit);
        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }


    /**
     * //结算统计 结算记录 已完成的
     * @return array
     * @throws \think\exception\DbException
     */
    public function statistics()
    {
        $page = $this->request->get('page/d',1);
        $limitRows = $this->request->get('size/d',10);
        $order_sn = $this->request->get('order_sn','');

        //查询条件
        $where['agent_id'] = $this->parentAgentInfo->id;
        $where['status'] = 2;
        $where['pay_time'] = ['>', 0];

        $merchantWithdraw = new MerchantWithdraw();
        //1、统计结算金额
        $allot_money = $merchantWithdraw->where($where)
                                        ->sum('money');
        //帅选条件 放在统计结算金额下 以免影响统计
        if (!empty($order_sn)) {
            $where['order_sn'] =  ['like', '%' . trim($order_sn) . '%'];
        }
        $allot_money = bcadd($allot_money, '0.00',2);
        //2、列表
        $list = $merchantWithdraw->with(['memberAccount', 'settlement','agentAccount'])
                                 ->where($where)
                                 ->order("id desc")
                                 ->paginate($limitRows, false, [
                                     'page' => $page,
                                     'query' => Request::instance()->query()
                                 ]);
        return $this->buildSuccess(['allot_money'=>$allot_money,'list'=>$list->items(),'count'=>$list->total()]);
    }

    /**
     * 上传结算凭证图
     */
    public function proof()
    {
        $path = '/upload/proof/' . date('Ymd', time()) . '/';
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
                'fileUrl' => $this->request->domain() . $path . $new_name,
                'filePath' => $path . $new_name
            ]);
        } else {
            return $this->buildFailed(ReturnCode::FILE_SAVE_ERROR, '文件上传失败');
        }
    }

    /**
     * 保存结算凭证图
     * @return array
     * @throws \think\exception\DbException
     */
    public function proofSave()
    {
        //结算订单id
        $merchant_withdraw_id = $this->request->post('id/d');
        //凭证图
        $filePath = $this->request->post('filePath', '');
        if (empty($filePath)){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '凭证图上传失效，请重新上传');
        }


        $lock = new Lock('redis');

        $lockKey = 'proofSave:'.$merchant_withdraw_id;
        $lock->get($lockKey,15);

        $now = time();
        //修改结算订单状态 结算任务状态 结算申请表状态
        Db::startTrans();
        try {
            $merchant_withdraw = MerchantWithdraw::lock(true)
                                                 ->where('id',$merchant_withdraw_id)
                                                 ->where('agent_id',$this->parentAgentInfo->id)
                                                 ->find();
            if (empty($merchant_withdraw)){
                abort(ReturnCode::PARAM_INVALID, '参数错误：结算订单不存在');
            }
            if($merchant_withdraw->getData('status') != 1){
                abort(ReturnCode::PARAM_INVALID, '订单已处理,请刷新后再试');
            }

            //结算订单
            $merchant_withdraw->pic = $filePath;
            $merchant_withdraw->pay_time = $now;
            $merchant_withdraw->status = 2;
            $merchant_withdraw->save();

            $agentInfo = \app\model\Agent::lock(true)->find($this->parentAgentInfo->id);
            // 恢复代理商相应的可用额度
            \app\model\Agent::where( 'id', $this->parentAgentInfo->id )
                            ->inc( 'return_money', -$merchant_withdraw[ 'money' ] )//代理总持有未结
                            ->inc( 'usable_limit', $merchant_withdraw[ 'money' ] )
                            ->update();

            $currentMoney = bcsub($agentInfo->return_money, $merchant_withdraw['money'],2);

            // 代理账变
            $agentMoneyLog = [
                'agent_id' => $this->parentAgentInfo->id,
                'order_sn' => $merchant_withdraw->order_sn,
                'order_id' => $merchant_withdraw->id,
                'money' => $merchant_withdraw['money'],//代理商待返金额
                'current_money' => $currentMoney,//当前待返总额度(包含本单)
                'create_time' => $now,
                'update_time' => $now,
                'remark' => '下发任务完成',
                'type' => 2
            ];
            AgentMoneyLog::create($agentMoneyLog);

            //分配给自己的任务, 查看还有其他分配没...如果没, 说明打款已经完成,就差自己了, 进入下一步
            $count = MerchantWithdraw::where('settlement_id',$merchant_withdraw['settlement_id'])
                                     ->where('status',1)
                                     ->where('id','<>',$merchant_withdraw_id)
                                     ->count();

            if ($count == 0) {//所有的结算订单都打款成功
                //结算任务--分配给自己和其他代理
                $settlementTask = SettlementTask::lock(true)->find($merchant_withdraw['settlement_id']);
                if($settlementTask->getData('status') !== 1){
                    abort(ReturnCode::PARAM_INVALID, '任务未分配,请分配后再试');
                }

                //新增 已打款状态
                $settlementTask->status = 5;
                $settlementTask->save();

                $count2 = SettlementTask::where([
                    'withdraw_id' => $settlementTask['withdraw_id'],
                    'id' => ['<>',$merchant_withdraw['settlement_id']],
                    'type' => $settlementTask['type'],
                    'status' => ['<>',5]
                ])->count();
                if(empty($count2)){
                    if ($settlementTask['type'] == 1) {//商户
                        $merchant_withdraw_audit = MerchantWithdrawAudit::lock(true)->find($settlementTask['withdraw_id']);
                        //非打款中... 状态异常
                        if($merchant_withdraw_audit->type != 2){
                            abort(ReturnCode::PARAM_INVALID, '操作失败,请重试');
                        }

                        //更新商户提现表状态
                        MerchantWithdrawAudit::where('id',$settlementTask['withdraw_id'])
                                             ->update(['type' => 3, 'update_time' => time()]);

                        Merchant::where('id',$merchant_withdraw_audit->merchant_id)
                                ->setDec('frozen_money',$merchant_withdraw_audit->money);
                        //结算成功,回调商户地址
                        if($merchant_withdraw_audit->callback){
                            //推送并定时器检测回调是否成功
                            $client = new SwooleClientService();
                            $params = [
                                'withdrawId'=>$settlementTask['withdraw_id'],
                            ];

                            $package = $client->package('withdrawCallback',$params);

                            $client->push($package);

                            MerchantCallbakService::withdrawCallback($settlementTask['withdraw_id']);
                        }

                    } elseif ($settlementTask['type'] == 2) {//平台
                        $platform_withdraw = PlatformWithdraw::lock(true)->find($settlementTask['withdraw_id']);
                        if($platform_withdraw->status == 2){
                            abort(ReturnCode::PARAM_INVALID, '操作失败,请重试');
                        }

                        PlatformWithdraw::where('id',$settlementTask['withdraw_id'])
                                        ->update(['status' => 2, 'update_time' => time()]);
                    }
                }
            }
            Db::commit();
            $lock->release($lockKey);
            return $this->buildSuccess([]);
        } catch(\Exception $e) {
            Db::rollback();
            $lock->release($lockKey);
            return $this->buildFailed(ReturnCode::PARAM_INVALID, $e->getMessage());
        }
    }

    /**
     * 结算操作日志
     * @return array
     * @throws \think\exception\DbException
     */
    public function allotLog()
    {
        $page = $this->request->get('page/d',1);
        $limit = $this->request->get('size/d',15);

        $agentAllotLog = new AgentAllotLog();
        $list = $agentAllotLog->where('agent_id','=',$this->parentAgentInfo->id)
                              ->order('create_time desc')
                              ->paginate($limit,false, [
                                  'page' => $page,
                                  'query' => Request::instance()->query()
                              ]);
        return $this->buildSuccess(['list'=>$list->items(),'count' =>$list->total()]);
    }
}
