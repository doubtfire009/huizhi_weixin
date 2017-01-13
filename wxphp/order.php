<?php
//订单界面 order.html
//type: 1-未支付；2-未完成；3-已完成；4-全部

include("./pm/conn.php");


$type = $_GET["type"];
$userid = $_COOKIE["userid"];

db_open();

//判断有效性
$content['code'] = 100;

if (ischeckmobile($userid))
{
	$content['code'] = 200;
	echo json_encode($content, true);
	exit_php();
}

if ($type == 1) //未支付
{
	$order_status = "and (order_status=0 or order_status=1 or order_status=11)";
}

else if ($type == 2) //未完成
{
	$order_status = "and (order_status=10 or order_status=20 or order_status=30 or order_status=31 or order_status=90)";
}

else if ($type == 3) //已完成
{
	$order_status = "and (order_status=100 or order_status=101 or order_status=110)";
}

else if ($type == 4) //全部
{
	$order_status = "";
}

//产品分类名称，下单时间，订单状态，SKU(skuname和数量)，总价
$sql="select id, order_status, date_created, payment_need, payment_paid from tbl_order where customer_id=:customer_id $order_status order by date_created desc";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":customer_id", $userid);
$sth->execute();

$orderlist = array();
while($row = $sth->fetch(PDO::FETCH_NUM))
{
	$orderid = $row[0];
	$order_status = $row[1];
	$date_created = date('Y-m-d H:i', $row[2]);
	$payment_need = $row[3];
	$payment_paid = $row[4];

	//根据订单查 cat_id
	$catid = getcatidbyorder($orderid);
	$catname = getcatnamebycatid($catid);
	$status = getstatusname($order_status);

	$each['orderid'] = $orderid;
	$each['order_status'] = $status;
	$each['date_created'] = $date_created;
	$each['payment_paid'] = $payment_paid;
	$each['payment_need'] = $payment_need;
	$each['catname'] = $catname;

	//取sku
	{
	    $sku_arr = array();

	    $sql2="select sku_id,product_num from tbl_order_item where order_id=:order_id";
	    $sth2 = $config['db_conn']->prepare($sql2);
	    $sth2->bindValue(":order_id",$orderid);
	    $sth2->execute();
	    while ($row2 = $sth2->fetch(PDO::FETCH_NUM)) 
	    {
	        $skuid = $row2[0];
	        $product_num = $row2[1];

	        $skuname = getskuname($skuid);

	        $skueach['sku_name'] = $skuname;
	        $skueach['product_num'] = $product_num;
	        $sku_arr[] = $skueach;
	    }
	    $each['sku_list'] = $sku_arr;
	}

	$orderlist[] = $each;
}
$content['orderlist'] = $orderlist;

echo json_encode($content, true);
exit_php();

?>