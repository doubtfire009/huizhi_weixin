<?php
//发送手机验证码

include("./pm/conn.php");
include("./taobao-sdk/TopSdk.php");

$mobile = $_GET['mobile']; 
$userid = $_COOKIE["userid"];

//1,生成验证码
$verify = rand(123456, 999999);//获取随机验证码

//发送短信url接口
{
    $c = new TopClient; 
    $appkey='23573620';
    $secret='4c864f0b2623a5d92d92aa1920ddba20';
    $c->appkey = $appkey ;
    $c->secretKey = $secret ;
    $req = new AlibabaAliqinFcSmsNumSendRequest; 
    $req->setExtend(""); 
    $req->setSmsType("normal"); 
    $req->setSmsFreeSignName("身份验证"); 
    $req->setSmsParam("{code:'$verify',product:'汇至美家'}"); 
    $req->setRecNum($mobile); 
    $req->setSmsTemplateCode("SMS_34790357"); 
    $resp = $c->execute($req); 

    $content = (array)$resp;
    $content2 = (array)$content['result'];

    $err_code = $content2['err_code']; 
    $code = $content['code']; 

    print_r($content2);

    if ($err_code == 0) //发送成功
    {
        //设置cookie
        setcookie("mobile", $mobile, time() + 60);
        setcookie("verify", $verify, time() + 60);
    }
    else if ($code > 0)
    {
        echo "3";
    }
}
exit;
?>