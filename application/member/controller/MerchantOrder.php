<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/3/12 0012
 * Time: 10:47
 */

namespace app\member\controller;

use app\admin\service\ExcelService;
use app\member\service\memberOrderService;
use app\api\service\memberCallbakService;
use app\model\memberOrder as memberOrderModel;
use app\model\memberOrderLog as memberOrderLogModel;
use app\util\ReturnCode;

class MerchantOrder extends Base
{

    /**
     * 订单明细
     * @return array
     * @throws \think\exception\DbException
     */
    public function index()
    {
        $limit = $this->request->get('size/d', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page/d', 1);
        $status = $this->request->get('status', '');
        $id = $this->request->get('id', '');//订单编号
        $member_sn = $this->request->get('member_sn', '');
        $daterange = $this->request->get('daterange/a', '');
        $confirmTimedaterange = $this->request->get('confirmTimedaterange/a', '');
        $isReplacement = $this->request->get('isReplacement/d', '');
        $excel = $this->request->get('excel/d', 0);//1导出excel表格
        $where = [];

        if ($status === '1') {
            $where['pay_status'] = 2;
        } elseif ($status === '0') {
            $where['pay_status'] = ['neq', 2];
        }

        if ($id) {
            $where['id'] = $id;
        }

        if (!empty($member_sn)) {
            $where['member_order_sn'] = ['like', '%' . $member_sn . '%'];
        }

        if (!empty($isReplacement)) {
            $where['replacement_order'] = $isReplacement;//1:平台内部补单 2:正常单
        }

        $memberId = $this->userInfo['id'];
        $fields = [
            'id',
            'member_order_sn',
            'member_order_date',
            'start_money',
            'create_time',
            'confirm_time',
            'pay_status',
            'member_order_callbak_confirm_duein',
            'remark'
        ];

        $memberOrderModel = memberOrderModel::field($fields);
        if (!empty($daterange)) {
            $where['create_time'] = ['between',[strtotime($daterange[0]),strtotime($daterange[1])]];
        }

        if (!empty($confirmTimedaterange)) {
            $where['confirm_time'] = ['between',[strtotime($confirmTimedaterange[0]),strtotime($confirmTimedaterange[1])]];
        }

        if( $excel == 1 ){
            if(empty($where)){
                return $this->buildFailed(ReturnCode::INVALID, '请携带搜索条件导出');
            }
            $list = $memberOrderModel->where('member_id',$memberId)
                                       ->where($where)->order('create_time DESC')
                                       ->select();

            if(empty($list)){
                return $this->buildFailed(ReturnCode::PARAM_INVALID, '暂无数据');
            }
            $listInfo = memberOrderService::getOrderList($list);
            $excelService = new ExcelService();
            //设置表头：
            $head = ['订单编号', '商户单号', '订单金额', '下单时间', '确认时间', '收款状态'];
            //数据中对应的字段，用于读取相应数据：
            $keys = ['id', 'member_order_sn', 'start_money', 'member_order_date', 'confirm_time', 'order_status_name'];
            $excelService->exportExcel('商家订单明细', $listInfo, $head, $keys);
            return;
        }

        $order_num = 0 ;
        $order_money = 0.00;
        if( !empty($where) ){
            $totalWhere = $where;
            $totalWhere['pay_status'] = 2;
            $order_num = $memberOrderModel
                ->where('member_id',$memberId)
                ->where($totalWhere)
                ->order('create_time DESC')
                ->count('id');
            $order_money = $memberOrderModel
                ->where('member_id',$memberId)
                ->where($totalWhere)
                ->order('create_time DESC')
                ->sum('start_money');
        }

        $listObj = $memberOrderModel->where('member_id',$memberId)
                                      ->where($where)->order('create_time DESC')
                                      ->paginate($limit, false, ['page' => $start])->toArray();

        $listInfo = memberOrderService::getOrderList($listObj['data']);

        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj['total'],
            'order_num' => $order_num,
            'order_money' => $order_money,
        ]);
    }

    /**
     * 商户对账详情
     * @return array
     * @throws \think\exception\DbException
     */
    public function schedulingdetails()
    {
        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $daterange = $this->request->get('daterange/a', '');
        $where['member_id'] = $this->userInfo['id'];
        if ($daterange) {
            $listObj = (new memberOrderLogModel())->where($where)->whereTime('create_time', 'between', [strtotime($daterange[0]), strtotime($daterange[1])])
                ->order('create_time DESC')
                ->paginate($limit, false, ['page' => $start])
                ->toArray();
        } else {
            $listObj = (new memberOrderLogModel())->where($where)
                ->order('create_time DESC')
                ->paginate($limit, false, ['page' => $start])
                ->toArray();
        }
        $listInfo = $listObj['data'];
        return $this->buildSuccess([
            'list' => $listInfo,
            'count' => $listObj['total'],
        ]);
    }

    public function confirmDueIn(){
        $orderId = $this->request->get('id/d', 1);
        $where['id'] = $orderId;
        $where['member_id'] = $this->userInfo['id'];

        $orderInfo = \app\model\memberOrder::where($where)->find();
        if($orderInfo->pay_status != 2){
            return $this->buildFailed(ReturnCode::INVALID, '发起回调失败,订单状态异常!');
        }

        if(empty($orderInfo->member_order_callbak_confirm_duein)){
            return $this->buildFailed(ReturnCode::INVALID, '回调地址未设置!');
        }

        $postReturn = memberCallbakService::confirmDueIn($orderId);
        $msg = '回调地址 : [ '.$orderInfo->member_order_callbak_confirm_duein.' ] 返回:  '.$postReturn;

        return $this->buildSuccess([],$msg);
    }

}
