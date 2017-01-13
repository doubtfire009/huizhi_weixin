<?php
//获取定位信息 
//首页必须是index.php，因为调用的jssdk里的签名要依赖当前发起签名的URL，如果改用HTML调AJAX的方式则会出现签名URL和展示URL不一致的情况，
//那样JSSDK端口调用会失败。
include("./pm/conn.php");
require_once "jssdk.php";

$jssdk = new JSSDK($config['appid'], $config['appsecret']);
$signPackage = $jssdk->GetSignPackage();

$lastcity = $_GET['city'];

if (isempty($lastcity))
{
    $lastcity="上海"; //wluotest
}

if ($lastcity != "上海")
{
    Header("HTTP/1.1 303 See Other"); 
    Header("Location: ../noservice.html"); 
    //echo "<br><br><br><center><font size=15><b>即将到来，敬请期待！</b></font></center>";
    exit;
}


$randnum = rand();
$indexurl = "/wxphp/index.php?free=".$randnum;
$orderurl = "/order.html?free=".$randnum;
$vipurl = "/vip.html?free=".$randnum;
$myurl = "/my.html?free=".$randnum;

?>
<!DOCTYPE html>
<html lang="zh-cmn-Hans">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
    <title>汇至美家</title>
    <link rel="stylesheet" href="../style/weui.min.css"/>
    <link rel="stylesheet" href="../style/mayhomer.css"/>
    <script src="../js/zepto.min.js"></script>
</head>

<body bgcolor="#f9f8f6" ontouchstart  onload="init()">
<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script charset="utf-8" src="http://map.qq.com/api/js?v=2.exp"></script>
<script type="text/javascript">
    <!--记录城市，当前只在上海 cityid=1-->
    if(window.localStorage)
    {
        var storage = window.localStorage;
        //写入
        storage["cityid"] = 21;
    }

    var latitude, longitude;
    function lbs() 
    {
        wx.config
        ({
            debug: false,
            appId: '<?php echo $signPackage["appId"];?>',
            timestamp: <?php echo $signPackage["timestamp"];?>,
            nonceStr: '<?php echo $signPackage["nonceStr"];?>',
            signature: '<?php echo $signPackage["signature"];?>',
            jsApiList:
            [
            'getLocation'
            ]
        });

        wx.ready(function ()
        {
            wx.getLocation
            ({
                type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
                success: function (res)
                {
                    latitude = res.latitude; // 纬度，浮点数，范围为90 ~ -90
                    longitude = res.longitude; // 经度，浮点数，范围为180 ~ -180。
                    codeLatLng();
                }
            });
        });
    };

    var geocoder = null;
    var city = null;
    var init = function() 
    {
        geocoder = new qq.maps.Geocoder({
            complete : function(result){
                //document.getElementById('city').innerHTML = result.detail.addressComponents.city;
                city = result.detail.addressComponents.city;
                
                var last = city.charAt(city.length - 1);
                if (last == '市')
                {
                    var tmp = city.substring(0, city.length - 1);
                    city = tmp;
                }

                if (city != '<?php echo $lastcity;?>')
                {
                    $("#lbscity").html(city);
                    $("#lbscity").siblings().html(city);

                    var $lbsdialog = $('#lbsdialog');
                    $lbsdialog.fadeIn(200);
                }
            }
        });
    }
    function closedlg() 
    {
        var $lbsdialog = $('#lbsdialog');
        $lbsdialog.fadeOut(200);
    }
    function lbsindex()
    {
        location.href = "index.php?city="+city;
    }

    function category(cid)
    {
        if(window.localStorage)
        {
            var storage = window.localStorage;
            //写入
            storage["cateid"] = cid;
        }

        location.href = "../category.html"+"?fresh="+Math.random();
    }
    function codeLatLng() 
    {
        var latLng = new qq.maps.LatLng(latitude, longitude);
        //调用信息窗口
        //var info = new qq.maps.InfoWindow({map: map});
        //调用获取位置方法
        geocoder.getAddress(latLng);

    }

    window.setTimeout('lbs();',2000);
</script>

        <div>
            <img src = "../images/indexheader.png">
        　　<div style="position:absolute; z-index:2; left:0px; top:0px; width:200px;height: 2px">
                <a class="weui-cell weui-cell_access" href="#">
                    <div class="weui-cell__hd">
                        <img src="../images/lbs.png" alt="" style="width:16px;height:22px;margin-right:5px;display:block">
                    </div>
                    <div style="color:#FFF;font-size:12px;font-weight:bold" id="city"><?php echo $lastcity;?></div>
                </a>
        　　</div>
        </div>
        <div style="margin-top: -20px;">
            <a href="javascript:void(0)" onclick="category(1)"><img src = "../images/indexpanel1.png"></a>
        </div>

        <div>
            <a href="javascript:void(0)" onclick="category(2)"><img src = "../images/indexpanel2.png"></a>
        </div>

        <div>
            <a href="javascript:void(0)" onclick="category(3)"><img src = "../images/indexpanel3.png"></a>
        </div>

        <div>
            <a href="javascript:void(0)" onclick="category(4)"><img src = "../images/indexpanel4.png"></a>
        </div>

        <div style="height: 100px;">

        </div>

        <div class="weui-tab">
            <div class="weui-tabbar">
                <a href="<?php echo $indexurl;?>" class="weui-tabbar__item weui-bar__item_on">
                    <img src="../images/icon_indextab1_on.png" id="img1" alt="" class="weui-tabbar__icon">
                    <p class="weui-tabbar__label">主页</p>
                </a>
                <a href="<?php echo $orderurl;?>" class="weui-tabbar__item">
                    <img src="../images/icon_indextab2.png" id="img2" alt="" class="weui-tabbar__icon">
                    <p class="weui-tabbar__label">订单</p>
                </a>
                <a href="<?php echo $vipurl;?>" class="weui-tabbar__item">
                    <img src="../images/icon_indextab3.png" id="img3" alt="" class="weui-tabbar__icon">
                    <p class="weui-tabbar__label">会员</p>
                </a>
                <a href="<?php echo $myurl;?>" class="weui-tabbar__item">
                    <img src="../images/icon_indextab4.png" id="img4" alt="" class="weui-tabbar__icon">
                    <p class="weui-tabbar__label">我的</p>
                </a>
            </div>
        </div>
        <script type="text/javascript">
            $(function(){
                $('.weui-tabbar__item').on('click', function () {
                    var i = $(this).index();
                    var imgon="";

                    $('#img1').attr('src', "../images/icon_indextab1.png");
                    $('#img2').attr('src', "../images/icon_indextab2.png");
                    $('#img3').attr('src', "../images/icon_indextab3.png");
                    $('#img4').attr('src', "../images/icon_indextab4.png");
                    switch(i)
                    {
                        case 0:
                            imgon = "../images/icon_indextab1_on.png";
                            break;
                        case 1:
                            imgon = "../images/icon_indextab2_on.png";
                            break;
                        case 2:
                            imgon = "../images/icon_indextab3_on.png";
                            break;
                        case 3:
                            imgon = "../images/icon_indextab4_on.png";
                            break;
                    }
                    $(this).children('img').attr('src', imgon);

                    $(this).addClass('weui-bar__item_on').siblings('.weui-bar__item_on').removeClass('weui-bar__item_on');
                });
            });
        </script>

        <div class="js_dialog" id="lbsdialog" style="display: none;">
            <div class="weui-mask"></div>
            <div class="weui-dialog">
                <div class="weui-dialog__hd"><strong class="weui-dialog__title">提示</strong></div>
                <div class="weui-dialog__bd">
                    定位您在<span id="lbscity"></span>, 是否切换到<span id="lbscity"></span>？
                </div>
                <div class="weui-dialog__ft">
                    <a href="javascript:void(0)" onclick="closedlg()" class="weui-dialog__btn weui-dialog__btn_default">取消</a>
                    <a href="javascript:void(0)" onclick="lbsindex()" class="weui-dialog__btn weui-dialog__btn_primary">确定</a>
                </div>
            </div>
        </div>
</body>
</html>
