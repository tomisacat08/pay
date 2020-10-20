<?php

namespace app\api\swoole;

use app\api\handle\Swoole;
use app\model\MerchantOrder;

class SwooleService {

    public static $_instance;
    public static function getInstance()
    {
        if(empty($_instance)){
            self::$_instance = new static();
            return self::$_instance;
        }
        return $_instance;
    }


    public function run($option,$params,$serverFd)
    {
        return (new SwooleFactory($option,$params))->run($serverFd);
    }

    public function package($data,$msg = 'success',$code = 1)
    {
        return returnJson($data,$msg,$code);
    }

    public function unpack($jsonData)
    {
        $arr = getJsonData($jsonData);
        if(
            !$arr ||
            !array_key_exists('NO',$arr) ||
            !array_key_exists('OP',$arr) ||
            !array_key_exists('params',$arr)
        ){
            return false;
        }

        return $arr;
    }

    public function push($fd,$data)
    {
        $package = $this->package($data);
        $isAlive = Swoole::$server->isEstablished($fd);
        if($isAlive){
            cliDump(date('Y-m-d H:i:s').' - [ '.$fd.' ] push    : ',$data);
            return Swoole::$server->push($fd,$package);
        }
        cliDump(date('Y-m-d H:i:s').' - failed push: ',$data);
        return false;
    }

    /**
     * 初始化,swoole服务启动
     * @return bool
     */
    public function swooleInit()
    {
        //将所有未收款状态的订单置为, 收款超时,修复停止服务时定时器异常的问题
        MerchantOrder::where('pay_status',1)->where('status',2)->update(['pay_status'=>3]);
        return false;
    }

}
