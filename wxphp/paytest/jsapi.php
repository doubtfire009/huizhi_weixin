<?php 
ini_set('date.timezone','Asia/Shanghai');

include("../pm/conn.php");

//error_reporting(E_ERROR);
require_once "../lib/WxPay.Api.php";
require_once "WxPay.JsApiPay.php";

//require_once 'log.php';

//初始化日志
/*
$logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

//打印输出数组信息

function printf_info($data)
{
    foreach($data as $key=>$value){
        echo "<font color='#00ff55;'>$key</font> : $value <br/>";
    }
}
*/

$orderid = $_GET["orderid"];
$userid = $_COOKIE["userid"];

db_open();

//1, 获取用户openid
$tools = new JsApiPay();
//$openId = $tools->GetOpenid();
if (!isempty($userid))
{
    $openid = getwxuseropenid($userid);
}
$catid = getcatidbyorder($orderid);
$catname = getcatnamebycatid($catid);

//付款金额
$sql="select payment_need from tbl_order where id=:order_id";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":order_id", $orderid);
$sth->execute();
$row = $sth->fetch(PDO::FETCH_NUM);
$payment_need = $row[0]*100; //转化为分

$payment_need = 1; //wluotest

//②、统一下单
$input = new WxPayUnifiedOrder();
$input->SetBody($catname); //商品名称
$input->SetAttach($orderid); //带上订单号
$input->SetOut_trade_no(WxPayConfig::MCHID.date("YmdHis"));
$input->SetTotal_fee($payment_need);
$input->SetTime_start(date("YmdHis"));
$input->SetTime_expire(date("YmdHis", time() + 600));
$input->SetGoods_tag("goods_tag");
$input->SetNotify_url("http://m.huizhish.com/wxphp/paytest/notify.php");
$input->SetTrade_type("JSAPI");
$input->SetOpenid($openid);
$order = WxPayApi::unifiedOrder($input);
//echo '<font color="#f00"><b>统一下单支付单信息</b></font><br/>';
//printf_info($order);
$jsApiParameters = $tools->GetJsApiParameters($order);

//获取共享收货地址js函数参数
$editAddress = $tools->GetEditAddressParameters();

//③、在支持成功回调通知中处理成功之后的事宜，见 notify.php
/**
 * 注意：
 * 1、当你的回调地址不可访问的时候，回调通知会失败，可以通过查询订单来确认支付是否成功
 * 2、jsapi支付时需要填入用户openid，WxPay.JsApiPay.php中有获取openid流程 （文档可以参考微信公众平台“网页授权接口”，
 * 参考http://mp.weixin.qq.com/wiki/17/c0f37d5704f0b64713d5d2c37b468d75.html）
 */
?>
<!DOCTYPE html>
<html lang="zh-cmn-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <title>汇至美家</title>
    <link rel="stylesheet" href="../../style/weui.min.css"/>
    <link rel="stylesheet" href="../../style/mayhomer.css"/>
    <script src="../../js/zepto.min.js"></script>

    <script type="text/javascript">
		//调用微信JS api 支付
		function jsApiCall()
		{
			WeixinJSBridge.invoke(
				'getBrandWCPayRequest',
				<?php echo $jsApiParameters; ?>,
				function(res){
					WeixinJSBridge.log(res.err_msg);

					location.href = "/orderresult.html"+"?fresh="+Math.random();
				}
			);
		}

		function callpay()
		{
			if (typeof WeixinJSBridge == "undefined"){
			    if( document.addEventListener ){
			        document.addEventListener('WeixinJSBridgeReady', jsApiCall, false);
			    }else if (document.attachEvent){
			        document.attachEvent('WeixinJSBridgeReady', jsApiCall); 
			        document.attachEvent('onWeixinJSBridgeReady', jsApiCall);
			    }
			}else{
			    jsApiCall();
			}
		}
	</script>
	<script type="text/javascript">
		//获取共享地址
		function editAddress()
		{
			WeixinJSBridge.invoke(
				'editAddress',
				function(res){
					var value1 = res.proviceFirstStageName;
					var value2 = res.addressCitySecondStageName;
					var value3 = res.addressCountiesThirdStageName;
					var value4 = res.addressDetailInfo;
					var tel = res.telNumber;
					
					alert(value1 + value2 + value3 + value4 + ":" + tel);
				}
			);
		}
		
		window.onload = function(){
			if (typeof WeixinJSBridge == "undefined"){
			    if( document.addEventListener ){
			        document.addEventListener('WeixinJSBridgeReady', editAddress, false);
			    }else if (document.attachEvent){
			        document.attachEvent('WeixinJSBridgeReady', editAddress); 
			        document.attachEvent('onWeixinJSBridgeReady', editAddress);
			    }
			}else{
				editAddress();
			}
		};
	</script>
</head>


<body bgcolor="#f3f3f5">
<div class="weui-flex">
    <div><div class="back"><a href="/order.html"><img src="/images/back.png" style="width:20px;height: 15px;vertical-align: middle;"></a></div></div>
    <div class="weui-flex__item"><div class="titlename" id ="titlename">支付</div></div>
</div>

<div class="weui-form-preview">
    <div class="weui-form-preview__hd paylefttime">
        <div class="weui-form-preview__item">
            <label class="paylefttime">剩余支付时间 <span id="paylefttime">00:30:00</span></label>
        </div>
    </div>
    <div class="weui-form-preview__hd head">
        <div class="weui-form-preview__item">
            <label class="weui-form-preview__label head"><b>账单信息</b></label>
        </div>
    </div>
    <div class="weui-form-preview__bd" id="skulist">

    </div>
    <div class="weui-form-preview__hd total">
        <div class="weui-form-preview__item detail">
            <label class="weui-form-preview__label"></label>
            <span><b>总金额： </b></span><span class="lightfont" id="totalprice"></span>
        </div>
    </div>
</div>


<div class="weui-form-preview paymode">
    <div class="weui-form-preview__hd head">
        <div class="weui-form-preview__item">
            <label class="weui-form-preview__label head"><b>支付方式</b></label>
        </div>
    </div>
    <div class="weui-form-preview__bd paymode">
        <a href="javascript:void(0);" class="weui-media-box weui-media-box_appmsg">
            <div class="weui-media-box__hd paymode">
                <img class="weui-media-box__thumb" src="/images/wechat.png" style="width:2.5em;height:2.5em;vertical-align: middle;">
            </div>
            <div class="weui-media-box__bd">
                <h4 class="weui-media-box__title paymode">微信支付</h4>
                <p class="weui-media-box__desc paymode">推荐安装微信5.0版本以上用户使用</p>
            </div>
            <div class="weui-cells_checkbox paymode" id="zonelist">
                <label class="weui-cell weui-check__label paymode" for="s11">
                    <div class="weui-cell__hd paymode">
                        <input type="radio" class="weui-check" name="checkbox1" id="s11" checked="checked" disabled>
                        <i class="weui-icon-checked checked"></i>
                    </div>
                </label>
            </div>
        </a>
    </div>
</div>

<div class="bottom_flex">
    <div class="weui-flex__item"><div class="bottom_flex_item_div">
        <a href="javascript:void(0)" onclick="callpay()" class="bottom_btn pay_btn">支付</a>
    </div></div>
</div>

<script>
    var maxlefttime = 60*30;//最大30分钟倒计时
    var timerid = 0
    function lefttime()
    {
        if (maxlefttime <= 0) //触发上报，取消订单
        {
            clearInterval(timerid);
            maxlefttime = 0;
            cancelorder();
        }
        maxlefttime--;
        var mins = PrefixInteger(parseInt(maxlefttime/60),2);
        var secs = PrefixInteger(maxlefttime%60,2);
        var left = "00"+":"+mins+":"+secs;
        $('#paylefttime').html(left);

    }

    //数字前补0
    function PrefixInteger(num, n)
    {
        return (Array(n).join(0) + num).slice(-n);
    }

    function cancelorder()
    {
        $.ajax({
            url:'/wxphp/ordercancel.php?fresh='+Math.random()+"&orderid="+orderid,
            async: false,
            cache:false,
            success:function(data) {
                var obj = data;

                //返回order.html
                location.href = "/order.html"+"?fresh="+Math.random();
            }
        });
    }

    <!-- 初始时请求后台 -->
    var orderid;
    if(window.localStorage)
    {
        var storage = window.localStorage;
        orderid = storage["orderid"];
    }

    $.ajax({
        url:'/wxphp/orderpay.php?fresh='+Math.random()+"&orderid="+orderid,
        async: false,
        cache:false,
        success:function(data) {
            var obj = eval("(" + data + ")");

            maxlefttime = obj.maxlefttime;
            timerid = setInterval('lefttime()',1000);

            var payment_need = obj.payment_need;
            $('#totalprice').html('¥'+payment_need);

            var skulist = obj.sku_list;
            for(var i in skulist)
            {
                var each = skulist[i];
                var skuname = each.sku_name;
                var skunums = each.product_num;
                var skuprice = each.product_price;

                var item ='\
                       <div class="weui-form-preview__item detail">\
                            <label class="weui-form-preview__label">'+skuname+'X'+skunums+'</label>\
                            <span class="weui-form-preview__value">¥'+skuprice+'</span>\
                        </div>';
                $('#skulist').append(item);
            }
        }
    });

</script>

</body>
</html>
