<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   3/07 007 04:30
 */

namespace app\admin\service;

use app\util\GoogleAuthenticator;

class GoogleService
{
    //验证
    public static function check( $secretKey, $code)
    {
        $getCode = self::getGoogleCode($secretKey);
        return $code == $getCode;
    }

    public static function getGoogleQrcode($accountName,$secretKey,$title='',$params=[])
    {
        $ga = new GoogleAuthenticator();
        $qrCodeUrl = $ga->getQRCodeGoogleUrl( $accountName, $secretKey,$title,$params ); //第一个参数是"标识",第二个参数为"安全密匙SecretKey" 生成二维码信息
        return $qrCodeUrl; //Google Charts接口 生成的二维码图片,方便手机端扫描绑定安全密匙SecretKey
    }

    public static function getGoogleCode($secretKey)
    {
        $ga = new GoogleAuthenticator();
        $code = $ga->getCode( $secretKey );
        return $code;
    }
}