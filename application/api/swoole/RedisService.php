<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   2019/3/31 15:34
 */

namespace app\api\swoole;

class RedisService
{
    public static $handler;
    public function __construct()
    {
        $this->connect();
    }

    public function connect()
    {
        self::$handler =
        $redis = new \Redis();
        $redis->connect(env('redis.host','127.0.0.1'),env('redis.port',6379));
        $redis->auth(env('redis.password','Josss0910'));
        $redis->select(env('redis.select',0));
    }

    public static $_instance;
    public static function getInstance()
    {
        if(empty($_instance)){
            self::$_instance = new static();
            return self::$_instance;
        }
        return $_instance;
    }


    /**
     * 直接调用PHPRedis函数
     * @param $name
     * @param $arguments
     * @return mixed
     * @author
     * @date   2019/3/31 17:52
     */
    public function __call( $name, $arguments )
    {
        try{
            $data = call_user_func_array([self::$handler,$name],$arguments);
        }catch(\RedisException $e){
            if (php_sapi_name() === 'cli') {
                echo $e->getMessage();
            }
            $this->connect();
            $data = call_user_func_array([self::$handler,$name],$arguments);
        }
        return $data;
    }

    /**
     * 直接调用PHPRedis函数
     * @param $name
     * @param $arguments
     * @return mixed
     * @author
     * @date   2019/3/31 17:52
     */
    public static function __callStatic( $name, $arguments )
    {
        //考虑长连接情况,加入断线重连
        $self = self::getInstance();
        try{
            $data = call_user_func_array([$self::$handler,$name],$arguments);
        }catch(\RedisException $e){
            if (php_sapi_name() === 'cli') {
                echo $e->getMessage();
            }
            $self->connect();
            $data = call_user_func_array([$self::$handler,$name],$arguments);
        }
        return $data;
    }


}