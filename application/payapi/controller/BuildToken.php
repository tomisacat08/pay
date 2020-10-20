<?php
/**
 *
 * @since   2017-10-26
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\payapi\controller;


use app\model\AdminApp;
use app\model\Merchant;
use app\payapi\service\AddOrderService;
use app\util\ApiLog;
use app\util\ReturnCode;
use app\util\Strs;

class BuildToken extends Base {

    /**
     * 构建AccessToken
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function getAccessToken() {
        $param = $this->request->param();
        if (empty($param['uid'])) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少UID');
        }

        if (empty($param['apikey'])) {
            return $this->buildFailed(ReturnCode::EMPTY_PARAMS, '缺少apikey');
        }
        $appInfo = (new Merchant())->field('id,uid,apikey')->where(['uid' => $param['uid'], 'status' => 1,'type'=>1])->find();
        if (empty($appInfo)) {
            return $this->buildFailed(ReturnCode::INVALID, '商户UID非法');
        }
        $checkApiKey = md5($appInfo->apikey);
        if($checkApiKey != $param['apikey'] && $param['apikey'] != $appInfo->apikey){
            return $this->buildFailed(ReturnCode::INVALID, '秘钥验证失败');
        }

        $appInfo = $appInfo->toArray();

        $return = AddOrderService::getAccessToken($appInfo);

        return $this->buildSuccess($return);
    }
}
