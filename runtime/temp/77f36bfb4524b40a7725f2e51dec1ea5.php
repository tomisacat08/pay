<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:31:"payapi/index/wechat_qrcode.html";i:1591438038;}*/ ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="Content-Language" content="zh-cn">
    <meta name="renderer" content="webkit">
    <title>微信扫码支付</title>
    <script type="text/javascript" src="/payapi/js/jquery.min.js"></script>
    <script type="text/javascript" src="/payapi/js/layer/layer.js"></script>
    <link href="/payapi/css/wechat_pay.css" rel="stylesheet" media="screen">
    <link rel="icon" href="data:image/ico;base64,aWNv">
    <style>
        .shadow {
            -webkit-box-shadow: #666 0px 0px 10px;
            -moz-box-shadow: #666 0px 0px 10px;
            box-shadow: #666 0px 0px 10px;
            background: #FFFFFF;
            width: 325px;
            height: 325px;
        }

        img {
            width: 100%;
        }
        .time-item strong {
            background: #13A500;
            color: #fff;
            line-height: 30px;
            font-size: 20px;
            font-family: Arial;
            padding: 0 10px;
            margin-right: 10px;
            border-radius: 5px;
            box-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        }

        h2 {
            line-height: 50px;
            font-family: "微软雅黑";
            font-size: 16px;
            letter-spacing: 2px;
        }

        .tips p {
            color: red;
            line-height: 40px;
        }

        .tips span {
            color: #53b2f9;
        }

        .tips strong {
            color: #53b2f9;
            font-size: 24px;
            font-weight: bold;
        }

    </style>
</head>
<body>
<div class="body">
    <h1 class="mod-title">
        <span class="ico-wechat"></span><span
            class="text">微信扫码支付</span>
    </h1>
    <div class="mod-ct">
        <div class="amount">￥<b id="money"><?php echo $get_money; ?></b><a id="copyMoney" href="##" style="font-size: 20px;cursor:pointer;">【复制金额】</a></div>
        <div style="color: red;font-size: 25px;">支付过程中,若出现以下提示:</div>
        <div style="font-size: 25px;">1.对方是新注册账户,谨防诈骗,请放心支付!</div>
        <div style="font-size: 25px;">2.[ 电信诈骗\赌博等风险提示 ], 请放心支付!</div>
        <div style="font-size: 25px;">3.若提示 [ 账户收款无权限 ] 请重新发起订单.</div>
        <h2>订单编号：<?php echo $merchant_order_sn; ?></h2>
        <div align="center">
            <div class="shadow">
                <div align="center">
                    <div class="qr-image" id="qrcode">
                        <div id="qrcode_img">
                            <?php if($voucher_pic != ''): ?>
                            <img src="<?php echo $voucher_pic; ?>" style="width:100%;height:100%;"/>
                            <?php else: ?>
                            <img src="../img/wechat.png" style="width:100%;height:100%;"/>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <h2>距离该订单过期还有</h2>
            <div class="time-item">
                <strong id="hours"></strong><span style="font-size:18px">时</span>
                <strong id="minutes"></strong><span style="font-size:18px">分</span>
                <strong id="seconds"></strong><span style="font-size:18px">秒</span>
            </div>

        </div>
        <div class="tip">
            <span class="dec dec-left"></span>
            <span class="dec dec-right"></span>
<!--            <div class="ico-scan"></div>-->
            <div class="tip-text">
                <p>手机充值引导:</p>
                <p style="color: red;font-size: 25px;">①.使用另一台手机直扫</p>
                <p style="font-size: 20px;">②.截屏,从相册选取</p>
            </div>
        </div>
    </div>
    <script type="text/javascript">
        function isMobile() {
            var userAgentInfo = navigator.userAgent;

            var mobileAgents = [ "Android", "iPhone", "SymbianOS", "Windows Phone", "iPad","iPod"];

            var mobile_flag = false;

            //根据userAgent判断是否是手机
            for (var v = 0; v < mobileAgents.length; v++) {
                if (userAgentInfo.indexOf(mobileAgents[v]) > 0) {
                    mobile_flag = true;
                    break;
                }
            }

            var screen_width = window.screen.width;
            var screen_height = window.screen.height;

            //根据屏幕分辨率判断是否是手机
            if(screen_width < 500 && screen_height < 800){
                mobile_flag = true;
            }

            return mobile_flag;
        }


        function copy(str) {
            let oInput = document.createElement('input');
            oInput.value = str;
            document.body.appendChild(oInput);
            oInput.select();
            document.execCommand("Copy");
            oInput.style.display = 'none';
            document.body.removeChild(oInput);
        }

        $(function () {
            $("#copyButton").click(function () {
                let url = $('#copy').text();
                copy(url);
            });

            $("#copy_goto_alipay").click(function () {
                let account = $('#account').text();
                copy(account);
                $(this).html('账号已复制成功');
                // let url = 'alipays://platformapi/startapp?appId=09999988&actionType=toAccount&goBack=NO';
                // window.open(url);
                return false;
            });

            $("#copyMoney").click(function () {
                let money = $('#money').text();
                copy(money);
                $(this).html('【复制成功】');
                return false;
            });

            CountDown();
            timer = setInterval("CountDown()", 1000);
        });

        //倒计时
        var maxtime = 300; //2个小时，按秒计算，自己调整!
        function CountDown() {
            if (maxtime >= 0) {
                hours = Math.floor(maxtime / 60 / 60);
                minutes = Math.floor(maxtime / 60 % 60);
                seconds = Math.floor(maxtime % 60);
                msg = "距离结束还有" + hours + "时" + minutes + "分" + seconds + "秒";
                document.getElementById("hours").innerHTML = hours;
                document.getElementById("minutes").innerHTML = minutes;
                document.getElementById("seconds").innerHTML = seconds;
                // document.all["timer"].innerHTML=msg;
                // console.log(msg)
                --maxtime;
                /*if (maxtime % 5 == 0) {
                    getOrderStatus();
                }*/
            } else {
                clearInterval(timer);
                document.getElementById("hours").innerHTML = 0;
                document.getElementById("minutes").innerHTML = 0;
                document.getElementById("seconds").innerHTML = 0;
                qrcode_timeout();
            }
        }
    </script>

</body>
</html>
