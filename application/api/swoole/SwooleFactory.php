<?php
/**
 * Created by PhpStorm.
 *
 * @author
 * @date   2019/3/30 16:19
 */

namespace app\api\swoole;


class SwooleFactory
{
    public $option;
    public $params;
    public function __construct($option,$params)
    {
        $this->option = $option;
        $this->params = $params;
    }

    public function run($serverFd)
    {
        $obj = PayService::getInstance($serverFd);
        switch ($this->option){
            case 'getOrder'://会员开始接单
                $obj->getOrder($this->params);
                break;
            case 'addQrcode'://会员接单追加码
                $obj->addQrcode($this->params);
                break;
            case 'delQrcode'://会员接单下码
                $obj->delQrcode($this->params);
                break;
            case 'stopOrder': //会员主动停止接单
                $obj->stopOrder($this->params);
                break;
            case 'addOrder': //商家下单
                $obj->addOrder($this->params);
                break;
            case 'pushImg': //会员上传图片后,给商家推送图片
                $obj->pushImg($this->params);
                break;
            case 'confirmDueIn': //确认收款之后,推送给商家做下一步跳转
                $obj->confirmDueIn($this->params);
                break;
            case 'withdrawCallback': //下发完成,回调通知
                $obj->withdrawCallback($this->params);
                break;
            case 'checkCreateOrder': //确认下单成功
                $obj->checkCreateOrder($this->params);
                break;
            case 'pushMsg': //推送公告信息
                $obj->pushMsg($this->params);
                break;
            default:
        }

        return true;
    }

}