<?php
/**
 * Created by PhpStorm.
 * User: 28127
 * Date: 2017/11/30 0030
 * Time: 10:05
 */

namespace app\util\lock;

use think\exception\HttpException;

class Lock
{
    const LOCK_TYPE_DB       = 'SQLLock';
    const LOCK_TYPE_FILE     = 'FileLock';
    const LOCK_TYPE_MEMCACHE = 'CacheLock';
    const LOCK_TYPE_REDIS    = 'RedisLock';

    private        $_lock;
    private static $_supportLocks
        = [
            'file'  => 'FileLock',
            'sql'   => 'SQLLock',
            'cache' => 'CacheLock',
            'redis' => 'RedisLock'
        ];

    public function __construct( $type, $options = [] )
    {
        if ( !empty( $type ) ) {
            $this->set( $type, $options );
        }
    }

    public function set( $type, $options = [] )
    {
        if ( !array_key_exists( $type, self::$_supportLocks ) ) {
            throw new HttpException( "not support lock of ${type}" );
        }
        $className = 'app\util\lock\\'.self::$_supportLocks[$type];
        $this->_lock = new $className( $options );
    }

    public function get( $key, $timeout = ILock::EXPIRE )
    {
        if ( !$this->_lock instanceof ILock ) {
            throw new HttpException( 'false == $this->_lock instanceof ILock' );
        }
        $this->_lock->getLock( $key, $timeout );
    }

    public function release( $key )
    {
        if ( !$this->_lock instanceof ILock ) {
            throw new HttpException( 'false == $this->_lock instanceof ILock' );
        }
        $this->_lock->releaseLock( $key );
    }
}