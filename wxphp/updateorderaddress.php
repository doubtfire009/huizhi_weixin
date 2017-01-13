<?php
//更新订单服务地址

include("./pm/conn.php");

$orderid = $_GET['orderid']; 
$addrid = $_GET['addrid']; 
$userid = $_COOKIE["userid"];

db_open();

$sql="select id,city,zone,address,room,lat,lng,geohash,service_zone,name,phone from tbl_customer_addr where id=:id";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":id", $addrid);
$sth->execute();
$row = $sth->fetch(PDO::FETCH_NUM);

$addrid = $row[0];
$city = $row[1];
$zone = $row[2];
$address = $row[3];
$room = $row[4];
$order_lat = $row[5];
$order_lng = $row[6];
$order_geohash = $row[7];
$service_zone = $row[8];
$name = $row[9];
$phone = $row[10];

try
{
    $config['db_conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//设置异常模式
    $sql="update tbl_order set order_city=:order_city,order_zone=:order_zone,service_zone=:service_zone,order_addr=:order_addr,order_lat=:order_lat,order_lng=:order_lng,order_geohash=:order_geohash,contact_name=:contact_name,contact_phone=:contact_phone,addr_index=:addr_index where id=:orderid";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":order_city",$city);
    $sth->bindValue(":order_zone",$zone);
    $sth->bindValue(":service_zone",$service_zone);
    $sth->bindValue(":order_addr",$address.$room);
    $sth->bindValue(":order_lat",$order_lat);
    $sth->bindValue(":order_lng",$order_lng);
    $sth->bindValue(":order_geohash",$order_geohash);
    $sth->bindValue(":contact_name",$name);
    $sth->bindValue(":contact_phone",$phone);
    $sth->bindValue(":addr_index",$addrid);
    $sth->bindValue(":orderid",$orderid);
    $sth->execute(); 
}
catch(PDOException $e)
{
    tracelog($e->getLine());
    tracelog($e->getMessage());
}

$content = 1;
echo json_encode($content, true);

exit_php();
?>