<?php
/**
 * 后台操作日志记录
 * @since   2018-02-28
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\agent\behavior;


use app\agent\service\AuthService;
use app\model\AgentMenu;
use app\model\AgentUserAction;
use app\util\ReturnCode;
use think\Request;

class AgentLog {

    /**
     * 代理商后台操作日志记录
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     * @return \think\response\Json
     * @throws \think\Exception
     * @throws \think\exception\DbException
     */
    public function run() {
        $header = config('apiAdmin.CROSS_DOMAIN');
        $request = Request::instance();
        $route = $request->routeInfo();
        $agentToken = $request->header('agentToken', '');
        $agentInfo = AuthService::getUserInfoByApiAuth($agentToken);

        $menuInfo = AgentMenu::where('url', $route['route'])->find();
        if (!$menuInfo) {
            $data = ['code' => ReturnCode::INVALID, 'msg' => '当前路由非法：'. $route['route'], 'data' => []];
            return json($data, 200, $header);
        }

        $menuInfo = $menuInfo->toArray();
        if($route['route'] != 'agent/Transaction/appoint'){
            AgentUserAction::create([
                'actionName' => $menuInfo['name'],
                'uid'        => $agentInfo->id,
                'nickname'   => $agentInfo->nickname,
                'addTime'    => time(),
                'url'        => $route['route'],
                'data'       => json_encode($request->param()),
                'ip'         => Request::instance()->ip(),
                'agent_id'   => $agentInfo['id']
            ]);
        }
    }

}
