<?php
//orderpay.html 付款前

include("./pm/conn.php");

$orderid = $_GET['orderid']; 

$userid = $_COOKIE["userid"];

db_open();

//取总价
{
    $sql="select payment_need from tbl_order where id=:orderid";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":orderid", $orderid);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);

    $payment_need = $row[0];
    $content['payment_need'] = $payment_need;
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

$content['maxlefttime'] = $config_maxlefttime;


echo json_encode($content, true);

exit_php();
?>