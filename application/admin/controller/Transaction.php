<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/14 0014
 * Time: 18:54
 */

namespace app\admin\controller;


use app\agent\model\AgentAccountLog;
use app\agent\model\Config;
use app\agent\model\MemberImages;
use app\agent\model\Merchant;
use app\agent\model\MerchantOrder;
use app\admin\service\MemberService;
use app\model\AdminAccountLog;
use app\util\ReturnCode;
use PhpOffice\PhpSpreadsheet\IOFactory;
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
     * 普通收款员对账列表 已收到款的记录
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function reconcile()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $order_sn = $this->request->get('order_sn', '');
        $member_id = $this->request->get('member_id', '');
        $daterange = $this->request->get('daterange/a','');
        $merchant_uid = $this->request->get('merchant_uid','');//商户编号
        $merchant_sn = $this->request->get('merchant_sn','');//商户单号
        $agent = $this->request->get('agent','');//代理商
        $where = [];
        if ($order_sn) {
            $where['order_sn'] = ['like', "%{$order_sn}%"];
        }
        if ($member_id) {
            $wheres['nickname|mobile'] = ['like', "%{$member_id}%"];
            $ids = db('member')->where($wheres)->column('id');
            if(count($ids) != 0){
                $where['member_id']=['in',$ids];
            }
        }
        //商户单号
        if(!empty($merchant_sn)){
            $where['merchant_order_sn'] = $merchant_sn;
        }
        //商户编号
        if(!empty($merchant_uid)){
            $merchantModel = new \app\model\Merchant();
            $filter['uid|mobile|nickname'] = ['like','%'.$merchant_uid.'%'];
            $merchantId = $merchantModel->where($filter)->column('id');
            if(!empty($merchantId)){
                $where['merchant_id'] = ['IN',$merchantId];
            }
        }

        //代理商
        if(!empty($agent)){
            $agentModel = new \app\model\Agent();
            //模糊查询
            $maps['mobile|nickname'] = ['like','%'.trim($agent).'%'];
            $agentId = $agentModel->where($maps)->column('id');
            if(!empty($agentId)){
                $where['agent_id'] = ['IN',$agentId];
            }
        }
        $where['pay_status'] = 2;
        $where['status'] = 4;
        $where['is_clear'] = 2;
        if(!empty($daterange)){
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['>=',$start_time];
            $where['create_time'] = ['<=',$end_time];
        }

        $list = MerchantOrder::getOrderList($where, 'id desc', '*', $page, $limit);
        foreach ($list as $k=>$v){
            $v->append(['order_status']);
            $list[$k]['return_time'] = empty($v['return_time']) ? 'N/A' : date("Y-m-d H:i:s",$v['return_time']);
            $bank_card = db('bank_card')->where(['type'=>2,'status'=>1,'audit_type'=>1,'uid'=>$list[$k]['agent_id']])->find();
            $list[$k]['bank_card'] = $bank_card['bank_name'] . '-' . $bank_card['bank_address'].'-'.$bank_card['card'].'-'.$bank_card['name'];
        }
        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
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
        $order = MerchantOrder::get(['id' => $order_id]);
        if (empty($order)) {
            return $this->buildFailed(ReturnCode::RECORD_NOT_FOUND, '订单不存在');
        }

        if($order['is_clear'] == 1){
            return $this->buildFailed(ReturnCode::PARAM_INVALID, '该订单已确认返款，请勿重复操作');
        }

        $memberService = new MemberService();
        $result = $memberService->settleRefund($order,$this->userInfo);
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
            'is_clear' => 2,
            'color_status' => 1
        ])->whereIn('id',$order_ids)->select();

        $memberService = new MemberService();
        $error = '';
        foreach ($orders as $key=>$order)
        {
            $result = $memberService->settleRefund($order,$this->userInfo);
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
            return $this->buildFailed(ReturnCode::FILE_SAVE_ERROR, '请上传文件');
        }
        $info = $file->rule('uniqid')->validate(['size' => 156780, 'ext' => 'xlsx,xls'])->move(ROOT_PATH . 'public' . DS . 'upload'.DS.'excel');
        if ($info) {
            //获取文件名
            $excelPath = $info->getSaveName();
            //上传文件的地址
            $file_name = ROOT_PATH . 'public' . DS . 'upload'.DS.'excel' . DS . $excelPath;
            //后缀
            $extension = $info->getExtension();

            $inputFileType = IOFactory::identify($file_name);
            $excelReader = IOFactory::createReader($inputFileType);
            $phpExcel = $excelReader->load($file_name);
            $activeSheet = $phpExcel->getActiveSheet();
            $excel_array = $activeSheet->toArray();

            foreach ($excel_array as $k=>$v)
            {
                if(!empty($v[4]) && !empty($v[10])){
                    $order = MerchantOrder::get(['id'=>$v[10]]);
                    if(!empty($order)) {
                        //订单返款金额-对账返款金额
                        $diffMoney = bcsub($order['return_money'],str_replace(",","",$v[4]),2);
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

        $agentAccountLog = new AdminAccountLog();
        $list = $agentAccountLog
            ->order('create_time desc')
            ->paginate($limit,false, [
                'page' => $page,
                'query' => Request::instance()->query()
            ]);
        return $this->buildSuccess(['list'=>$list->items(),'count' =>$list->total()]);
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
        $memberCount = $memberModel->count();
        $memberOpenCount = $memberModel->where(['is_receipt'=>1])->count();

        //查询条件
        $where['replacement_order'] = 2;
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
}
