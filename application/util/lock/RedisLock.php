<?php
/**
 * Created by PhpStorm.
 * User: 28127
 * Date: 2017/11/30 0030
 * Time: 10:09
 */

namespace app\util\lock;

use app\api\swoole\RedisService;
use think\exception\HttpException;

class RedisLock implements ILock
{

    private $_single;
    private $namespace;
    private $redis;
    public function __construct($options = [])
    {
        $this->_single = isset( $options[ 'single' ] ) ? $options[ 'single' ] : false;
        $this->namespace = isset( $options[ 'namespace' ] ) ? $options[ 'namespace' ].':' : '';
        $this->redis = new RedisService();

    }

    public function getLock($key, $timeout=self::EXPIRE)
    {
        $fullKey = $this->getKey($key);

        $timeoutAt = time()+$timeout;


        while( $this->redis->setnx($fullKey, $timeoutAt) == false){

            $now = time();
            if($now > $this->redis->get($fullKey) && $now > $this->redis->getset($fullKey, $timeoutAt)){
                //锁超时,自动释放
                break;
            }else{
                if($this->_single){
                    throw new HttpException( 'lock is single!' );
                }

                sleep(1);
            }
        }
    }

    public function releaseLock($key)
    {
        $fullKey = $this->getKey($key);
        $this->redis->del($fullKey);
    }

    private function getKey($key)
    {
        return 'lock:'.$this->namespace.$key;
    }
}