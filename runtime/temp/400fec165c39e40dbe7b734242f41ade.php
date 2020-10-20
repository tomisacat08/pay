<?php if (!defined('THINK_PATH')) exit(); /*a:1:{s:32:"payapi/index/bank_card_0607.html";i:1599828190;}*/ ?>
<!DOCTYPE html>
<html lang="en"><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>银行卡转账</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <script type="text/javascript" src="/payapi/js/jquery.min.js"></script>
    <script type="text/javascript" src="/payapi/js/layer/layer.js"></script>
    <script type="text/javascript" src="/payapi/js/clipboard.min.js"></script>

    <link rel="stylesheet" href="/payapi/js/layer/theme/default/layer.css" id="layuicss-layer">

    <link rel="icon" href="data:image/ico;base64,aWNv">

    <style>
        html, body, .main {
            height: 100%;
        }
        a {
            text-decoration:none;
            color: white;
        }
        a:active {
            color:white;
        }
        a:-webkit-any-link {
            color: white;
            cursor: pointer;
        }
        body {
            margin: 0;
            padding: 0px;
            background: #f3f6f7;
        }
        .red{
            color: red;
        }
        .blue{
            color: #0a68fc;
            cursor: pointer;
        }
        .bold{
            font-weight: bold;
        }

        .tit{
            background: white;
            text-align: center;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px 0;
            width: 90%;
            margin: 0 auto;
        }
        .tit span{
            font-size: 30px;
        }
        .tit img{
            width: 40px;
            margin-right: 10px;
        }
        .content{
            padding: 10px 0;
            width: 90%;
            margin: 0 auto;
            background: white;
            margin-top: 20px;
        }
        .list,.listBox{
            padding: 5px 20px;
            display: flex;
            justify-content: space-between;
            align-content: center;
        }
        .listBox{
            padding: 10px 20px;
            flex-direction: column;
            align-items: self-start;
            background: #ddf3f5;
            box-sizing: border-box;
            width: 98%;
            margin: 5PX auto;
            border-radius: 5px;
        }
        .listBox div{
            margin: 5px;
        }
        .listTit{
            color: #3c669f;
            font-weight: bold;
        }
        .listBox div{
            display: flex;
            justify-content: flex-start;
            align-items: center;
        }

        .copy{
            width:55px;
            height: 25px;
            background:#1ea1ff;
            text-align: center;
            color: white;
            padding: 10px;
            box-sizing: border-box;
            margin-left: 10px!important;
            border-radius: 5px;
            cursor: pointer;
        }

        @media screen and (max-width: 414px) {
            .phoneList{
                display: flex;
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
<div class="tit">
    <img src="/payapi/img/bank_card_0607/cny_logo.png">
    <span>支付宝,微信转账</span>
</div>

<div class="content">
    <div class="list ">
        <div class="listTit">银行转账  &nbsp; | &nbsp;收银台</div>
        <div>
            支付倒计时：
            <span class="time red" id="countdown">0:00</span>
        </div>
    </div>

    <div class="list ">
        <div class="listText red">如遇转账24小时到账请联系客服!</div>
    </div>
    <div class="list ">
        <div class="listText red">如遇提示异常，请稍后稍后再试!</div>
    </div>

    <div class="listBox">
        <div class="red">
            <span>支付金额：</span>
            <span id="amount">￥<?php echo $get_money; ?>元</span>
            <div class="copy copy_paymount">复制</div>
            <script>
                var clipboard_payamount = new ClipboardJS('.copy_paymount', {
                    text: function () {
                        return '<?php echo $get_money; ?>';
                    }
                });
                clipboard_payamount.on('success', function (e) { layer.msg('成功复制支付金额');});
                clipboard_payamount.on('error', function (e) { layer.msg('成功复制支付金额');});
            </script>
        </div>
    </div>




    <div class="list phoneList" style="margin-top: 30px;">
        <div class="listText"><span class="bold">收款人银行卡</span></div>
    </div>

    <div class="listBox" style="background: #ddf5df">
        <div>
            <span>卖家：</span>
            <span id="name"><?php echo $bank_account; ?></span>
            <div class="copy copy_BankOwnerName">复制</div>
            <script>
                var clipboard_BankOwnerName = new ClipboardJS('.copy_BankOwnerName', {
                    text: function () {
                        return '<?php echo $bank_account; ?>';
                    }
                });
                clipboard_BankOwnerName.on('success', function (e) { layer.msg('成功复制持卡人姓名');});
                clipboard_BankOwnerName.on('error', function (e) { layer.msg('成功复制持卡人姓名');});
            </script>
        </div>

        <div>
            <span>银行：</span>
            <span id="bank"><?php echo $bank_name; ?></span>
            <div class="copy copy_BankName">复制</div>
            <script>
                var clipboard_BankName = new ClipboardJS('.copy_BankName', {
                    text: function () {
                        return '<?php echo $bank_name; ?>';
                    }
                });
                clipboard_BankName.on('success', function (e) { layer.msg('复制银行名称成功');});
                clipboard_BankName.on('error', function (e) { layer.msg('复制银行名称失败');});
            </script>

        </div>
        <div>
            <span>开户行：</span>
            <span id="opening"><?php echo $bank_desc; ?></span>
            <div class="copy copy_BankBranch">复制</div>
            <script>
                var clipboard_BankBranch = new ClipboardJS('.copy_BankBranch', {
                    text: function () {
                        return '<?php echo $bank_desc; ?>';
                    }
                });
                clipboard_BankBranch.on('success', function (e) { layer.msg('复制开户行成功');});
                clipboard_BankBranch.on('error', function (e) { layer.msg('复制开户行失败');});
            </script>

        </div>

        <div>
            <span>卡号：</span>
            <span id="cardno" style="font-weight:bold;"><?php echo $bank_card; ?></span>
            <div class="copy copy_BankCard">复制</div>
            <script>
                var clipboard_BankCard = new ClipboardJS('.copy_BankCard', {
                    text: function () {
                        return '<?php echo $bank_card; ?>';
                    }
                });
                clipboard_BankCard.on('success', function (e) { layer.msg('成功复制银行卡号');});
                clipboard_BankCard.on('error', function (e) { layer.msg('复制银行卡号失败');});
            </script>
        </div>
    </div>
    <div class="list phoneList" style="margin-top: 30px;">
        <div class="listText"><span class="bold">转账教程</span></div>
    </div>
    <div class="listBox" style="background-color:#FDDD9B;">
        <div>
            <p class="blue zfb_zzjc" style="font-weight: bold;">
                支付宝：<span onclick="window.open('/payapi/img/bank_card_0607/alilc.jpg')">(查看教程)</span>
            </p>
        </div>
        <div>
            <p class="blue zfb_zzjc" style="font-weight: bold;">转账-&gt;转到银行卡-&gt;复制银行卡信息完成转账</p>
        </div>
    </div>
    <div class="listBox" style="background-color:#FDDD9B;">
        <div>
            <p class="green wx_zzjc" style="font-weight: bold;">微信：<span style="cursor:pointer;" onclick="window.open('/payapi/img/bank_card_0607/wxlc.jpg')">（查看教程）</span></p>
        </div>
        <div>
            <p class="green wx_zzjc" style="font-weight: bold;">微信右上角加号-&gt;收付款-&gt;转账到银行卡-&gt;复制银行卡信息完成转账</p>
        </div>
    </div>
    <div class="list ">
        <div class="listText">安全交易须知：</div>
    </div>
    <div class="list ">
        <div class="listText red bold">1、请严格按照页面显示的支付金额转帐（注：包括.小数点后两位数；按规定倒计时内，超时请重新创建订单）否则可能无法即时到账</div>
    </div>

    <div class="list ">
        <div class="listText">2、每次支付 <span class="red">随机匹配</span>的卖家不同，同一个卖家所使用的银行卡可能也不同，<span class="red">请按照每次所显示的付款信息打款，</span>请勿直接到款到之前充值过的卡号， <span class="red">否则可能无法到账，</span>造成的损失平台概不负责</div>
    </div>

    <div class="list ">
        <div class="listText">3、转账时请勿填写任何备注！否则可能导致收款账户和您的账户被冻结，造成的损失平台概不负责。</div>
    </div>

</div>;

<script>
    window.onload=function(){
        var tickTimeId;
        function timeOut(){
            clearTimeout(tickTimeId);
            layer.open({
                content: '<span style="color:#000000;font-size: 16px;"><span style="color:red;font-size: 22px;font-weight: bold;"> 订单已超时,请勿支付! </span></span>',
                btn: ['关闭'],
                yes: function(index,layero){
                    window.close();
                    // layer.close(index);
                }
            });
        }

        let timeOutSec = <?php echo $timeOutOnly; ?>;
        if(timeOutSec < 0){
            timeOut();
        }else{
            timer(timeOutSec);
            layer.open({
                content: '<span style="color:#000000;font-size: 16px;">请务必在规定时间内完成付款<span style="color:red;font-size: 22px;font-weight: bold;"> <?php echo $get_money; ?> </span>元，过期支付或修改金额导致不到帐平台概不负责！</span>'
                ,btn: '我知道了'
            });
        }

        function timer(intDiff) {
            var sTotal = parseInt(intDiff);
            tickTimeId = window.setInterval(function () {
                var minute = 0, second = 0;//时间默认值
                if (sTotal > 0) {
                    day = Math.floor(sTotal / (60 * 60 * 24));
                    hour = Math.floor(sTotal / (60 * 60)) - (day * 24);
                    minute = Math.floor(sTotal / 60) - (day * 24 * 60) - (hour * 60);
                    second = Math.floor(sTotal) - (day * 24 * 60 * 60) - (hour * 60 * 60) - (minute * 60);
                }
                if (minute <= 9) minute = '0' + minute;
                if (second <= 9) second = '0' + second;
                $('#countdown').html(minute + ':' + second);
                sTotal--;
                if (sTotal < 1) {
                    timeOut();
                }
            }, 1000);
        }
    };

</script>
</body></html>