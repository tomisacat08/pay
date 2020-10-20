<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/14 0014
 * Time: 18:54
 */

namespace app\agent\controller;


use app\agent\model\AgentAccountLog;
use app\agent\model\Config;
use app\agent\model\MemberImages;
use app\agent\model\Merchant;
use app\agent\model\MerchantOrder;
use app\agent\service\MemberService;
use app\util\lock\Lock;
use app\util\ReturnCode;
use PhpOffice\PhpSpreadsheet\IOFactory;
use think\Db;
use think\Request;

class Transaction extends Base
{
    /**
     * 状态帅选
     * @var array
     */
    protected $order_status = [
        //匹配中
        'not_match' => ['status'=>1,'pay_status'=>1],
        //传码超时
        'img_timeout' => ['status'=>-1,'pay_status'=>1],
        //未收款
        'not_payment' => ['status'=>2,'pay_status'=>1],
        //收款超时
        'pay_timeout' => ['status'=>2,'pay_status'=>3],
        //已收款待返款
        'money_receipt' => ['status'=>3,'pay_status'=>2],
        //待确认返款
        'not_refund' => ['status'=>4,'pay_status'=>2,'is_clear'=>2],
        //已返款
        'refund' => ['status'=>4,'pay_status'=>2,'is_clear'=>1],
    ];

    /**
     * 交易记录
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index()
    {
        //请求参数
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
//        $keywords = $this->request->get('keywords', '');//关键字
        $order_id = $this->request->get('order_id/d', 0);//订单id
        $order_sn = $this->request->get('order_sn', '');//订单编号
        $get_money = $this->request->get('get_money', '');//收款金额
        $start_money = $this->request->get('start_money', '');//订单金额
        $merchant_sn = $this->request->get('merchant_sn', '');//商户单号
//        $merchant_uid = $this->request->get('merchant_uid', '');//商户编号
        $daterange = $this->request->get('daterange/a','');
        $order_status = $this->request->get('order_status','');
        $member = $this->request->get('member','');
        $pay_info = trim($this->request->get('pay_info/s', ''));


        //查询条件
        $where['agent_id'] = $this->agent_id;
        //订单id
        if (!empty($order_id)) {
            $where['id'] = $order_id;
        }
        //订单编号
        if (!empty($order_sn)) {
            $where['order_sn'] = ['like','%'.$order_sn.'%'];
        }
        //收款金额
        if (!empty($get_money)) {
            $where['get_money'] = $get_money;
        }
        //收款金额
        if (!empty($start_money)) {
            $where['start_money'] = $start_money;
        }
        //日期
        if(!empty($daterange)){
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between',[$start_time,$end_time]];
        }
        //状态
        if(!empty($order_status)){
            $map = $this->order_status[$order_status];
            $where = array_merge($where,$map);
        }
        //会员
        if(!empty($member)){
            $memberModel = new \app\agent\model\Member();
            //模糊查询
            $maps['mobile|nickname'] = ['like','%'.trim($member).'%'];
            $memberId = $memberModel->where($maps)->column('id');
            if(!empty($memberId)){
                $where['member_id'] = ['IN',$memberId];
            }
        }

        //商户单号
        if (!empty($merchant_sn)) {
            $where['merchant_order_sn'] = ['like','%'.$merchant_sn.'%'];
        }

        //收款信息
        if ($pay_info) {
            $imgIds = \app\model\MemberImages::field('id')->where('account|pay_qrcode_url|bank_account',$pay_info)->select();

            if(empty($imgIds)){
                return $this->buildFailed(500,'收款相关信息未找到!');
            }
            $ids = array_column($imgIds,'id');
            $where['get_money_qrcode_img_id'] = ['in',$ids];
        }


        //商户编号
//        if (!empty($merchant_uid)) {
//            $merchantModel = new \app\model\Merchant();
//            $filter['uid|mobile|nickname'] = ['like', '%' . $merchant_uid . '%'];
//            $merchantId = $merchantModel->where($filter)->column('id');
//            if (!empty($merchantId)) {
//                $where['merchant_id'] = ['IN', $merchantId];
//            }
//        }

        //列表
        $list = MerchantOrder::getOrderList($where, 'id desc', '*', $page, $limit);
//        $memberImages = new MemberImages();
        foreach ($list as $k=>$v){
            $v->append(['order_status']);
//            $img = $memberImages->where(['order_id'=>$v['id'],'member_id'=>$v['member_id'],'type'=>1])
//                ->field('img')->find();
//            $list[$k]['voucher_pic'] = !empty($img['img']) ? $img['img'] : '';
            $list[$k]['member_mobile'] = $v['member_mobile'].'('.$v['member_nickname'].')';
        }
        //统计订单金额
        $total_start_money = (new MerchantOrder())->where($where)->sum('start_money');
        //不随查询条件变动
        /*$success_start_money = (new MerchantOrder())
            ->where('pay_status','=',2)
            ->where('agent_id','=',$this->agent_id)
            ->where('replacement_order','=',2)
            ->sum('start_money');*/
        //随查询条件变动,不包含 pay_status
        unset($where['pay_status']);
        $success_start_money = (new MerchantOrder())
        ->where($where)
        ->where('pay_status',2)
        ->sum('start_money');
        //随查询条件变动,不包含 pay_status,status,is_clear
        /*unset($where['pay_status']);
        unset($where['status']);
        unset($where['is_clear']);
        $success_start_money = (new MerchantOrder())
            ->where($where)
            ->where('pay_status','=',2)
            ->sum('start_money');*/
        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total(),'total_start_money'=>$total_start_money,'success_start_money'=>$success_start_money]);
    }

    /**
     * 指派记录
     * @return array
     * @throws \think\Exception
     */
    public function appoint(){
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);

        $memberModel = new \app\agent\model\Member();
        $memberCount = $memberModel->where('agent_id',$this->agent_id)->count();
        $memberOpenCount = $memberModel->where(['agent_id'=>$this->agent_id,'is_receipt'=>1])->count();

        //查询条件
        $where['agent_id'] = $this->agent_id;
        $where['replacement_order'] = 2;//1:平台补单 2:正常单
        $where['is_help_confirm_order'] = 2;//1:代理帮助确认收款的单 2:正常单
        $where['pay_status'] = ['neq',2];
        $orderCount = (new MerchantOrder())->where($where)->count();

        //列表
        $list = MerchantOrder::getOrderList($where, 'id desc', '*', $page, $limit);
        $time = time();
        $upload_time = (new Config())->where('varname','upload_time')->value('value')*60;
        foreach ($list as $k=>$v){
//            $v->append(['order_status','qr_time']);
            $v->append(['order_status']);
            if(!empty($v['match_time']) && empty($v['upload_time'])){
                $difTime = $time - $v['match_time'];
                if((int)$difTime >= (int) $upload_time) {
                    $list[$k]['diff_time'] = $upload_time.'秒(传码超时)';
                }else{
                    $list[$k]['diff_time'] = $time - $v['match_time'].'秒';
                }
            }elseif (!empty($v['match_time']) && !empty($v['upload_time'])){
                $list[$k]['diff_time'] = $v['upload_time'] - $v['match_time'].'秒';
            }else {
                $list[$k]['diff_time'] = 'N/A';
            }

//            $list[$k]['diff_time'] = $time - $v->getData('create_time');
        }
        return $this->buildSuccess([
            'member_count' => $memberCount,
            'member_open_count' => $memberOpenCount,
            'order_count' => $orderCount,
            'list' => $list->items(),
            'count' => $list->total()
        ]);
    }

    /**
     * 补单记录
     * @return array
     */
    public function replacement()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $keywords = $this->request->get('keywords', '');
        $daterange = $this->request->get('daterange/a','');
        $order_status = $this->request->get('order_status','');

        $where['agent_id'] = $this->agent_id;

//        $where['status'] = ['neq', 4];
        if (!empty($keywords)) {
//            $where['order_sn|merchant_order_sn'] = ['like', '%' . trim($keywords) . '%'];
            $where['id'] = (int)$keywords;
        }
        if(!empty($daterange)){
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between',[$start_time,$end_time]];
        }
        if(!empty($order_status)){
            $map = $this->order_status[$order_status];
            $where = array_merge($where,$map);
        }

        $where['replacement_order'] = 1;//1:平台补单 2:正常单

        $lists = $this->getOrderList($where, $page, $limit);

        $count = count($lists);
        return $this->buildSuccess(['list' => $lists, 'count' => $count]);
    }

    /**
     * @param $replacement
     * @param $help
     * @param $where
     * @param $page
     * @param $limit
     * @return array
     */
    private function getOrderList($where, $page, $limit)
    {
        $list = MerchantOrder::getOrderList($where, 'id desc', '*', $page, $limit);
        foreach ($list as $k=>$v){
            $v->append(['order_status']);
        }
        return $list->items();
    }


    /**
     * 收款员收款超时代理商帮其确认收款
     * @return array
     * @throws \think\exception\DbException
     */
    public function confirmReceipt()
    {
        $order_id = $this->request->post('id/d');

        $lockKey = 'confirmDueIn:'.$order_id;
        $socketLock = new Lock('redis',['namespace'=>'confirmDueIn']);
        $socketLock->get($lockKey);

        Db::startTrans();
        try{
            $memberService = new MemberService();
            $result = $memberService->confirm($order_id,true);
            if ($result !== true) {
                abort(500,$memberService->getError());
            }

            Db::commit();
            $socketLock->release($lockKey);
            return $this->buildSuccess([]);
        }catch (\Exception $e){
            Db::rollback();
            $socketLock->release($lockKey);
            return $this->buildFailed(ReturnCode::INVALID, $e->getMessage());
        }

    }


    /**
     * 普通收款员对账列表 已收到款的记录
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    /**
     * @return array
     */
    public function reconcile()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $orderID = $this->request->get('order_id', '');//订单序号
        $daterange = $this->request->get('daterange/a','');//时间日期
        $nickname = $this->request->get('nickname','');//会员昵称
        $mobile = $this->request->get('mobile','');//会员账户
        $merchant_sn = $this->request->get('merchant_sn','');//商户单号

        //找出普通会员id
        $member_id = \app\agent\model\Member::getSubMemberId(['type' => 1, 'agent_id' => $this->agent_id]);

        $where['member_id'] = ['IN', $member_id];
        $where['agent_id'] = $this->agent_id;
        $where['pay_status'] = 2;
        $where['status'] = 4;
        $where['is_clear'] = 2;
        //订单序号
        if (!empty($orderID)) {
//            $where['order_sn|merchant_order_sn'] = ['like', '%' . trim($keywords) . '%'];
            $where['id'] = (int)$orderID;
        }
        //时间日期
        if(!empty($daterange)){
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between',[$start_time,$end_time]];
        }
        //会员
        if(!empty($nickname)){
            $memberModel = new \app\agent\model\Member();
            //模糊查询
            $maps['nickname'] = ['like','%'.$nickname.'%'];
            $memberId = $memberModel->where($maps)
                ->where(['type' => 1, 'agent_id' => $this->agent_id])
                ->column('id');
            $where['member_id'] = ['IN',$memberId];
        }

        //会员账户
        if(!empty($mobile)){
            $memberModel = new \app\agent\model\Member();
            $filter['mobile'] = $mobile;
            $memberId = $memberModel->where($filter)
                ->where(['type' => 1, 'agent_id' => $this->agent_id])
                ->column('id');
            $where['member_id'] = ['IN',$memberId];
        }

        //商户单号
        if(!empty($merchant_sn)){
            $where['merchant_order_sn'] = ['like','%'.$merchant_sn.'%'];
        }

        $list = MerchantOrder::getOrderList($where, 'id desc', '*', $page, $limit);
//        $memberImages  = new MemberImages();
        foreach ($list as $k=>$v){
            $v->append(['order_status']);
//            $img = $memberImages->where(['order_id'=>$v['id'],'member_id'=>$v['member_id'],'type'=>2])
//                ->field('img')->find();
//            $list[$k]['voucher_pic'] = !empty($img['img']) ? $img['img'] : '';
            $list[$k]['return_time'] = empty($v['return_time']) ? 'N/A' : date("Y-m-d H:i:s",$v['return_time']);
        }
        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

    //会员返款
    public function memberRefund()
    {

    }


    /**
     * 确认已返款（普通会员）
     * @return array
     * @throws \think\exception\DbException
     */
    public function confirmRefund()
    {
        //1.扣除会员账户金额 记录日志
        //2.修改订单状态
        $order_id = $this->request->post('id/d');
        $order = MerchantOrder::get(['id' => $order_id, 'agent_id' => $this->agent_id]);
        if (empty($order)) {
            return $this->buildFailed(ReturnCode::RECORD_NOT_FOUND, '订单不存在');
        }

        if($order['is_clear'] == 1){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '该订单已确认返款，请勿重复操作');
        }

        if($order['status'] != 4 && $order['pay_status'] != 2){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '订单状态异常');
        }

        $memberService = new MemberService();
        $result = $memberService->settleRefund($order);
        if ($result == true) {
            return $this->buildSuccess([]);
        }
        return $this->buildFailed(ReturnCode::INVALID, '操作失败' . $memberService->getError());
    }

    /**
     * 一键确认返款
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function oneKeyConfirm()
    {
        $order_ids = $this->request->post('ids/a');
        $merchantOrder = new MerchantOrder();
        $orders = $merchantOrder->where([
            'agent_id' => $this->agent_id,
            'is_clear' => 2,
            'color_status' => 1
        ])->whereIn('id',$order_ids)->select();
//        return json($orders);
        $memberService = new MemberService();
        $error = '';
        foreach ($orders as $key=>$order)
        {
            $result = $memberService->settleRefund($order);
            if($result !== true){
                $error .= $order['id'].'，';
            }
        }
        if(!empty($error)){
            return $this->buildSuccess([],'订单为：'.$error.' 确认失败');
        }
        return $this->buildSuccess([]);
    }


    /**
     * 普通收款员对账模板
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     */
    public function exportTemplate()
    {
        return $this->buildSuccess([]);
    }


    /**
     * 导入对账单
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     * @throws \think\exception\DbException
     */
    public function importExcel()
    {

        //获取表单上传文件
        $file = request()->file('excel');
        if(empty($file)){
            return $this->buildFailed(ReturnCode::FILE_SAVE_ERROR, '文件上传失败请重新上传');
        }
        $info = $file->rule('uniqid')->validate(['size' => 156780, 'ext' => 'xlsx,xls'])->move(ROOT_PATH . 'public' . DS . 'upload'.DS.'excel');
        if ($info) {
            //获取文件名
            $excelPath = $info->getSaveName();
            //上传文件的地址
            $file_name = ROOT_PATH . 'public' . DS . 'upload'.DS.'excel' . DS . $excelPath;
            //后缀
            //$extension = $info->getExtension();

            $inputFileType = IOFactory::identify($file_name);
            $excelReader = IOFactory::createReader($inputFileType);
            $phpExcel = $excelReader->load($file_name);
            $activeSheet = $phpExcel->getActiveSheet();
            $excel_array = $activeSheet->toArray();

            foreach ($excel_array as $k=>$v)
            {
                if(!empty($v[0]) && !empty($v[1])){
                    $orderId = str_replace(",","",$v[0]);
                    // excel 文本格式特殊处理
                    $returnMoney = str_replace(",","",$v[1]);
                    $order = MerchantOrder::field('return_money')->where(['id'=>$orderId,'agent_id'=>$this->agent_id])->find();
                    if(!empty($order)) {
                        //订单返款金额-对账返款金额
                        $diffMoney = bcsub($order['return_money'],$returnMoney,2);
                        if($diffMoney === "0.00"){
                            $order->color_status = 1;
                            $order->save();
                        }elseif (bccomp($diffMoney,'1') === -1){
                            $order->color_status = 2;
                            $order->save();
                        }
                    }
                }
            }
            //删除excel文件
            unset($info); //一定要unset之后才能进行删除操作，否则请求会被拒绝
            unlink($file_name); //删除上传失败文件
        } else {
            // 上传失败获取错误信息
            return $this->buildFailed(ReturnCode::FILE_SAVE_ERROR, '文件上传失败' . $file->getError());
        }
        return $this->buildSuccess([]);
    }

    /**
     * 对账操作日志
     * @return array
     * @throws \think\exception\DbException
     */
    public function accountLog()
    {
        $page = $this->request->get('page/d',1);
        $limit = $this->request->get('size/d',15);
        $nickname = $this->request->get('nickname','');//会员昵称
        $mobile = $this->request->get('mobile','');//会员账户

        $where = [];
        //会员
        if(!empty($nickname)){
            $memberModel = new \app\agent\model\Member();
            //模糊查询
            $maps['nickname'] = ['like','%'.$nickname.'%'];
            $memberId = $memberModel->where($maps)
                ->where(['type' => 1, 'agent_id' => $this->agent_id])
                ->column('id');
            $where['member_id'] = ['IN',$memberId];
        }

        //会员账户
        if(!empty($mobile)){
            $memberModel = new \app\agent\model\Member();
            $filter['mobile'] = $mobile;
            $memberId = $memberModel->where($filter)
                ->where(['type' => 1, 'agent_id' => $this->agent_id])
                ->column('id');
            $where['member_id'] = ['IN',$memberId];
        }

        $agentAccountLog = new AgentAccountLog();
        $list = $agentAccountLog->where('agent_id','=',$this->agent_id)
            ->where($where)
            ->order('create_time','desc')
            ->paginate($limit,false, [
                'page' => $page,
                'query' => Request::instance()->query()
            ]);
        return $this->buildSuccess(['list'=>$list->items(),'count' =>$list->total()]);
    }
}
