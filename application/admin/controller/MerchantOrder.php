<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\admin\controller;

use app\admin\service\ExcelService;
use app\admin\service\MerchantOrderService;
use app\model\MemberImages;
use app\model\MerchantOrder as MerchantOrderModel;
use app\model\Member as MemberModel;
use app\model\MerchantWithdraw as MerchantWithdrawModel;
use app\model\MerchantWithdrawAudit as MerchantWithdrawAuditModel;
use app\model\SettlementTask as SettlementTaskModel;
use app\util\ReturnCode;

class MerchantOrder extends Base
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
     * 派单列表
     * @return array|void
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \think\Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function index($params = [])
    {
        $limit = $this->request->get('size/d', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page/d', 1);
        $order_id = trim($this->request->get('id/d', 0));
        $agent_mobile = trim($this->request->get('agent_mobile/d', ''));
        $member_mobile = trim($this->request->get('member_mobile/d', ''));
        $pay_info = trim($this->request->get('pay_info/s', ''));
        $channel = trim($this->request->get('channel/s', ''));
        $daterange = $this->request->get('daterange/a', '');
        $merchant_sn = trim($this->request->get('merchant_sn/s', ''));//商户单号
        $merchant_uid = trim($this->request->get('merchant_uid/d', ''));//商户编号
        $order_status = $this->request->get('order_status/s', '');//订单状态
        $start_money = trim($this->request->get('start_money/f', ''));//订单金额
        $ip = trim($this->request->get('ip/s', ''));//ip
        $excel = $this->request->get('excel/d', 0);//1导出excel表格


        $tipOrder = data_get($params,'tip_order','');//是否标注单

        $where = [];
        //状态
        if ($order_id) {
            $where['id'] = $order_id;
        }


        if ($merchant_uid) {
            $merchantInfo = \app\model\Merchant::where('uid',$merchant_uid)->find();
            if(empty($merchantInfo)){
                $this->buildFailed(500,'商户uid未找到!');
            }
            $where['merchant_id'] = $merchantInfo->id;
        }

        if (!empty($merchant_sn)) {
            $where['merchant_order_sn'] = ['like', "%{$merchant_sn}%"];
        }

        if (!empty($tipOrder)) {
            $where['tip_order'] = $tipOrder;
        }

        //渠道编码
        if ($channel) {
            $where['merchant_order_channel'] = $channel;
        }

        //收款信息
        if ($pay_info) {
            $imgIds = MemberImages::field('id')->where('account|pay_qrcode_url|bank_account',$pay_info)->select();

            if(empty($imgIds)){
                return $this->buildFailed(500,'收款相关信息未找到!');
            }
            $ids = array_column($imgIds,'id');
            $where['get_money_qrcode_img_id'] = ['in',$ids];
        }


        if ($member_mobile) {
            $memberInfo = \app\model\Member::where('mobile',$member_mobile)->find();
            if(empty($memberInfo)){
                $this->buildFailed(500,'收款员账号未找到!');
            }
            $where['member_id'] = $memberInfo->id;
        }

        if ($agent_mobile) {
            $agentInfo = \app\model\Agent::where('mobile',$agent_mobile)->find();
            if(empty($agentInfo)){
                $this->buildFailed(500,'代理账号未找到!');
            }
            $where['agent_id'] = $agentInfo->id;
        }


        if (!empty($start_money)) {
            $where['start_money'] = $start_money;
        }

        if (!empty($ip)) {
            $where['ip'] = $ip;
        }

        $order_num = 0 ;
        $order_money = 0.00;
        if(!empty($order_status)){
            //改变status状态条件
            $map = $this->order_status[$order_status];
            $where = array_merge($where,$map);
        }

        $MerchantOrderModel = new MerchantOrderModel();
        if (!empty($daterange)) {
            $where['create_time'] = ['between',[strtotime($daterange[0]),strtotime($daterange[1])]];
        }

        if( $excel == 1 ){
            if(empty($where)){
                return $this->buildFailed(ReturnCode::INVALID, '请携带搜索条件导出');
            }
            $list = $MerchantOrderModel->with(['merchantInfo','agentInfo','memberInfo'])
                                       ->where($where)
                                       ->order('create_time DESC')
                                       ->select();

            if(empty($list)){
                return $this->buildFailed(ReturnCode::PARAM_INVALID, '暂无数据');
            }
            $listInfo = MerchantOrderService::getOrderList($list);
            $excelService = new ExcelService();
            //设置表头：
            $head = ['订单编号', '商户编号', '商户单号', '订单金额',  '收款金额', '收款人', '代理', '下单时间','确认耗时', '收款状态'];
            //数据中对应的字段，用于读取相应数据：
            $keys = ['id', 'merchantInfo', 'merchant_order_sn', 'start_money','get_money', 'memberInfo', 'agentInfo', 'create_time','confirmInterval', 'order_status_name'];
            $excelService->exportExcel('派单记录', $listInfo, $head, $keys);
            return;
        }

        if( !empty($where) ){
            $totalWhere = $where;
            $totalWhere['pay_status'] = 2;
            unset($totalWhere['status']);
            unset($totalWhere['is_clear']);
            $order_num = $MerchantOrderModel
                ->where($totalWhere)
                ->order('create_time DESC')
                ->count('id');
            $order_money = $MerchantOrderModel
                ->where($totalWhere)
                ->order('create_time DESC')
                ->sum('get_money');
        }

        $listObj = $MerchantOrderModel->with(['merchantInfo','agentInfo','memberInfo'])
                                      ->where($where)
                                      ->order('create_time DESC')
                                      ->paginate($limit, false, ['page' => $start])
                                      ->toArray();

        $listInfo = MerchantOrderService::getOrderList($listObj['data']);

        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj['total'],
            'order_num' => $order_num,
            'order_money' => $order_money,
        ]);
    }

    /**
     * 还款记录列表
     * @return array
     * @author
     */
    /**
     * @return array
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function refunds()
    {
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $order_sn = $this->request->get('order_sn', '');
        $id = $this->request->get('id/d', '');
        $merchant_order_sn = $this->request->get('merchant_order_sn', '');
        $member_id = $this->request->get('member_id', '');
        $daterange = $this->request->get('daterange/a', '');
        $where = [];
        if (!empty($order_sn)) {
            $where['o.order_sn'] = ['like', "%{$order_sn}%"];
        }
        if (!empty($id)) {
            $where['o.id'] = $id;
        }
        if (!empty($merchant_order_sn)) {
            $where['o.merchant_order_sn'] = $merchant_order_sn;
        }
        if (!empty($member_id)) {
            $where['m.nickname|m.mobile'] = ['like', "%{$member_id}%"];
        }
        $where['m.type'] = 1;
        $where['o.pay_status'] = 2;
        $MerchantOrderModel = new MerchantOrderModel();
        if (!empty($daterange)) {
            $MerchantOrderModel = $MerchantOrderModel->whereTime('o.create_time', 'between', [strtotime($daterange[0]), strtotime($daterange[1])]);
        }
        $listObj = $MerchantOrderModel->alias('o')->field('o.*')
            ->join('pay_member m', 'm.id = o.member_id')
            ->where($where)->order('o.create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $over_num = $MerchantOrderModel->alias('o')
            ->join('pay_member m', 'm.id = o.member_id')
            ->where(['o.status' => 4])
            ->where($where)->order('o.create_time DESC')
            ->count('o.id');
        $over_money = $MerchantOrderModel->alias('o')
            ->join('pay_member m', 'm.id = o.member_id')
            ->where(['o.status' => 4])
            ->where($where)->order('o.create_time DESC')
            ->sum('o.return_money');
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key => $val) {
            /*$listInfo[$key]['voucher_pic'] = db('MemberImages')->where('order_id',$listInfo[$key]['id'])->where('member_id',$listInfo[$key]['member_id'])->where('type',2)->value('img');*/
            $listInfo[$key]['poundage'] = $val['start_money'] - $val['money'];
            if ($listInfo[$key]['return_time'] == 0) {
                $listInfo[$key]['return_time'] = null;
            } else {
                $listInfo[$key]['return_time'] = date('Y-m-d H:i:s', $listInfo[$key]['return_time']);
            }
            $member = db('member')->field('mobile,nickname')->where(['id' => $listInfo[$key]['member_id']])->find();
            $listInfo[$key]['member_id'] = $member['mobile'] . '-' . $member['nickname'];
            $agent_id = db('agent')->field('mobile,nickname')->where(['id' => $listInfo[$key]['agent_id']])->find();
            $listInfo[$key]['agent_id'] = $agent_id['mobile'] . '-' . $agent_id['nickname'];
            $merchant = db('merchant')->field('mobile,nickname')->where(['id' => $listInfo[$key]['merchant_id']])->find();
            $listInfo[$key]['merchant_id'] = $merchant['mobile'] . '-' . $merchant['nickname'];
            $listInfo[$key]['pay_status'] = (new MerchantOrderModel())->getOrderStatusAttr('', $listInfo[$key]);
        }
        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj['total'],
            'over_num' => $over_num,
            'over_money' => $over_money,
        ]);
    }

    /**
     * 补单记录
     * @return array
     * @author
     */
    /**
     * @return array
     * @throws \think\exception\DbException
     */
    public function supplement()
    {
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $order_sn = $this->request->get('order_sn', '');
        $id = $this->request->get('id', '');
        $member_id = $this->request->get('member_id', '');
        $daterange = $this->request->get('daterange/a', '');
        $where = [];
        if (!empty($order_sn)) {
            $where['o.order_sn'] = ['like', "%{$order_sn}%"];
        }
        if (!empty($id)) {
            $where['o.id'] = $id;
        }
        if (!empty($member_id)) {
            $where['m.nickname|m.mobile'] = ['like', "%{$member_id}%"];
        }
        $where['o.replacement_order'] = 1;
        $MerchantOrderModel = new MerchantOrderModel();
        if (!empty($daterange)) {
            $MerchantOrderModel = $MerchantOrderModel->whereTime('o.create_time', 'between', [strtotime($daterange[0]), strtotime($daterange[1])]);
        }
        $listObj = $MerchantOrderModel->alias('o')->field('o.*,m.type')
            ->join('pay_member m', 'm.id = o.member_id')
            ->where($where)->order('o.create_time DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();
        $listInfo = $listObj['data'];
        foreach ($listInfo as $key => $val) {
            $listInfo[$key]['poundage'] = $val['start_money'] - $val['money'];
            $member = db('member')->field('mobile,nickname')->where(['id' => $listInfo[$key]['member_id']])->find();
            $listInfo[$key]['member_id'] = $member['mobile'] . '-' . $member['nickname'];
            $agent_id = db('agent')->field('mobile,nickname')->where(['id' => $listInfo[$key]['agent_id']])->find();
            $listInfo[$key]['agent_id'] = $agent_id['mobile'] . '-' . $agent_id['nickname'];
            $merchant = db('merchant')->field('mobile,nickname')->where(['id' => $listInfo[$key]['merchant_id']])->find();
            $listInfo[$key]['merchant_id'] = $merchant['mobile'] . '-' . $merchant['nickname'];
            $listInfo[$key]['pay_status'] = (new MerchantOrderModel())->getOrderStatusAttr('', $listInfo[$key]);
            $listInfo[$key]['type'] = (new MemberModel())->getType($listInfo[$key]['type']);
        }
        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj['total'],
        ]);
    }

    /**
     * 加入移除到问题单
     * @return array
     * @author
     */
    public function joinTip(){
        $id = $this->request->get('id', '');
        $tip_order = $this->request->get('tip_order', '');//2是加入1移除
        $MerchantOrderModel = new MerchantOrderModel();
        $res = $MerchantOrderModel::update([
            'id'         => $id,
            'tip_order'  => $tip_order,
        ]);
        if ($res === false) {
            return $this->buildFailed(ReturnCode::DB_SAVE_ERROR, '操作失败');
        } else {
            return $this->buildSuccess([]);
        }
    }
    /**
     * 问题单列表
     * @return array
     * @author
     */
    public function tipIndex(){
        $params = ['tip_order'=>2];
        return $this->index($params);
    }
}
