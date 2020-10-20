<?php
/**
 * 处理Api接入认证
 * @since   2017-07-25
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\agent\behavior;


use app\agent\service\AuthService;
use app\util\ReturnCode;
use think\Db;
use think\Request;

class ApiAuth
{

    /**
     * 默认行为函数
     * @return \think\response\Json
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function run()
    {
        $request = Request::instance();
        $header = config('apiAdmin.CROSS_DOMAIN');
        $apiAuth = $request->header('agentToken', '');
        if (empty($apiAuth)) {
            $data = [ 'code' => ReturnCode::AUTH_ERROR, 'msg' => '登录失效,请重新登录', 'data' => [] ];
            return json( $data, 200, $header );
        }

        $userInfo = AuthService::getUserInfoByApiAuth($apiAuth);

        if(!$userInfo){
            $data = ['code' => ReturnCode::AUTH_ERROR, 'msg' => '登录失效,请重新登录1', 'data' => []];
            return json( $data, 200, $header );
        }

        $user_token = $userInfo->user_token;
        if($apiAuth != $user_token){
            $data = ['code' => ReturnCode::AUTH_ERROR, 'msg' => '您的账号在别处登录2', 'data' => []];
            return json($data, 200, $header);
        }

        if($userInfo->last_login_ip != $request->ip() ){
            $data = ['code' => ReturnCode::AUTH_ERROR, 'msg' => '登录失效,请重新登录3', 'data' => []];
            return json($data, 200, $header);
        }

        $timeout = config('apiAdmin.ONLINE_TIME');
        if(time() - $userInfo->last_login_time > $timeout ){
            $data = ['code' => ReturnCode::AUTH_ERROR, 'msg' => '登录失效,请重新登录4', 'data' => []];
            return json($data, 200, $header);
        }
    }

}
