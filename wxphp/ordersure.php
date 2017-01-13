<?php
//查询一个订单信息 ordersure.html发起
// jiedaninfo: {"4":45,"3":0,"2":150,"1":0,"0":"2017-01-06"}

include("./pm/conn.php");

$orderid = $_GET['orderid']; 

$userid = $_COOKIE["userid"];

db_open();

//取服务地址
{

    $sql="select order_zone, order_addr, order_room, contact_name, contact_phone,payment_need,schedule_date,schedule_timeinfo,service_zone,minutes_need from tbl_order where id=:orderid";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":orderid", $orderid);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);

    $zoneid = $row[0];
    $zonename = getzonename($zoneid);
    $address = $row[1];
    $room = $row[2];
    $name = $row[3];
    $phone = $row[4];
    $payneed = $row[5];
    $schedule_date = $row[6];
    $schedule_timeinfo = $row[7];
    $service_zone = $row[8];
    $minutes_need = $row[9];

    $content['zoneid'] = $zoneid;
    $content['zonename'] = $zonename;
    $content['address'] = $address;
    $content['room'] = $room;
    $content['name'] = $name;
    $content['phone'] = $phone;
    $content['payneed'] = $payneed;
    $content['schedule_date'] = $schedule_date;
    $content['schedule_timeinfo'] = $schedule_timeinfo;
}

//取sku
{
    $sku_arr = array();

    $sql="select sku_id,product_num,total_price from tbl_order_item where order_id=:order_id";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":order_id",$orderid);
    $sth->execute();
    while ($row = $sth->fetch(PDO::FETCH_NUM)) 
    {
        $skuid = $row[0];
        $product_num = $row[1];
        $product_price = $row[2];

        $skuname = getskuname($skuid);

        $each['sku_name'] = $skuname;
        $each['product_num'] = $product_num;
        $each['product_price'] = $product_price;
        $sku_arr[] = $each;
    }
    $content['sku_list'] = $sku_arr;
}

//取资源信息
//{"date":"2017-01-10","zone":1,"lineid":1,"needminutes":100}
if ($service_zone > 0 && $orderid > 0)
{
    $timearr = array();

    //今天之后的28天
    for ($i=0; $i<=30; $i++)
    {
        $date = date("Y-m-d",strtotime("+$i day"));

        $eachtime['date'] = $date;
        $eachtime['zone'] = $service_zone;
        $eachtime['lineid'] = 1;
        $eachtime['needminutes'] = $minutes_need;
        $timearr[] = $eachtime;
    }    
    $json = json_encode($timearr,true);

    $apiurl = $config['domain']."wxphp/pm/getresource.php";
    $timelist = wxapipost($apiurl, "datas=".$json);
    $content['timelist'] = $timelist;
}

//是否显示任意时间段按钮
$content['anytimeon'] = $config_anytime;

echo json_encode($content, true);

exit_php();
?>