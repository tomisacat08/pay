<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
/**
 * 把返回的数据集转换成Tree
 * @param $list
 * @param string $pk
 * @param string $pid
 * @param string $child
 * @param string $root
 * @return array
 */
function listToTree($list, $pk = 'id', $pid = 'fid', $child = '_child', $root = '0')
{
    $tree = array();
    if (is_array($list)) {
        $refer = array();
        foreach ($list as $key => $data) {
            $refer[$data[$pk]] = &$list[$key];
        }
        foreach ($list as $key => $data) {
            $parentId = $data[$pid];
            if ($root == $parentId) {
                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parentId])) {
                    $parent = &$refer[$parentId];
                    $parent[$child][] = &$list[$key];
                }
            }
        }
    }
    return $tree;
}

function formatTree($list, $lv = 0, $title = 'name')
{
    $formatTree = array();
    foreach ($list as $key => $val) {
        $title_prefix = '';
        for ($i = 0; $i < $lv; $i++) {
            $title_prefix .= "|---";
        }
        $val['lv'] = $lv;
        $val['namePrefix'] = $lv == 0 ? '' : $title_prefix;
        $val['showName'] = $lv == 0 ? $val[$title] : $title_prefix . $val[$title];
        if (!array_key_exists('_child', $val)) {
            array_push($formatTree, $val);
        } else {
            $child = $val['_child'];
            unset($val['_child']);
            array_push($formatTree, $val);
            $middle = formatTree($child, $lv + 1, $title); //进行下一层递归
            $formatTree = array_merge($formatTree, $middle);
        }
    }
    return $formatTree;
}

/**
 * 反转义html
 * @param $content
 * @return array|string
 */
function html_decode(&$content)
{
    if (is_array($content)) {
        foreach ($content as $k => &$v) {
            if (is_array($v)) {
                html_decode($v);
            } else {
                $content[$k] = htmlspecialchars_decode($v);
            }
        }
    } else {
        $content = htmlspecialchars_decode($content);
    }
    return $content;
}

/**
 * Luhn算法校验16位或19位银行卡卡号是否有效
 * @param $no
 * @return bool
 */
function checkBankCard($no)
{
    $arr_no = str_split($no);
    $last_n = $arr_no[count($arr_no) - 1];
    krsort($arr_no);
    $i = 1;
    $total = 0;
    foreach ($arr_no as $n) {
        if ($i % 2 == 0) {
            $ix = $n * 2;
            if ($ix >= 10) {
                $nx = 1 + ($ix % 10);
                $total += $nx;
            } else {
                $total += $ix;
            }
        } else {
            $total += $n;
        }
        $i++;
    }
    $total -= $last_n;
    $total *= 9;
    if ($last_n == ($total % 10)) {
        return true;
    }
    return false;
}

//验证手机
function check_mobile($mobile)
{
    if (preg_match('/^1[3456789]\d{9}$/', $mobile))
        return true;
    return false;
}

function uidNo()
{
    return date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 6);
}

/**
 * 生成随机订单号
 * @param
 * @return string
 */
function rand_order()
{
    $str = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 9);
    return $str;
}

if (! function_exists('sql')) {
    function sql(){
        $model = new \app\model\Base();

        var_dump($model->getLastSql());die;
    }
}

if (! function_exists('cliDump')) {
    function cliDump(...$params){
        foreach($params as $var){
            if(is_array($var)){
                echo json_encode($var,JSON_UNESCAPED_UNICODE);
            }else{
                echo $var;
            }
            echo ' - ';
        }
        echo PHP_EOL;
    }
}

if (! function_exists('returnJson')) {
    function returnJson($data,$msg = 'success',$code = 1){
        if(
            is_array($data) &&
            array_key_exists('code',$data) &&
            array_key_exists('msg',$data) &&
            array_key_exists('data',$data)
        ){
            $return = $data;
        }else{
            $return = [
                'code' => $code,
                'msg'  => $msg,
                'data' => $data
            ];
        }

        if(empty($return['data'])){
            unset($return['data']);
        }

        return json_encode($return);
    }
}
if (! function_exists('getJsonData')) {
    function getJsonData($string) {
        $array = json_decode($string,true);
        return (json_last_error() == JSON_ERROR_NONE) ? $array : false;
    }
}

/**
 * 加密函数
 *
 * @param string $txt 需要加密的字符串
 * @param string $key 密钥
 * @return string 返回加密结果
 */
function encrypts($txt, $key = '') {
    if (empty($txt)) return $txt;
    if (empty($key)) $key = md5('MD5_KEY');
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
    $ikey = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
    $nh1 = rand(0, 64);
    $nh2 = rand(0, 64);
    $nh3 = rand(0, 64);
    $ch1 = $chars{$nh1};
    $ch2 = $chars{$nh2};
    $ch3 = $chars{$nh3};
    $nhnum = $nh1 + $nh2 + $nh3;
    $knum = 0;
    $i = 0;
    while (isset($key{$i})) $knum += ord($key{$i++});
    $mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
    $txt = base64_encode(time() . '_' . $txt);
    $txt = str_replace(array('+', '/', '='), array('-', '_', '.'), $txt);
    $tmp = '';
    $j = 0;
    $k = 0;
    $tlen = strlen($txt);
    $klen = strlen($mdKey);
    for ($i = 0; $i < $tlen; $i++) {
        $k = $k == $klen ? 0 : $k;
        $j = ($nhnum + strpos($chars, $txt{$i}) + ord($mdKey{$k++})) % 64;
        $tmp .= $chars{$j};
    }
    $tmplen = strlen($tmp);
    $tmp = substr_replace($tmp, $ch3, $nh2 % ++$tmplen, 0);
    $tmp = substr_replace($tmp, $ch2, $nh1 % ++$tmplen, 0);
    $tmp = substr_replace($tmp, $ch1, $knum % ++$tmplen, 0);
    return $tmp;
}
/**
 * 解密函数
 *
 * @param string $txt 需要解密的字符串
 * @param string $key 密匙
 * @return string 字符串类型的返回结果
 */
function decrypt($txt, $key = '', $ttl = 0)
{
    if (empty($txt)) return $txt;
    if (empty($key)) $key = md5('MD5_KEY');
    $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
    $ikey = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
    $knum = 0;
    $i = 0;
    $tlen = @strlen($txt);
    while (isset($key{$i})) $knum += ord($key{$i++});
    $ch1 = @$txt{$knum % $tlen};
    $nh1 = strpos($chars, $ch1);
    $txt = @substr_replace($txt, '', $knum % $tlen--, 1);
    $ch2 = @$txt{$nh1 % $tlen};
    $nh2 = @strpos($chars, $ch2);
    $txt = @substr_replace($txt, '', $nh1 % $tlen--, 1);
    $ch3 = @$txt{$nh2 % $tlen};
    $nh3 = @strpos($chars, $ch3);
    $txt = @substr_replace($txt, '', $nh2 % $tlen--, 1);
    $nhnum = $nh1 + $nh2 + $nh3;
    $mdKey = substr(md5(md5(md5($key . $ch1) . $ch2 . $ikey) . $ch3), $nhnum % 8, $knum % 8 + 16);
    $tmp = '';
    $j = 0;
    $k = 0;
    $tlen = @strlen($txt);
    $klen = @strlen($mdKey);
    for ($i = 0; $i < $tlen; $i++) {
        $k = $k == $klen ? 0 : $k;
        $j = strpos($chars, $txt{$i}) - $nhnum - ord($mdKey{$k++});
        while ($j < 0) $j += 64;
        $tmp .= $chars{$j};
    }
    $tmp = str_replace(array('-', '_', '.'), array('+', '/', '='), $tmp);
    $tmp = trim(base64_decode($tmp));

    if (preg_match("/\d{10}_/s", substr($tmp, 0, 11))) {
        if ($ttl > 0 && (time() - substr($tmp, 0, 11) > $ttl)) {
            $tmp = null;
        } else {
            $tmp = substr($tmp, 11);
        }
    }
    return $tmp;
}

function getReailFileType( $filename )
{
    $file = fopen( $filename, "rb" );
    $bin  = fread( $file, 2 );
    //只读2字节
    fclose( $file );
    $strInfo  = @unpack( "C2chars", $bin );
    $typeCode = intval( $strInfo[ 'chars1' ] . $strInfo[ 'chars2' ] );
    switch ( $typeCode ) {
        case 7790:
            $fileType = 'exe';
            break;
        case 7784:
            $fileType = 'midi';
            break;
        case 8297:
            $fileType = 'rar';
            break;
        case 255216:
            $fileType = 'jpg';
            break;
        case 7173:
            $fileType = 'gif';
            break;
        case 6677:
            $fileType = 'bmp';
            break;
        case 13780:
            $fileType = 'png';
            break;
        default:
            $fileType = 'unknown';
    }

    return $fileType;
}

function isImage($filename){
    $fileType = getReailFileType($filename);
    return in_array($fileType,['jpg','bmp','png']);
}

//定时任务日志记录

function write_log($logs){
    $content = $logs."\r\n";
    $logSize = 5000000;//5M
    $log = RUNTIME_PATH.'/log/'.'crontab_log.txt';
    if(file_exists($log) && filesize($log) > $logSize){
        $saveName = RUNTIME_PATH.'/log/'.'crontab_'.date("Y-m-d_H-i-s",time()).'.txt';
        copy($log,$saveName);
        unlink($log);
    }
    file_put_contents($log,date('Y-m-d H:i:s')." ".$content.PHP_EOL,FILE_APPEND);
}


if (! function_exists('env')) {
    /**
     * @param      $name
     * @param null $default
     * @return mixed
     */
    function env($name,$default = null) {
        return \think\Env::get($name,$default);
    }
}

if (! function_exists('curl_proxy_post')) {
    /* 提交请求
 * @param $host array 需要配置的域名 array("Host: act.qzone.qq.com");
* @param $data string 需要提交的数据 'user=xxx&qq=xxx&id=xxx&post=xxx'....
* @param $url string 要提交的url 'http://192.168.1.12/xxx/xxx/api/';
*/
    function curl_proxy_post($host,$data='',$url,$port=':80')
    {
        $result="";
        try{
            $ch = curl_init();
            $res= curl_setopt ($ch, CURLOPT_URL,$url);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt ($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
// echo $host.$port;
            curl_setopt($ch, CURLOPT_PROXY, $host.$port);
            $result = curl_exec ($ch);
            curl_close($ch);
        }catch(\Exception $e)
        {
            $result=$e->getMessage();
        }
        if ($result == ""||$result=="1"||$result=="ok") {
            return array('code'=>1,'msg'=>$result);
        }
        return array('code'=>0,'msg'=>$result);
    }
}

if (! function_exists('curl_proxy_get')) {

    /**
     * @brief CURL请求封装方法
     * @param string $url 请求地址
     * @param string $get 请求方式
     * @param string $data 请求数据
     * @return string  返回结果
     */
    function curl_proxy_get($host, $data = '',$url,$headers=array('Accept-Charset: utf-8'),$port=':80')
    {
        $result="";
        try{
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper('get'));
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible;MSIE 5.01;Windows NT 5.0)');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_PROXY, $host.$port);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $result = curl_exec($ch);
            curl_close($ch);
        }catch(\Exception $e)
        {
            $result=$e->getMessage();
        }
        if ($result == ""||$result=="1"||$result=="ok") {
            return array('code'=>1,'msg'=>$result);
        }
        return array('code'=>0,'msg'=>$result);
    }
}

if (! function_exists('curl_get')) {

    function curl_get($url, array $params = array(), $timeout = 5)
    {
        $curl = curl_init();
        $getParams = parse_url($url);
        if($getParams['query']){
            $url .= '&'.http_build_query($params);
        }
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        $tmpInfo = curl_exec($curl);//运行curl
        if (curl_errno($curl)) {
            $tmpInfo = 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl);
        return $tmpInfo;
    }

}
if (! function_exists('curl_post')) {
    function curl_post($url, array $params = array(), $timeout)
    {
        $curl = curl_init();//初始化
        curl_setopt($curl, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);//等待返回超时 60秒
        curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
        $tmpInfo = curl_exec($curl);//运行curl
        if (curl_errno($curl)) {
            $tmpInfo = 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl);
        return $tmpInfo;
    }
}

if (! function_exists('curl_post_json')) {
    function curl_post_json($url, array $params = array(), $timeout = 200,$header = [])
    {

        $data_string = json_encode($params);

        $originHeader = [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data_string)
        ];
        $header = array_merge($originHeader,$header);
        $curl = curl_init();//初始化

        if(strpos($url,'https') !== false){
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        }

        curl_setopt($curl, CURLOPT_URL, $url);//抓取指定网页
        curl_setopt($curl, CURLOPT_HEADER, 0);//设置header
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeout);//连接超时 秒
        curl_setopt($curl, CURLOPT_TIMEOUT, 60);//等待返回超时 60秒
        curl_setopt($curl, CURLOPT_POST, 1);//post提交方式
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);

        $tmpInfo = curl_exec($curl);//运行curl
        if (curl_errno($curl)) {
            $tmpInfo = 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl);
        return $tmpInfo;
    }
}

if (! function_exists('curl_get_https')) {
    function curl_get_https($url,$data)
    {
        $curl = curl_init(); // 启动一个CURL会话
        curl_setopt($curl, CURLOPT_URL, $url); // 要访问的地址
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0); // 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); // 模拟用户使用的浏览器
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, 1); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, 1); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 30); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, 0); // 显示返回的Header区域内容
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // 获取的信息以文件流的形式返回
        $tmpInfo = curl_exec($curl); // 执行操作
        if (curl_errno($curl)) {
            $tmpInfo = 'Errno'.curl_error($curl);//捕抓异常
        }
        curl_close($curl); // 关闭CURL会话
        return $tmpInfo; // 返回数据，json格式
    }

}


if (! function_exists('data_get')) {
    function data_get($data, $key, $default = null)
    {
        $lvKeys = explode('.',$key);
        foreach($lvKeys as $lvKey){

            if(is_array($data) && array_key_exists($lvKey,$data)){
                $data = $data[$lvKey];
                continue;
            }

            if( is_object($data) && isset($data->{$lvKey}) ){
                $data = $data->{$lvKey};
                continue;
            }

            return $default;
        }

        return $data;


    }
}

/**
 *      把秒数转换为时分秒的格式
 *      @param Int $times 时间，单位 秒
 *      @return String
 */
if (! function_exists('secToTime')) {
    function secToTime($times){
        $result = '0秒';
        if ( $times > 0 ) {
            $str = '';
            $hour = floor($times/3600);
            if($hour){
                $str .= $hour.'小时';
            }

            $minute = floor(($times-3600 * $hour)/60);
            if($minute){
                $str .= $minute.'分';
            }

            $second = floor((($times-3600 * $hour) - 60 * $minute) % 60);
            if($second){
                $str .= $second.'秒';
            }

            $result = !empty($str) ? $str : $result;
        }
        return $result;
    }
}

if (! function_exists('timeBefore')) {
    function timeBefore($beforeTimeStamp)
    {
        $time = time() - $beforeTimeStamp ;
        if($time < 60)
        {
            $result = $time.'秒前';
        }
        else if($time < 1800)
        {
            $result = floor($time/60).'分钟前';
        }
        else if($time < 3600)
        {
            $result = '半小时前';
        }
        else if($time < 86400)
        {
            $result = floor($time/3600).'小时前';
        }
        else
        {
            $qt = strtotime(date('Y-m-d 00:00:00',strtotime("-1 day")));
            $st = strtotime(date('Y-m-d 00:00:00',strtotime("-2 day")));
            $bt = strtotime(date('Y-m-d 00:00:00',strtotime("-7 day")));
            if( $beforeTimeStamp < $bt)
            {
                $result = date('Y-m-d H:i:s', $beforeTimeStamp);
            }
            else if($beforeTimeStamp < $st)
            {
                $result = floor($time/86400).'天前';
            }
            else if($beforeTimeStamp < $qt)
            {
                $result = "前天".date('H:i', $beforeTimeStamp);
            }
            else
            {
                $result = '昨天'.date('H:i', $beforeTimeStamp);
            }
        }
        return $result;
    }
}


