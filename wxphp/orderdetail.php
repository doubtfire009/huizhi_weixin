<?php
//orderdetail.html 订单详情

include("./pm/conn.php");

$orderid = $_GET['orderid']; 

$userid = $_COOKIE["userid"];

db_open();

//取订单信息
{
    $sql="select order_no, order_status, date_created, contact_name, contact_phone, order_zone, order_addr, order_room, schedule_date, schedule_timeinfo, payment_need, payment_paid from tbl_order where id=:orderid";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":orderid", $orderid);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);
    $order_no = $row[0];
    $order_status = $row[1];
    $date_created = $row[2];
    $contact_name = $row[3];
    $contact_phone = $row[4];
    $order_zone = $row[5];
    $order_addr = $row[6];
    $order_room = $row[7];
    $schedule_date = $row[8];
    $schedule_timeinfo = gettimeinfo($row[9]);
    $payment_need = $row[10];
    $payment_paid = $row[11];

    $zonename = getzonename($order_zone);
    $addr = $zonename.$order_addr.$order_room;

    $content['order_no'] = $order_no;
    $content['order_status'] = $order_status;
    $content['status'] = getstatusname($order_status);
    $content['date_created'] = date('Y-m-d H:i:s', $date_created);
    $content['contact_name'] = $contact_name;
    $content['contact_phone'] = $contact_phone;
    $content['addr'] = $addr;
    $content['timeinfo'] = $schedule_date.' '.$schedule_timeinfo;
    $content['payment_need'] = $payment_need;
    $content['payment_paid'] = $payment_paid;
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


echo json_encode($content, true);

exit_php();
?>