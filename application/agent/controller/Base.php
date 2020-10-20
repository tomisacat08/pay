<?php
/**
 * 工程基类
 * @since   2017/02/28 创建
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\agent\controller;
use app\agent\model\Agent;
use app\agent\service\AuthService;
use app\util\ReturnCode;
use think\Controller;

class Base extends Controller {

    private $debug = [];
    //代理商主账号信息
    public $parentAgentInfo;
    public $agentInfo;
    //代理商id
    public $agent_id = 0;

    public function _initialize() {
        $agentToken = $this->request->header('agentToken');
        if(empty($agentToken)){
            return false;
        }

        $agentInfo = AuthService::getUserInfoByApiAuth($agentToken);
        if(!is_object($agentInfo)){
            return false;
        }

        $this->agentInfo = $agentInfo;
        $this->agent_id = $agentInfo->id;
        $this->parentAgentInfo = $agentInfo;
        if( $agentInfo->parent_id !== 0 ){
            $this->parentAgentInfo = Agent::find($agentInfo->parent_id);
            $this->agent_id = $agentInfo->parent_id;
        }

    }

    public function buildSuccess($data, $msg = '操作成功', $code = ReturnCode::SUCCESS) {
        $return = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];
        if ($this->debug) {
            $return['debug'] = $this->debug;
        }

        return $return;
    }

    public function buildFailed($code, $msg, $data = []) {
        $return = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];
        if ($this->debug) {
            $return['debug'] = $this->debug;
        }

        return $return;
    }

    protected function debug($data) {
        if ($data) {
            $this->debug[] = $data;
        }
    }

}
