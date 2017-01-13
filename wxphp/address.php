<?php
//查询用户的服务地址 address.html

include("./pm/conn.php");

$userid = $_COOKIE["userid"];

db_open();

//该类目下的产品信息
$sql="select id, city, zone, address,room, main_addr, name, phone from tbl_customer_addr where cust_id=:cust_id order by main_addr desc";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":cust_id",$userid);
$sth->execute();

$productlist = array();
while($row = $sth->fetch(PDO::FETCH_NUM))
{
    $id = $row[0];
    $city = $row[1];
    $zone = $row[2];
    $address = $row[3];
    $room = $row[4];
    $main_addr = $row[5];
    $name = $row[6];
    $phone = $row[7];

    $each['id'] = $id;
    $each['city'] = "上海市";
    $each['zonename'] = getzonename($zone);
    $each['address'] = $address.$room;
    $each['main_addr'] = $main_addr;
    $each['name'] = $name;
    $each['phone'] = $phone;

    $productlist[] = $each;
}
$content['address_list'] = $productlist;

echo json_encode($content, true);

exit_php();
?>