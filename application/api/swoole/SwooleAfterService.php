<?php
/**
 * Created by PhpStorm.
 */

namespace app\api\swoole;


class SwooleAfterService
{
    private static $_instance;

    public static function getInstance(array $work = [])
    {
        if(empty(self::$_instance)){
            self::$_instance = new static();
        }
        return self::$_instance->set($work);
    }

    public function set(array $work)
    {
        RedisService::lPush('afterWork',json_encode($work));
        return $this;
    }

    public function get()
    {
        $jsonWork = RedisService::rPop('afterWork');
        if(!$jsonWork){
            return false;
        }
        return json_decode($jsonWork,true);
    }

    public function run()
    {
        do{
            $item = $this->get();
            if(!$item){
                break;
            }

            $module = $item[0];
            $func = $item[1];
            $params = $item[2];

            switch ($module){
                case 'timer':
                    SwooleTimerService::getInstance()->run($func,$params);
                    break;
            }

        }while(true);

        return true;
    }

}