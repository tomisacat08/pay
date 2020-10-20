<?php
/**
 * 后台操作日志管理
 * @since   2018-02-06
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\agent\controller;

use app\agent\model\AgentMoneyLog;
use app\agent\model\AgentUserAction;
use app\agent\model\MemberMoneyLog;
use app\util\ReturnCode;

class Log extends Base
{

    /**
     * 获取操作日志列表
     * @return array
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function index()
    {

        $limit = $this->request->get('size', config('apiAdmin.ADMIN_LIST_DEFAULT'));
        $start = $this->request->get('page', 1);
        $type = $this->request->get('type', '');
        $keywords = $this->request->get('keywords', '');

        $where = [];
        $where['agent_id'] = $this->agent_id;
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
        $listObj = (new AgentUserAction())->where($where)->order('addTime DESC')
            ->paginate($limit, false, ['page' => $start])->toArray();

        return $this->buildSuccess([
            'list' => $listObj['data'],
            'count' => $listObj['total']
        ]);
    }

    /**
     * 删除日志
     * @return array
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function del()
    {
        /*$id = $this->request->get('id');
        if (!$id) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少必要参数');
        }
        AgentUserAction::destroy($id);

        return $this->buildSuccess([]);*/

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
        $page = $this->request->get('page/d', 1);
        $limit = $this->request->get('size/d', 15);
        $daterange = $this->request->get('daterange/a', '');//日期
        $type = $this->request->get('type/d', 0);//类型 1:收入 2:支出


        $where['agent_id'] = $this->agent_id;
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

}
