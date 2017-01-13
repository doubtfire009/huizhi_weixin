<?php
//添加一个新的订单 从product.html和shopcart.html中发起
//订单编码规则：城市号（3位）类别ID(3位)时间（12位）用户号（6位）

include("./pm/conn.php");

$data = $_POST['data']; 

$userid = $_COOKIE["userid"];

$obj=json_decode($data); 

db_open();

//保护
if (isempty($data) || isempty($userid))
{
    $content['code'] = 300;
    echo json_encode($content, true);
    exit_php();
}

//获得当前用户的默认地址
$sql="select id,city,zone,address,room,lat,lng,geohash,service_zone,name,phone from tbl_customer_addr where cust_id=:cust_id and main_addr=1";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":cust_id", $userid);
$sth->execute();
$row = $sth->fetch(PDO::FETCH_NUM);

$addr_id = $row[0];
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

if ($addr_id < 1) 
{
    $city = "";
    $zone = "";
    $address = "";
    $room = "";
    $order_lat = "";
    $order_lng = "";
    $order_geohash = "";
    $service_zone = "";
    $name = "";
    $phone = "";
    $addr_id = "";
}

//生成订单号 order_no
{
    $city_no = "021";

    //cat_no
    $pid = $obj[0][0];
    $sql="select cat_id from tbl_product where id=:id";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":id", $pid);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);
    $cat_id = $row[0];
    $cat_no = zeroleftstr($cat_id, 3);

    $time_no = date("ymdHis"); //12位
    $user_no = zeroleftstr($userid, 6);
    $order_no = $city_no.$cat_no.$time_no.$user_no;
}

$order_status = 0; //订单状态，未付款
$line_id = 1;   //产品线，默认为1
$order_flag = 0;    //订单默认不需要注意

//插入tbl_order表，获得order_id
$curtime = time();
$sql="insert into tbl_order(order_no, order_status, customer_id, date_created,order_city,order_zone,service_zone,line_id,order_addr,order_room,order_lat,order_lng,order_geohash,contact_name,contact_phone,source,order_flag,addr_index) values(:order_no, :order_status, :customer_id, :date_created,:order_city,:order_zone,:service_zone,:line_id,:order_addr,:order_room,:order_lat,:order_lng,:order_geohash,:contact_name,:contact_phone,:source,:order_flag,:addr_index)";
$sth = $config['db_conn']->prepare($sql);

try
{
    $config['db_conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//设置异常模式
    $sth->bindValue(":order_no",$order_no);
    $sth->bindValue(":order_status",$order_status);
    $sth->bindValue(":customer_id",$userid);
    $sth->bindValue(":date_created",$curtime);
    $sth->bindValue(":order_city",$city);
    $sth->bindValue(":order_zone",$zone);
    $sth->bindValue(":service_zone",$service_zone);
    $sth->bindValue(":line_id",$line_id);
    $sth->bindValue(":order_addr",$address);
    $sth->bindValue(":order_room",$room);
    $sth->bindValue(":order_lat",$order_lat);
    $sth->bindValue(":order_lng",$order_lng);
    $sth->bindValue(":order_geohash",$order_geohash);
    $sth->bindValue(":contact_name",$name);
    $sth->bindValue(":contact_phone",$phone);
    $sth->bindValue(":source","weixin");
    $sth->bindValue(":order_flag",$order_flag);
    $sth->bindValue(":addr_index",$addr_id);
    $sth->execute();
}
catch(PDOException $e)
{
    tracelog($e->getLine());
    tracelog($e->getMessage());
}
$orderid = $config['db_conn']->lastInsertId();

//插入tbl_order_item表
$total_price = 0;
$total_times = 0;
for ($i=0; $i<count($obj); $i++)
{
    $pid = $obj[$i][0];
    $skuid = $obj[$i][1];
    $nums = $obj[$i][2];
    $brandid = $obj[$i][3];
    if ($brandid == null)
    {
        $brandid = "";
    }

    //先计算该sku的总价格和时间
    $sql="select min_nums,base_mins,step_mins,base_price,step_price from tbl_sku_list where prod_id=:productid and id=:skuid";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":productid",$pid);
    $sth->bindValue(":skuid",$skuid);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);
    $min_nums = $row[0];
    $base_mins = $row[1];
    $step_mins = $row[2];
    $base_price = $row[3];
    $step_price = $row[4];

    {
        //price
        $sku_price = $base_price;
        $sku_price += $step_price*$nums;

        //time
        $sku_time = $base_mins;
        $sku_time += $nums * $step_mins;
    }

    $total_price += $sku_price;
    $total_times += $sku_time;

    //插入 tbl_order_item
    try
    {
        $sql="insert into tbl_order_item(order_id, product_id, product_num, product_price,brand_id,sku_id,total_price,total_minutes) values(:order_id, :product_id, :product_num, :product_price,:brand_id,:sku_id,:total_price,:total_minutes)";
        $sth = $config['db_conn']->prepare($sql);
        $sth->bindValue(":order_id",$orderid);
        $sth->bindValue(":product_id",$pid);
        $sth->bindValue(":product_num",$nums);
        $sth->bindValue(":product_price",$step_price);
        $sth->bindValue(":brand_id",$brandid);
        $sth->bindValue(":sku_id",$skuid);
        $sth->bindValue(":total_price",$sku_price);
        $sth->bindValue(":total_minutes",$sku_time);
        $sth->execute(); 
    }
    catch(PDOException $e)
    {
        tracelog($e->getLine());
        tracelog($e->getMessage());
    }
} //end for

//更新订单表的总价格和时间
try
{
    $config['db_conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);//设置异常模式
    $sql="update tbl_order set order_amt=:order_amt,payment_need=:payneed,minutes_need=:minutes_need where id=:id";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":order_amt",$total_price);
    $sth->bindValue(":payneed",$total_price);
    $sth->bindValue(":minutes_need",$total_times);
    $sth->bindValue(":id",$orderid);
    $sth->execute(); 
}
catch(PDOException $e)
{
    tracelog($e->getLine());
    tracelog($e->getMessage());
}

//判断默认地址如果为空则返回代码100，否则返回200
if (isempty($addr_id))
{
    $content['code'] = 100;
    $content['order_id'] = $orderid;
    $content['action'] = "choice";
}
else
{
    $content['code'] = 200;
    $content['order_id'] = $orderid;
}

echo json_encode($content, true);

exit_php();
?>