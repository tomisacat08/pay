<?php

namespace app\intermediary\controller;


use app\util\ReturnCode;

class Index extends Base {
    //首页
    public function index() {
        //未结算余额
        $userInfo = $this->userInfo;
        $data['nickname'] = $userInfo['nickname'];
        $merchant_ids = db('merchant')->where(['intermediary_id'=>$userInfo['id']])->column('id');
        $data['merchant_num'] = count($merchant_ids);
        $data['today_order_num'] = db('merchant_order')->where(['merchant_id'=>['in',$merchant_ids]])->whereTime('create_time', 'today')->count('id');
        $data['today_over_order_num'] = db('merchant_order')->where(['pay_status'=>2,'merchant_id'=>['in',$merchant_ids]])->whereTime('create_time', 'today')->count('id');
        $data['today_order_money'] = sprintf("%.2f",db('merchant_order')->where(['merchant_id'=>['in',$merchant_ids]])->whereTime('create_time', 'today')->sum('start_money'));
        $data['today_over_order_money'] = sprintf("%.2f",db('merchant_order')->where(['pay_status'=>2,'merchant_id'=>['in',$merchant_ids]])->whereTime('create_time', 'today')->sum('start_money'));

        $data['order_num'] = db('merchant_order')->where(['merchant_id'=>['in',$merchant_ids]])->count('id');
        $data['over_order_num'] = db('merchant_order')->where(['pay_status'=>2,'merchant_id'=>['in',$merchant_ids]])->count('id');
        $data['order_money'] = sprintf("%.2f",db('merchant_order')->where(['merchant_id'=>['in',$merchant_ids]])->sum('start_money'));
        $data['over_order_money'] = sprintf("%.2f",db('merchant_order')->where(['pay_status'=>2,'merchant_id'=>['in',$merchant_ids]])->sum('start_money'));
        $data['notice'] = db('notice')->field('id,title,content,create_time')->where(['merchant_status'=>1])->order('is_top asc,create_time desc')->select();
        foreach ($data['notice'] as $key=>&$val){
            $val['create_time'] = date('Y-m-d H:i:s',$val['create_time']);
        }
        return json(['code' => '1', 'msg' => '请求成功,', 'data' => $data]);
    }

    public function upload() {
        $path = '/upload/intermediary/' . date('Ymd', time()) . '/';
        $name = $_FILES['file']['name'];
        $tmp_name = $_FILES['file']['tmp_name'];
        $error = $_FILES['file']['error'];
        //过滤错误
        if ($error) {
            switch ($error) {
                case 1 :
                    $error_message = '您上传的文件超过了PHP.INI配置文件中UPLOAD_MAX-FILESIZE的大小';
                    break;
                case 2 :
                    $error_message = '您上传的文件超过了PHP.INI配置文件中的post_max_size的大小';
                    break;
                case 3 :
                    $error_message = '文件只被部分上传';
                    break;
                case 4 :
                    $error_message = '文件不能为空';
                    break;
                default :
                    $error_message = '未知错误';
            }
            die($error_message);
        }
        $arr_name = explode('.', $name);
        $hz = array_pop($arr_name);
        $new_name = md5(time() . uniqid()) . '.' . $hz;
        if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
            mkdir($_SERVER['DOCUMENT_ROOT'] . $path, 0755, true);
        }
        if (move_uploaded_file($tmp_name, $_SERVER['DOCUMENT_ROOT'] . $path . $new_name)) {
            return $this->buildSuccess([
                'fileName' => $new_name,
                'filePath'  => $path . $new_name,
                'fileUrl'  => $this->request->domain() . $path . $new_name
            ]);
        } else {
            return $this->buildFailed(ReturnCode::FILE_SAVE_ERROR, '文件上传失败');
        }
    }
}