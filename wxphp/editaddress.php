<?php
//编辑某个服务地址 editaddress.html
//editaddress.php?fresh='+Math.random()+"&name="+name+"&phone="+phone+"&address="+address+"&room="+room+"&choicezone="+choicezone+addridstr,


include("./pm/conn.php");

$addrid = $_GET["addrid"];
$name = $_GET["name"];
$phone = $_GET["phone"];
$address = $_GET["address"];
$room = $_GET["room"];
$zone = $_GET["choicezone"];

$userid = $_COOKIE["userid"];
db_open();

//该类目下的产品信息
if ($addrid < 1) //添加
{
	//判断是否第一个，如果是第一个则是默认地址；
	$sql="select id from tbl_customer_addr where cust_id=:cust_id";
	$sth = $config['db_conn']->prepare($sql);
	$sth->bindValue(":cust_id",$userid);
	$sth->execute();
	$row = $sth->fetch(PDO::FETCH_NUM);
	$id = $row[0];
	if ($id > 0)
	{
		$main_addr = 0;
	}
	else //没有其他地址，此时是第一个地址
	{
		$main_addr = 1;
	}

	//服务区
	$service_zone = getsrvzone($zone);
	if (isempty($service_zone))
	{
		$content['code'] = 200;
		echo json_encode($content, true);
		exit_php();
	}

	//获取经纬度信息
	$zonename = getzonename($zone);
	$detailaddr = "上海市".$zonename.$address.$room;
	$coords = getLatLong($detailaddr);
	$lng = $coords[0];
	$lat = $coords[1];
	$geohash = encode_geohash($lat, $lng, 12);

	$sql="insert into tbl_customer_addr(cust_id, city, zone, address, room, service_zone, main_addr, name, phone, lat, lng, geohash) values(:cust_id, :city, :zone, :address, :room, :service_zone, :main_addr, :name, :phone, :lat, :lng, :geohash)";
	$sth = $config['db_conn']->prepare($sql);

	try
	{
	    $config['db_conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//设置异常模式
	    $sth->bindValue(":cust_id",$userid);
	    $sth->bindValue(":city",21);
	    $sth->bindValue(":zone",$zone);
	    $sth->bindValue(":address",$address);
	    $sth->bindValue(":room",$room);
	    $sth->bindValue(":service_zone",$service_zone);
	    $sth->bindValue(":main_addr",$main_addr);
	    $sth->bindValue(":name",$name);
	    $sth->bindValue(":phone",$phone);
	    $sth->bindValue(":lat",$lat);
	    $sth->bindValue(":lng",$lng);
	    $sth->bindValue(":geohash",$geohash);
	    $sth->execute();
	}
	catch(PDOException $e)
	{
	    tracelog($e->getLine());
	    tracelog($e->getMessage());
	}
}
else //更新
{
	//服务区
	$service_zone = getsrvzone($zone);
	if (isempty($service_zone))
	{
		$content['code'] = 200;
		echo json_encode($content, true);
		exit_php();
	}
	
	//获取经纬度信息
	$zonename = getzonename($zone);
	$detailaddr = "上海市".$zonename.$address.$room;
	$coords = getLatLong($detailaddr);
	$lng = $coords[0];
	$lat = $coords[1];
	$geohash = encode_geohash($lat, $lng, 12);

	$sql="update tbl_customer_addr set cust_id=:cust_id, city=:city, zone=:zone, address=:address, room=:room, service_zone=:service_zone,  name=:name, phone=:phone, lat=:lat, lng=:lng, geohash=:geohash where id=:id";
	$sth = $config['db_conn']->prepare($sql);

	try
	{
	    $config['db_conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//设置异常模式
	    $sth->bindValue(":cust_id",$userid);
	    $sth->bindValue(":city",21);
	    $sth->bindValue(":zone",$zone);
	    $sth->bindValue(":address",$address);
	    $sth->bindValue(":room",$room);
	    $sth->bindValue(":service_zone",$service_zone);
	    $sth->bindValue(":name",$name);
	    $sth->bindValue(":phone",$phone);
	    $sth->bindValue(":lat",$lat);
	    $sth->bindValue(":lng",$lng);
	    $sth->bindValue(":geohash",$geohash);
	    $sth->bindValue(":id",$addrid);
	    $sth->execute();
	}
	catch(PDOException $e)
	{
	    tracelog($e->getLine());
	    tracelog($e->getMessage());
	}
}

$content['code'] = 100;
echo json_encode($content, true);

exit_php();
?>