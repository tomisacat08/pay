<?php

namespace app\api\service;


class ChannelService {
    public static $channel_config = [
        'alipay_qrcode'  => [ 'name' => '支付宝扫码', 'color' => '#67c2f5' ],//支付宝扫码
        'alipay_account' => [ 'name' => '支付宝转账', 'color' => '#25467a' ],//支付宝扫码
        'alipay_card'    => [ 'name' => '支付宝转卡', 'color' => '#112f7a' ],//支付宝转卡
        'wechat_qrcode'  => [ 'name' => '微信扫码', 'color' => '#17f525' ],//微信扫码
        'wechat_account' => [ 'name' => '微信转账', 'color' => '#17f525' ],//微信转账
        'wechat_card'    => [ 'name' => '微信转卡', 'color' => '#21f5e0' ],//微信转卡
        'union_card'     => [ 'name' => '银联转卡', 'color' => '#f58d5d' ],//卡转卡
    ];

}
