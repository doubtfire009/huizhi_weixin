<?php

//获取定位信息
include("./pm/conn.php");
require_once "jssdk.php";

$userid = $_COOKIE["userid"];
$openid = $_COOKIE["openid"];
testlog($userid);
testlog($openid);
exit;

db_open();



db_close();
echo $userid;
exit;

$jssdk = new JSSDK($config['appid'], $config['appsecret']);
$signPackage = $jssdk->GetSignPackage();

print_r($signPackage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title></title>
</head>
<body>
  
</body>
<script src="http://res.wx.qq.com/open/js/jweixin-1.0.0.js"></script>
<script>
  /*

Array
(
    [appId] => wx2b18d1f7c60f6a78
    [nonceStr] => jI00NsyrmMIMaEE0
    [timestamp] => 1482199199
    [url] => http://mayhomer.baolink.net/wxphp/index.php
    [signature] => eb54543eb285a6fed4e1a2a6b222636e5ed7231a
    [rawString] => jsapi_ticket=kgt8ON7yVITDhtdwci0qef8MYChSOr4B6HqTCPvBFIxJTW5ORqj44CRdk95YQXyhpqYVtkIp9uvQ_jxGHEam4A&noncestr=jI00NsyrmMIMaEE0×tamp=1482199199&url=http://mayhomer.baolink.net/wxphp/index.php
)

Array
(
    [appId] => wx2b18d1f7c60f6a78
    [nonceStr] => jQ7yeA2UkCWuv97x
    [timestamp] => 1482199031
    [url] => http://mayhomer.baolink.net/wxphp/test.php
    [signature] => c2b948f1a2b01e052f038953530e24fdcc6c2607
    [rawString] => jsapi_ticket=kgt8ON7yVITDhtdwci0qef8MYChSOr4B6HqTCPvBFIxJTW5ORqj44CRdk95YQXyhpqYVtkIp9uvQ_jxGHEam4A&noncestr=jQ7yeA2UkCWuv97x×tamp=1482199031&url=http://mayhomer.baolink.net/wxphp/test.php
)

   */
   //

  wx.config({
    debug: false,
    appId: 'wx2b18d1f7c60f6a78',//'wx2b18d1f7c60f6a78',
          //wx2b18d1f7c60f6a78
    timestamp: 1482199199,//1482198416,
    nonceStr: 'jI00NsyrmMIMaEE0',//'fvWAXmZE8eKrzZGv',
             //Q3UHIqeYXUgof48Y
    signature: 'eb54543eb285a6fed4e1a2a6b222636e5ed7231a',//'48b08346be3abf2ea55cdbea4640f74357ded7bc',
              //fee8c7698f626ad1e1b7fbd7530dd2cc0d27b001
    jsApiList: [
      'getLocation'
    ]
  });
  wx.ready(function () {
        wx.getLocation
        ({
            type: 'wgs84', // 默认为wgs84的gps坐标，如果要返回直接给openLocation用的火星坐标，可传入'gcj02'
            success: function (res)
            {
                latitude = res.latitude; // 纬度，浮点数，范围为90 ~ -90
                longitude = res.longitude; // 经度，浮点数，范围为180 ~ -180。
                
            }
        });
  });
</script>
</html>
