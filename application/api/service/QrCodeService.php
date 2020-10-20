<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   2019/04/18 0018 14:09
 */

namespace app\api\service;


use Endroid\QrCode\QrCode;
use Zxing\QrReader;

class QrCodeService
{
    public $uploadPath = '/upload/api/tmpQrCode/';

    public function read($imgPath)
    {

        $qrRead = new QrReader($imgPath, QrReader::SOURCE_TYPE_FILE, false);
        $url = $qrRead->text();

        //支付宝号: https://qr.alipay.com/fkx00019fwnfywxywe5tl41
        //有金额  string(42) "wxp://f2f1nEZYkHDj21fvVxgzBaWZmHq0Qfspxa6g"
        //无金额  string(42) "wxp://f2f0d0CzxDZByXM91YUnt3rsX49KjQwNye2D"
        //检测wxp:// 开头
        //比对识别字符串,是否微信,支付宝链接
        //|| (strpos($url,'wxp://') !== 0 && strpos($url,'https://qr.alipay.com') !== 0 )
        if( empty($url)  ){
            return false;
        }

        return $url;
    }

    public function create($text,$fileName = null,$path = null)
    {
        $qrCode = new QrCode($text);
        if($fileName){
            $path = $path ?: '/upload/api/system/qrcode/';
            if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $path)) {
                mkdir($_SERVER['DOCUMENT_ROOT'] . $path, 0755, true);
            }
            $qrCode->writeFile($_SERVER['DOCUMENT_ROOT'].$path.$fileName);
        }
        return $qrCode->writeDataUri();
    }



}