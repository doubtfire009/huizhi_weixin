<?php
//查询一个订单信息 ordersure.html发起
// jiedaninfo: {"4":45,"3":0,"2":150,"1":0,"0":"2017-01-06"}

include("./pm/conn.php");

$orderid = $_GET['orderid']; 
$choicedate = $_GET['choicedate']; 
$choicetimeinfo = $_GET['choicetimeinfo']; 

//wluotest
//$orderid = 1;
//$choicedate='2017-01-12';
//$choicetimeinfo=1;

$userid = $_COOKIE["userid"];

if ($orderid <= 0 || isempty($choicedate) || $choicetimeinfo < 0)
{
    $content['code'] = 200;
    echo json_encode($content, true);
    exit_php();
}

db_open();

//查找订单的信息 servicezone_id, line_id, needtime,

$sql="select service_zone,line_id,minutes_need from tbl_order where id=:order_id";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":order_id",$orderid); 
$sth->execute();
$row = $sth->fetch(PDO::FETCH_NUM);
$service_zone = $row[0];
$line_id = $row[1];
$minutesneed = $row[2];


//调用setresource接口
//请求：{"date":"2017-01-10","zone":2,"lineid":1,"needminutes":30,"timeinfo":1}
$eachtime['date'] = $choicedate;
$eachtime['zone'] = $service_zone;
$eachtime['lineid'] = 1;
$eachtime['needminutes'] = $minutesneed;
$eachtime['timeinfo'] = $choicetimeinfo;

$json = json_encode($eachtime,true);

$apiurl = $config['domain']."wxphp/pm/setresource.php";
$timelist = wxapipost($apiurl, "datas=".$json);

//更新数据库
$de_json = (array)json_decode($timelist, true);
$morning = $de_json['1'];
$afternoon = $de_json['2'];
$night = $de_json['3'];
$anytime = $de_json['4'];
if ($morning > 0 || $afternoon > 0 || $night > 0 || $anytime > 0)
{
    $content['code'] = 100;

    $order_flag = 0;
    if ($anytime > 0)
    {
        $order_flag = 1;
    }

    try
    {
        //order_status = 1； 已经分配好资源
        
        $config['db_conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//设置异常模式
        $sql="update tbl_order set schedule_date=:schedule_date,schedule_timeinfo=:schedule_timeinfo,order_status=1,order_flag=:order_flag,jiedan_info=:jiedan_info where id=:orderid";
        $sth = $config['db_conn']->prepare($sql);
        $sth->bindValue(":schedule_date",$choicedate);
        $sth->bindValue(":schedule_timeinfo",$choicetimeinfo);
        $sth->bindValue(":order_flag",$order_flag);
        $sth->bindValue(":jiedan_info",$timelist);
        $sth->bindValue(":orderid",$orderid);
        $sth->execute(); 
    }
    catch(PDOException $e)
    {
        tracelog($e->getLine());
        tracelog($e->getMessage());
    }
}
else//没有一个合法
{
    $content['code'] = 200;
}

//是否显示任意时间段按钮
$content['timelist'] = $timelist;

echo json_encode($content, true);

exit_php();
?>