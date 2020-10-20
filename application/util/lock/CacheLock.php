<?php
/**
 * Created by PhpStorm.
 * User: 28127
 * Date: 2017/11/30 0030
 * Time: 10:09
 */

namespace app\util\lock;

use think\exception\HttpException;

class CacheLock implements ILock
{

    private $_single;
    private $namespace;
    public function __construct($options = [])
    {
        $this->_single = isset( $options[ 'single' ] ) ? $options[ 'single' ] : false;
        $this->namespace = isset( $options[ 'namespace' ] ) ? $options[ 'namespace' ].':' : '';
    }

    public function getLock($key, $timeout=self::EXPIRE)
    {
        $fullKey = $this->getKey($key);
        $waiTime = 20000;
        $totalWaiTime = 0;
        $time = $timeout*1000000;
        while ($totalWaiTime < $time && false == Cache($fullKey, 1, $timeout)){
            if(!$this->_single){
                throw new HttpException( 'failed' );
            }
            usleep($waiTime);
            $totalWaiTime += $waiTime;
        }
        if ($totalWaiTime >= $time){
            throw new HttpException('can not get lock for waiting '.$timeout.'s.');
        }

    }

    public function releaseLock($key)
    {
        $fullKey = $this->getKey($key);
        Cache($fullKey,null);
    }

    private function getKey($key)
    {
        return $this->namespace.$key;
    }
}