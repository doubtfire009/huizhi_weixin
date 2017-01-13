<?php
//发送手机验证码
//checkmobile.php?tel='+tel+'&vrcode='+vrcode+'&fresh='+Math.random(),

include("./pm/conn.php");

$tel = $_GET['tel']; 
$vrcode = $_GET['vrcode']; 

$userid = $_COOKIE["userid"];
$mobile = $_COOKIE["mobile"];
$verify = $_COOKIE["verify"];


db_open();

//判断有效性
$content['code'] = 100;


if (isempty($userid))
{
    $content['code'] = 200;
    $content['msg'] = "登录异常，请重新进入汇至美家。";

    echo json_encode($content, true);
    exit_php();
}
else if (strlen($tel) != 11)
{
    $content['code'] = 200;
    $content['msg'] = "电话号码不合法。";

    echo json_encode($content, true);
    exit_php();
}
else if ($tel != $mobile)
{
    $content['code'] = 200;
    $content['msg'] = "电话号码无效。";

    echo json_encode($content, true);
    exit_php();
}
else if ($vrcode != $verify)
{
    $content['code'] = 200;
    $content['msg'] = "验证码无效。";

    echo json_encode($content, true);
    exit_php();
}

//插入数据库
$sql="update tbl_customer set mobile=:mobile where id=:id";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":mobile",$mobile);
$sth->bindValue(":id",$userid);
$sth->execute(); 

$content['msg'] = "";

echo json_encode($content, true);
exit_php();
?>