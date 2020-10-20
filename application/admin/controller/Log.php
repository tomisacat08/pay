<?php
/**
 * 后台操作日志管理
 * @since   2018-02-06
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\admin\controller;


use app\model\AdminAuthGroupAccess;
use app\model\AdminUser;
use app\model\AdminUserAction;
use app\model\AdminUserData;
use app\model\AgentMoneyLog;
use app\model\MemberMoneyLog;
use app\model\MerchantMoneyLog;
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
     * 会员账户收支明细
     * @return array
     */
    public function memberLog()
    {
        $memberId = $this->request->get('id/d', 0);
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $daterange = $this->request->get('daterange/a', '');//日期
        $type = $this->request->get('type/d', 0);//类型 1:收入 2:支出

        if (empty($memberId)) return $this->buildFailed(ReturnCode::INVALID, '参数异常');

        $where['member_id'] = $memberId;
        //日期
        if (!empty($daterange)) {
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between', [$start_time, $end_time]];
        }
        if (!empty($type)) {
            $where['type'] = $type;
        }
        $list = MemberMoneyLog::getMoneyLogList($where, $page, $limit);

        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

    /**
     * 代理商资金变动明细
     * @return array
     */
    public function agentLog()
    {
        $agentId = $this->request->get('id/d', 0);
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $daterange = $this->request->get('daterange/a', '');//日期
        $type = $this->request->get('type/d', 0);//类型 1:收入 2:支出


        $where['agent_id'] = $agentId;
        //日期
        if (!empty($daterange)) {
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between', [$start_time, $end_time]];
        }
        if (!empty($type)) {
            $where['type'] = $type;
        }
        $list = AgentMoneyLog::getMoneyLogList($where, $page, $limit);

        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

    /**
     * 商户资金变动明细
     * @return array
     */
    public function merchantLog()
    {
        $merchantId = $this->request->get('id/d', 0);
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $daterange = $this->request->get('daterange/a', '');//日期
        $type = $this->request->get('type/d', 0);//类型 1:收入 2:支出


        $where['merchant_id'] = $merchantId;
        //日期
        if (!empty($daterange)) {
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between', [$start_time, $end_time]];
        }
        if (!empty($type)) {
            $where['type'] = $type;
        }
        $list = MerchantMoneyLog::getMoneyLogList($where, $page, $limit);

        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }

    /**
     * 平台资金变动明细
     * @return array
     */
    public function platformLog()
    {
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $daterange = $this->request->get('daterange/a', '');//日期
        $type = $this->request->get('type/d', 0);//类型 1:收入 2:支出

        $where = [];
        //日期
        if (!empty($daterange)) {
            $start_time = strtotime($daterange[0]);
            $end_time = strtotime($daterange[1]);
            $where['create_time'] = ['between', [$start_time, $end_time]];
        }
        if (!empty($type)) {
            $where['type'] = $type;
        }
        $list = PlatformMoneyLog::getMoneyLogList($where, $page, $limit);

        return $this->buildSuccess(['list' => $list->items(), 'count' => $list->total()]);
    }


}
