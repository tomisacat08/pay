<?php
/**
 * 处理Api接入认证
 * @since   2017-07-25
 * @author  zhaoxiang <zhaoxiang051405@gmail.com>
 */

namespace app\payapi\behavior;
use think\Request;

class ApiAuth {

    /**
     * 默认行为函数
     * @return \think\response\Json
     * @author zhaoxiang <zhaoxiang051405@gmail.com>
     */
    public function run() {
        $request = Request::instance();
        $header = config('apiAdmin.CROSS_DOMAIN');
        $ApiAuth = $request->post('apikey', '');
        $ApiId= $request->post('pay_memberid', '');
        if(empty($ApiAuth) || empty($ApiId)){
            $data = ['code' => '-1', 'msg' => '缺少参数', 'data' => []];
            return json($data, 500);
        }
        if ($ApiAuth && $ApiId) {
            $apikey = db('merchant_payapi')->where(['uid'=>$ApiId])->value('apikey');
            if($apikey == $ApiAuth){
                $data = ['code' => '1', 'msg' => '提交成功', 'data' => []];
                return json($data, 200,$header);
            }else{
                $data = ['code' => '2', 'msg' => '参数错误', 'data' => []];
                return json($data, 200,$header);
            }
        } else {
            $data = ['code' => '-1', 'msg' => '缺少apikey/merchant_id', 'data' => []];
            return json($data, 200,$header);
        }
    }

}
