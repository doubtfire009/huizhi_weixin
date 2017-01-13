<?php
//查询用户的某个具体服务地址 editaddress.html

include("./pm/conn.php");

$addrid = $_GET["addrid"];
$userid = $_COOKIE["userid"];
db_open();

//该类目下的产品信息
if ($addrid > 0)
{
	$sql="select zone, address, room, name, phone from tbl_customer_addr where id=:id";
	$sth = $config['db_conn']->prepare($sql);
	$sth->bindValue(":id",$addrid);
	$sth->execute();
	$row = $sth->fetch(PDO::FETCH_NUM);

	$zone = $row[0];
	$address = $row[1];
	$room = $row[2];
	$name = $row[3];
	$phone = $row[4];

	$content['zone'] = $zone;
	$content['address'] = $address;
	$content['room'] = $room;
	$content['name'] = $name;
	$content['phone'] = $phone;
}
	//wluotest 
/*
	$content['zone'] = 2108;
	$content['address'] = "浦东大道100号";
	$content['room'] = "69弄201室";
	$content['name'] = "cindy";
	$content['phone'] = "18603199231";
*/
	
$content['zonelist'] = $config_zonelist;

echo json_encode($content, true);

exit_php();
?>