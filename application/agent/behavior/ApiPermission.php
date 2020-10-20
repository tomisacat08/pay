<?php
/**
 * 处理后台接口请求权限
 * @since   2017-07-25
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\agent\behavior;


use app\agent\service\AuthService;
use app\model\AgentAuthGroup;
use app\model\AgentAuthGroupAccess;
use app\model\AgentAuthRule;
use app\util\ReturnCode;
use think\Request;

class ApiPermission {

    /**
     * 用户权限检测
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function run() {
        $request = Request::instance();
        $route = $request->routeInfo();
        $header = config('apiAdmin.CROSS_DOMAIN');
        $agentToken = $request->header('agentToken', '');
        //主账户信息
        $agentInfo = AuthService::getUserInfoByApiAuth($agentToken);

        //子账号信息
        if (!$this->checkAuth($agentInfo, $route['route'])) {
            $data = ['code' => ReturnCode::INVALID, 'msg' => '非常抱歉，您没有权限！', 'data' => []];
            return json($data, 200, $header);
        }
    }

    /**
     * 检测用户权限
     * @param $agentInfo
     * @param $subInfo
     * @param $route
     * @return bool
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    private function checkAuth($agentInfo, $route) {
        //判断是否是子账户登录
        if( $agentInfo->parent_id != 0 ){
            $route = strtolower($route);
            $rules = $this->getAuth($agentInfo->id);
            return in_array($route, $rules);
        }

        return true;

    }

    /**
     * 根据用户ID获取全部权限节点
     * @param $uid
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    private function getAuth($uid) {
        $groups = AgentAuthGroupAccess::get(['uid' => $uid]);
        if (isset($groups) && $groups->groupId) {
            $openGroup = (new AgentAuthGroup())->whereIn('id', $groups->groupId)->where(['status' => 1])->select();
            if (isset($openGroup)) {
                $openGroupArr = [];
                foreach ($openGroup as $group) {
                    $openGroupArr[] = $group->id;
                }
                $allRules = (new AgentAuthRule())->whereIn('groupId', $openGroupArr)->select();
                if (isset($allRules)) {
                    $rules = [];
                    foreach ($allRules as $rule) {
                        $rules[] = strtolower($rule->url);
                    }
                    $rules = array_unique($rules);

                    return $rules;
                } else {
                    return [];
                }
            } else {
                return [];
            }
        } else {
            return [];
        }
    }


}
