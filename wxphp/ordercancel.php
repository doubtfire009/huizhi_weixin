<?php
//取消订单 order_flag=90

include("./pm/conn.php");

$orderid = $_GET['orderid']; 

//wluotest
//$orderid = 1;

$userid = $_COOKIE["userid"];

db_open();

//取 jiedan_info
{
    $sql="select service_zone,line_id,jiedan_info from tbl_order where id=:orderid";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":orderid", $orderid);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);
    $service_zone = $row[0];
    $line_id = $row[1];
    $jiedan_info = $row[2];
    
    $jiedan_info = str_replace("\"0\"", "\"date\"",$jiedan_info);
    $jiedan_info = str_replace("\"1\"", "\"morning\"",$jiedan_info);
    $jiedan_info = str_replace("\"2\"", "\"afternoon\"",$jiedan_info);
    $jiedan_info = str_replace("\"3\"", "\"night\"",$jiedan_info);
    $jiedan_info = str_replace("\"4\"", "\"anytime\"",$jiedan_info);

    $info = str_replace("}", ",\"zone\":".$service_zone.",\"lineid\":".$line_id."}",$jiedan_info);
}

//{"0":"2017-01-12","1":160,"2":0,"3":0,"4":0}

$apiurl = $config['domain']."wxphp/pm/delresource.php";
$timelist = wxapipost($apiurl, "datas=".$info);
$de_json = (array)json_decode($timelist, true);
$code = $de_json['code'];
if ($code == 100)//成功
{
    try
    {
        //order_status = 90； 取消订单
        
        $config['db_conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//设置异常模式
        $sql="update tbl_order set order_status=90, jiedan_info='' where id=:orderid";
        $sth = $config['db_conn']->prepare($sql);
        $sth->bindValue(":orderid",$orderid);
        $sth->execute(); 
    }
    catch(PDOException $e)
    {
        tracelog($e->getLine());
        tracelog($e->getMessage());
    }
}

$content['code'] = $code;

echo json_encode($content, true);

exit_php();
?>