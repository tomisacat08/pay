<?php
/**
 * Created by PhpStorm.
 * User: 28127
 * Date: 2017/11/30 0030
 * Time: 10:08
 */

namespace app\util\lock;


use think\Exception;
use think\exception\HttpException;

class FileLock implements ILock
{
    private $_fp;
    private $_single;
    private $_lockPath;

    public function __construct( $options )
    {

        $dir = isset($options['dir']) ? $options['dir'].'/' : '';
        $this->_lockPath = APP_PATH.'util/lock/fileLock/'.$dir;
        if(!is_dir($this->_lockPath)){
            mkdir($this->_lockPath,0777,true);
        }

        $this->_single = isset( $options[ 'single' ] ) ? $options[ 'single' ] : false;
    }

    public function getLock( $key, $timeout = self::EXPIRE )
    {
        $file      = md5( __FILE__ . $key );
        $this->_fp = fopen( $this->_lockPath . $file . '.lock', "w+" );
        if ( $this->_single ) {
            $op = LOCK_EX;
        } else {
            $op = LOCK_EX | LOCK_NB;
        }
        if ( false == flock( $this->_fp, $op, $a ) ) {
            throw new HttpException( 'failed' );
        }

        return true;
    }

    public function releaseLock( $key )
    {
        flock( $this->_fp, LOCK_UN );
        fclose( $this->_fp );
    }
}