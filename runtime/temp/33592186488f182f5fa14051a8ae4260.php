<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:35:"payapi/index/alipay_qrcode_new.html";i:1591448917;}*/ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>支付宝扫码支付</title>
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <style>
        * {
            margin: 0;
            padding: 0;
        }

        body {
            width: 100%;
            background: #f9f9f9;
        }

        .phone {
            width: 500px;
            margin: auto;
            padding-top: 5.3125rem;
        }

        header {
            text-align: center;
            background-color: #fff;
            padding: 10px 0;
        }

        header img {
            height: 25px;
        }

        seaction {
            position: relative;
            overflow: hidden;
            width: 100%;
        }

        .backgroundColor {
            background-color: #02A1E2;
            width: 150%;
            height: 500px;
            border-radius: 200%;
            left: -25%;
            top: -300px;
            position: absolute;
        }

        .section {
            width: 100%;
            position: relative;
            overflow: hidden;
        }

        .centent {
            position: relative;
            z-index: 10;
            width: 90%;
            margin: auto;
        }

        .promptFonts {
            color: #F9F30E;
            font-size: 14px;
            font-weight: 600;
            text-align: center;
            letter-spacing: 1px;
            padding: 10px 0;
        }

        .codeBox {
            background-color: #fff;
            padding: 10px;
        }

        .money {
            text-align: center;
            font-size: 35px;
            font-weight: 600;
        }

        .promptFontsRed {
            color: red;
            text-align: left;
            position: relative;
            line-height: 25px;
            font-size: 12px;
            text-indent: 16px;
        }

        .copyMoney {
            color: #34AEE6;
            background: none;
            border: 1px solid #34AEE6;
            padding: 5px 10px;
            letter-spacing: 2px;
            position: absolute;
            right: 0px;
            font-size: 14px;
            display: none;
        }

        .blueBackground {
            width: 75%;
            margin: 20px auto;
            border-radius: 5px;
            background: #02A1E2;
            padding: 15px 0;
            text-align: center;
        }

        .blueBackground img {
            width: 70%;
        }

        .blueBackground .whiteDiv {
            background: #fff;
            border-radius: 100px;
            width: 70%;
            margin: 10px auto 0;
            padding: 5px 10px;
            color: #02A1E2;
            text-align: center;
            font-size: 12px;
        }

        .countdown {
            background: #fff;
            text-align: center;
            margin-top: 10px;
            padding: 10px 0;
        }

        .countdown span {
            display: inline-block;
            vertical-align: middle;
        }

        .countdown .timeBox {
            color: #fff;
            border-radius: 3px;
            background: #2F2B37;
            padding: 5px 10px;
        }

        .warning {
            background: #fff;
            padding: 5px 0;
        }

        .warningList {
            width: 75%;
            margin: auto;
            line-height: 25px;
            font-size: 14px;
        }

        .warningList .bluePint {
            display: inline-block;
            vertical-align: middle;
            width: 5px;
            height: 5px;
            background: #02A1E2;
            border-radius: 100px;
            margin-right: 5px;
        }

        .nonephone {
            display: none;
            line-height: 25px;
            font-size: 15px;
        }

        .openAlipayAPP {
            text-align: center;
            margin-bottom: 10px;
        }

        .openAlipayAPP a {
            background: #1AAD19;
            text-align: center;
            color: #fff;
            box-sizing: border-box;
            font-size: 18px;
            border-radius: 5px;
            text-decoration: none;
            padding: 8px;
        }

        @media screen and (max-width: 500px) {
            .phone {
                width: 100%;
                padding-top: 0;
            }

            .copyMoney {
                display: inline-block;
            }

            .nonephone {
                display: block;
            }
        }
    </style>
</head>
<body>
<div class="phone">
    <header>
        <img src="/payapi/img/timg.jpg" alt="">
    </header>
    <seaction>
        <div class="section">
            <div class="backgroundColor"></div>
            <div class="centent">
                <div class="promptFonts">请按照页面提示金额付款<?php echo $get_money; ?>元，否则不到账</div>
                <div class="codeBox">
                    <div class="money">
                        ￥<span id="moneySpan"><?php echo $get_money; ?></span>
                         <button type="button" class="copyMoney" id="copyMoney" data-clipboard-target="#moneySpan">复制金额</button>
                    </div>
                    <div class="promptFontsRed" style="text-align: center;font-size: 24px;">
                        请使用相机直扫,大幅提高支付效率
                    </div>
                    <div class="blueBackground">
                        <img id="show_qrcode"
                             src="<?php echo $voucher_pic; ?>"
                             alt="">
                        <div id="keyButom" class="whiteDiv" style="display: none">点击跳转支付宝</div>
                    </div>
                    <div class="promptFontsRed nonephone" style="text-align: left;font-size: 25px;color: #8500ff;">
                        特别提示<br>
                        <p style="color: black;font-size: 15px;">使用另一部手机直扫<span
                                style="color: red;font-size: 24px;">100%</span>成功教程如下：</p>
                    </div>
                    <div class="promptFontsRed nonephone">
                        【1】.将二维码发送给另一部手机,或者PC电脑端
                    </div>
                    <div class="promptFontsRed nonephone">
                        【2】.使用支付宝扫一扫, 相机直扫并支付
                    </div>
                </div>
            </div>
            <div class="countdown">
                <span class="timeBox" id="hours">0</span>
                <span>时</span>
                <span class="timeBox" id="minutes">4</span>
                <span>分</span>
                <span class="timeBox" id="seconds">32</span>
                <span>秒</span>
            </div>
            <div class="warning">
                <div class="warningList">
                    <span class="bluePint"></span>此二维码不可多次扫码否则会出现无法到账
                </div>
                <div class="warningList">
                    <span class="bluePint"></span>请付款<?php echo $get_money; ?>元，勿多付少付
                </div>
                <div class="warningList">
                    <span class="bluePint"></span>请在规定时间内及时付款，失效请勿付款
                </div>
                <div class="warningList">
                    <span class="bluePint"></span>付款后长时间未到账，请联系客服
                </div>
            </div>
        </div>
    </seaction>
</div>

<script src="/payapi/js/jquery.min.js"></script>
<script src="/payapi/js/clipboard.min.js"></script>
<script>
    //实例化clipboard
    var clipboard = new ClipboardJS('#copyMoney');
    clipboard.on("success", function (e) {
        alert('复制成功');
        e.clearSelection();
        clipboard.destroy(); // 如果不销毁，第一次以后的复制，会有重复的alert弹出
    });
    clipboard.on("error", function (e) {
        alert("复制失败，请手动复制!");
        clipboard.destroy(); // 如果不销毁，第一次以后的复制，会有重复的alert
    });

    //显示跳转按钮
    let showJumpButton = <?php echo $jumpButton; ?>;
    if(showJumpButton){

        if(isMobile()){
            $('#keyButom').show();

            $('#keyButom').on('click',function(){
                let url = '<?php echo $text; ?>';
                if(url){
                    location.href = '<?php echo $text; ?>';
                }else{
                    location.href = 'alipays://';
                }
            });
        }
    }

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

    CountDown();
    timer = setInterval("CountDown()", 1000);

    /*function getOrderStatus() {
        $.get("/pay/paystatus", {tradeNo: 0}, function (res) {
            if (res.code == 0) 
        });
    }*/

    function qrcode_timeout() {
        $('#show_qrcode').attr("src", "/payapi/img/qrcode_timeout.png");
        alert("二维码过期，请刷新本页或返回前页重新发起订单");
    }
</script>

</body>
</html>