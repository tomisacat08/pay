<?php
/**
 * 后台操作日志管理
 * @since   2018-02-06
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\member\controller;


use app\admin\service\ExcelService;
use app\member\service\LogService;
use app\member\service\memberOrderService;
use app\model\AdminAuthGroupAccess;
use app\model\AdminUser;
use app\model\AdminUserAction;
use app\model\AdminUserData;
use app\model\memberMoneyLog;
use app\model\PlatformMoneyLog;
use app\util\ReturnCode;
use app\util\Tools;

class Log extends Base {

    /**
     * 获取操作日志列表
     * @return array
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function index() {

        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $type = $this->request->get('type', '');
        $keywords = $this->request->get('keywords', '');

        $where = [];
        if ($type) {
            switch ($type) {
                case 1:
                    $where['url'] = ['like', "%{$keywords}%"];
                    break;
                case 2:
                    $where['nickname'] = ['like', "%{$keywords}%"];
                    break;
                case 3:
                    $where['uid'] = $keywords;
                    break;
            }
        }
        $listObj = (new AdminUserAction())->where($where)->order('addTime DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();

        return $this->buildSuccess([
            'list'  => $listObj['data'],
            'count' => $listObj['total']
        ]);
    }

    /**
     * 删除日志
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function del() {
        $id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        AdminUserAction::destroy($id);

        return $this->buildSuccess([]);

    }

    /**
     * 商户资金变动明细
     * @return array
     */
    public function memberLog()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $daterange = $this->request->get('daterange/a', '');//日期
        $orderId = $this->request->get('order_id/d', 0);//订单id
        $type = $this->request->get('type/d', 0);//类型 1:收入 2:支出
        $excel = $this->request->get('excel/d', 0);//1导出excel表格



        $memberId =  $this->userInfo['id'];
        //日期
        if (!empty($daterange)) {
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between', [$start_time, $end_time]];
        }
        if (!empty($type)) {
            $where['type'] = $type;
        }
        if (!empty($orderId)) {
            $where['order_id'] = $orderId;
        }

        if( $excel == 1 ){
            if(empty($where)){
                return $this->buildFailed(ReturnCode::INVALID, '请携带搜索条件导出');
            }

            $list = memberMoneyLog::where('member_id',$memberId)
                                    ->where($where)
                                    ->select();

            if(empty($list)){
                return $this->buildFailed(ReturnCode::PARAM_INVALID, '暂无数据');
            }
            $listInfo = LogService::getLogList($list);
            $excelService = new ExcelService();
            //设置表头：
            $head = ['订单ID', '订单备注', '订单金额','当前余额', '创建时间', '收支类型'];
            //数据中对应的字段，用于读取相应数据：
            $keys = ['order_id', 'remark', 'money','current_money', 'create_time', 'type_name'];
            $excelService->exportExcel('商家台账明细', $listInfo, $head, $keys);
            return;
        }


        $where['member_id'] = $memberId;
        $list = memberMoneyLog::getMoneyLogList($where, $page, $limit);




        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

}
