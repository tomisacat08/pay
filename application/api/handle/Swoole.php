<?php

namespace app\api\handle;

use app\api\behavior\Init;
use app\api\swoole\SwooleAfterService;
use app\api\swoole\SwooleLoginService;
use app\api\swoole\SwooleService;

class Swoole
{
    public $processName = 'swooleServer';

    public static $fdHashMapKey = 'SwooleServer_h_fd';
    public $pidKey = 'SwooleServer_h_pidKey';//进程开关

    public static $server;

    /**
     * @var SwooleService
     */
    public $service;
    public $loginService;

    public function __construct()
    {
        $this->service = SwooleService::getInstance();
        $this->loginService = SwooleLoginService::getInstance();
    }

    public function start()
    {
        set_time_limit(0);
        $this->setServer();
    }

    public function stop()
    {
        //批量查杀进程
        //exec("ps -ef |grep {$this->processName} |awk '{print $2}'|xargs kill -9");
        $this->loginService->clearAll();

    }


    public function setServer()
    {
        self::$server = new \Swoole\WebSocket\Server("0.0.0.0", env('swoole.port',9500));
        self::$server->set(
            [
                'enable_static_handler' => true,
                'enable_coroutine' => true,
                'task_enable_coroutine' => true,//开启,支持使用异步,协程
                'worker_num' => 4,
                //'max_request'=>5, //worker最大执行任务数,只能用于同步阻塞、无状态的请求响应式服务器程序,解决PHP进程内存溢出问题
                'task_worker_num' => 20,
                'task_max_request' => 1000,
                'heartbeat_check_interval' => 20,//20秒检查一下心跳
                'heartbeat_idle_time' => 60,//超过60秒无返回, 断开连接
                /*
                'open_tcp_keepalive'=>1,
                'tcp_keepidle'=>60,//连接闲置 60 秒无通信开始检测
                'tcp_keepinterval'=>60, //检测连接可用 间隔60秒请求一次
                'tcp_keepcount'=>5,// 检测可用失败总次数 5次 仍未连通,断开连接
                */
                'dispatch_mode'=>2, //默认固定分配模式
            ]
        );

        self::$server->on("start", [$this, 'onStart']);
        self::$server->on("task", [$this, 'onTask']);
        self::$server->on("finish", [$this, 'onFinish']);
        //监听WebSocket连接打开事件
        self::$server->on('open',[$this, 'onOpen']);
        //监听WebSocket消息事件
        self::$server->on('message', [$this,'onMessage']);
        //监听WebSocket连接关闭事件
        self::$server->on('close', [$this, 'onClose']);
        self::$server->on('workerStop', [$this, 'onWorkerStop']);
        self::$server->on('workerStart', [$this, 'onWorkerStart']);

        self::$server->start();
    }

    public function onStart($server) {
        //清理所有登录信息
        $this->loginService->clear();
        $this->service->swooleInit();
        swoole_set_process_name($this->processName);
    }

    /**
     * 每次task/worker 关闭, 都会执行 onWorkerStop
     * @param \swoole_server $server
     * @param int            $worker_id
     */
    public function onWorkerStop(\swoole_server $server,int $worker_id) {
    }

    /**
     * 每次task/worker 启动, 都会执行 onWorkerStart
     * @param \swoole_server $server
     * @param int            $worker_id
     */
    public function onWorkerStart(\swoole_server $server,int $worker_id) {
    }

    public function onTask(\swoole_server $server, \Swoole\Server\Task $task)
    {
        //来自哪个`Worker`进程
        //$task->workerId;
        //任务的编号
        //$task->id;
        //任务的类型，taskwait, task, taskCo, taskWaitMulti 可能使用不同的 flags
        //$task->flags;
        //任务的数据
        $data = $task->data;

        $option = $data['OP'];
        $params = isset($data['params']) ? $data['params'] : [];
        $fd = $data['fd'];
        //初始化配置config
        (new Init())->run();
        $this->service->run($option,$params,$fd);
        $task->finish(true);
    }

    public function onFinish(\swoole_server $serv, int $task_id, $data)
    {
        SwooleAfterService::getInstance()->run();
    }


    public function onOpen (\swoole_websocket_server $server,$req) {
        //维护一个fd和uid的映射关系, 每次重连重置
        cliDump(date('Y-m-d H:i:s').' - [ '.$req->fd.' ] connect');
    }

    public function onClose ($server, $fd) {
        $this->loginService->logout($fd);
        cliDump(date('Y-m-d H:i:s').' - [ '.$fd.' ] close');
    }

    /**
     * 接收消息,生成task任务,返回ACK
     * @param $server
     * @param $frame
     * @author
     * @date   2019/3/30 14:34
     */
    public function onMessage ($server, $frame) {

        //标准参数验证
        $jsonArr = $this->service->unpack($frame->data);
        if($jsonArr == false){
            cliDump(date('Y-m-d H:i:s').' - [ '.$frame->fd.' ] receive : '.$frame->data);
            $ackData = ['code'=>-1,'msg'=>'params format error','data'=>[]];
            $this->service->push($frame->fd,$ackData);
            return false;
        }

        //回复心跳
        if(in_array($jsonArr['OP'],['heartbeat']) ){
            $ackData = ['code'=>1,'msg'=>'heartbeat ack','data'=>[]];
            $package = $this->service->package($ackData);
            self::$server->push($frame->fd,$package);
            return true;
        }

        cliDump(date('Y-m-d H:i:s').' - [ '.$frame->fd.' ] receive : '.$frame->data);
        //登录验证
        if(in_array($jsonArr['OP'],['memberLogin','merchantLogin','systemLogin'])){
            //建立fd 与 用户 关联
            $check = $this->loginService->login($jsonArr,$frame->fd);
            $ackData = ['code'=>1,'msg'=>'login success','data'=>[]];
            if(!$check){
                $ackData = ['code'=>-1,'msg'=>'login failed!','data'=>[]];
                $this->service->push($frame->fd,$ackData);
                $server->close($frame->fd);//验证失败,断开链路
                return false;
            }

            $this->service->push($frame->fd,$ackData);
            return true;
        }



        //链接登记验证
        $checkLogin = cache('swoole_online_map:'.$frame->fd);
        if( !$checkLogin ){
            $ackData = ['code'=>-1,'msg'=>'check login error!','data'=>[]];
            $this->service->push($frame->fd,$ackData);
            $server->close($frame->fd);//验证失败,断开链路
            return false;
        }

        //task任务执行
        $jsonArr['fd'] = $frame->fd;
        $server->task($jsonArr);
    }

    
}