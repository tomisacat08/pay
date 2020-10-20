<?php
/**
 * websocket客户端类
 * @date   2019/3/31 21:01
 */

namespace app\api\swoole;


class SwooleClientService
{
    public static $key = 'system';
    public $client;
    public $recv = [];
    public function __construct()
    {
        $this->client = new WebSocketClient('127.0.0.1', env('swoole.port',9500));
        $client = $this->client->connect();
        if (!$client)
        {
            exit("connect failed. Error: {$this->client->socketerrCode}\n");
        }
        //登录验证
        $package = $this->package('systemLogin',["token"=>self::$key]);

        $this->client->send($package);
        $recv = $this->client->recv();
        $data = $this->unpack($recv);

        //登录验证
        if(!$data['code'] || $data['code'] != 1){
            exit("connect failed. Error: {$this->client->socketerrCode}\n");
        }

    }

    public function __destruct()
    {
        $this->client->close();
    }

    public function push($data)
    {
        return $this->client->send($data);
    }

    public function package($op,$params = [],$no = 1)
    {

        $data = [
            'OP'=>$op,
            'params'=>$params,
            'NO'=>$no,
        ];

        return json_encode($data);
    }

    public function unpack($frame)
    {
        if(!isset($frame->data)){
            return [];
        }
        $jsonData = $frame->data;
        $data = json_decode($jsonData,true);
        return $data;
    }

    /**
     * 同步接收数据
     * @param int $timeOut 超时时间
     * @return array|bool|mixed
     * @author
     * @date   2019/06/19 0019 15:58
     */
    public function rec($timeOut = 10)
    {
        $sleepTime = 0;
        $sleepTimeCount = 0;
        do{
            if($sleepTimeCount >= $timeOut){
                break;
            }
            $recv = $this->client->recv();
            if(!empty($recv)){
                return $this->unpack($recv);
            }
            ++$sleepTime;
            $sleepTimeCount += $sleepTime;
            sleep($sleepTime);
        }while(true);

        return false;
    }

}