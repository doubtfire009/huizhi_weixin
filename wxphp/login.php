<?php
//微信用户引导入口

include("./pm/conn.php");

//这个链接作为公众号菜单的引导链接
//$wxapi = "http://mayhomer.baolink.net/wxphp/login.php?type=1"

$type = $_GET['type'];

if ($type == 1) //登录
{
    $loginapi = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=".$config['appid']."&redirect_uri=".$config['domain']."wxphp/login.php&response_type=code&scope=snsapi_base&state=123#wechat_redirect";


    Header("HTTP/1.1 303 See Other"); 
    Header("Location: $loginapi");
    exit;
}
else //登录链接返回 redirect
{
    $code = $_GET['code'];

    $userid = $_COOKIE["userid"];
    $openid = $_COOKIE["openid"];

    db_open();
    if (isempty($openid))
    {
        if (!isempty($userid))
        {
            $openid = getwxuseropenid($userid);
        }

        if (isempty($openid)) //通过wxapi获取
        {
            $wxapi = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=".$config['appid']."&secret=".$config['appsecret']."&code=".$code."&grant_type=authorization_code";

            $content = wxapiget($wxapi);
            $openid = $content['openid'];

            if (!isempty($openid))
            {
                $curtime = time();

                //先判断openid是否重复，如果重复则不插入数据库，直接返回userid
                $sql="select id from tbl_customer where wx_openid=:wx_openid";
                $sth = $config['db_conn']->prepare($sql);
                $sth->bindValue(":wx_openid",$openid);
                $sth->execute();
                $row = $sth->fetch(PDO::FETCH_NUM);
                $userid = $row[0];

                //插入数据库
                if ($userid <= 0)
                {  
                    $sql="insert into tbl_customer(wx_openid, date_created) values(:wx_openid, :date_created)";
                    $sth = $config['db_conn']->prepare($sql);
                    $sth->bindValue(":wx_openid",$openid);
                    $sth->bindValue(":date_created",$curtime);
                    $sth->execute();
                    $userid = $config['db_conn']->lastInsertId();
                }

                //插入cookie
                setusercookie($userid, $openid);

            }
        }
    }
    
    $url = $config['index'];
    Header("HTTP/1.1 303 See Other"); 
    Header("Location: $url"); 
    exit_php();
}


?>