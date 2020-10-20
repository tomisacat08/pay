<?php
/**
 * 工程基类
 * @since   2017/02/28 创建
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\member\controller;
use app\util\ReturnCode;
use think\Controller;

class Base extends Controller {

    private $debug = [];
    protected $userInfo;

    public function _initialize() {
        $ApiAuth = $this->request->header('ApiAuth');
        if ($ApiAuth) {
            $userInfo = cache('member:' . $ApiAuth);
            $this->userInfo = json_decode($userInfo, true);
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

    public function json($data,$msg = 'success',$code = 1){
        if(
            is_array($data) &&
            array_key_exists('code',$data) &&
            array_key_exists('msg',$data) &&
            array_key_exists('data',$data)
        ){
            $return = $data;
        }elseif($data === false){
            $return = [
                'code' => -1,
                'msg'  => 'failed',
            ];
        }else{
            $return = [
                'code' => $code,
                'msg'  => $msg,
                'data' => $data
            ];
        }

        if ($this->debug) {
            $return['debug'] = $this->debug;
        }

        if(empty($return['data'])){
            unset($return['data']);
        }

        return json($return);
    }

}
