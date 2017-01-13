<?php
//从商品详情页添加到购物车
//url:'./wxphp/addshopcart.php?curbrand='+curbrand+"&cursku="+cursku+"&curskuminnums="+curskuminnums+"&productid="+pid,

include("./pm/conn.php");

$curbrand = $_GET['curbrand'];
$cursku = $_GET['cursku'];
$curskuminnums = $_GET['curskuminnums'];
$productid = $_GET['productid'];

$userid = $_COOKIE["userid"];

db_open();
$content['code'] = 100;

if (ischeckmobile($userid))
{
	$content['code'] = 200;
	echo json_encode($content, true);
	exit_php();
}

//保护
if ($cursku <= 0 || $curskuminnums <= 0 || $productid <= 0)
{
	echo json_encode($content, true);
	exit_php();
}

//先查找，判断是更新还是插入
$sql="select id from tbl_shopcart where customer_id=:customer_id and prod_id=:prod_id and sku_id=:sku_id";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":customer_id",$userid);
$sth->bindValue(":prod_id",$productid);
$sth->bindValue(":sku_id",$cursku);
$sth->execute();
$row = $sth->fetch(PDO::FETCH_NUM);
$id = $row[0];

if ($id <= 0)
{
	$curtime = time();

	$sql="insert into tbl_shopcart(customer_id, prod_id, brand_id, sku_id, nums, date_created) values(:customer_id, :prod_id, :brand_id, :sku_id, :nums, :date_created)";
	$sth = $config['db_conn']->prepare($sql);
	$sth->bindValue(":customer_id",$userid);
	$sth->bindValue(":prod_id",$productid);
	$sth->bindValue(":brand_id",$curbrand);
	$sth->bindValue(":sku_id",$cursku);
	$sth->bindValue(":nums",$curskuminnums);
	$sth->bindValue(":date_created",$curtime);
	$sth->execute();
}  
else //更新
{
    $sql="update tbl_shopcart set nums=:nums where id=:id";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":nums",$curskuminnums);
    $sth->bindValue(":id",$id);
    $sth->execute(); 
}


echo json_encode($content, true);

exit_php();
?>