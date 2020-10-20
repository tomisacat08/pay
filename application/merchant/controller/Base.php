<?php
/**
 * 工程基类
 * @since   2017/02/28 创建
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\merchant\controller;
use app\merchant\service\AuthService;
use app\util\ReturnCode;
use think\Controller;

class Base extends Controller {

    private   $debug = [];
    protected $merchantInfo;
    protected $userInfo;
    protected $isParent = true;

    public function _initialize() {
        $apiAuth = $this->request->header('ApiAuth');
        $userInfo = AuthService::getUserInfoByApiAuth($apiAuth);

        if ($userInfo) {
            $this->userInfo = $userInfo;
            if($this->userInfo->parent_id > 0){
                $this->isParent = false;
                $this->merchantInfo = \app\model\Merchant::field('password,pay_password,apikey,google_secret_key',true)->find($this->userInfo->parent_id);
            }else{
                $this->merchantInfo = $userInfo;
            }

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
