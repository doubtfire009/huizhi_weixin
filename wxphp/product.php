<?php
//获取产品的内容
//主题1|主题2 取主题2作为title显示

include("./pm/conn.php");

$productid = $_GET['productid'];
$cityid = $_GET['cityid'];
$userid = $_COOKIE["userid"];

/*
$fhandler = fopen("../tmp/tmp.txt", "w");

    fwrite($fhandler, $productid.'|');
    fwrite($fhandler, $cityid.'|');
    fwrite($fhandler, $userid.'\n');

fclose($fhandler);
*/

db_open();

//产品信息
$sql="select title, cat_id, bigimage, content, quality_desc, process_desc, price_desc,brand_list from tbl_product where id=:productid and city_id=:cityid and status=1";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":productid",$productid);
$sth->bindValue(":cityid",$cityid);
$sth->execute();
$row = $sth->fetch(PDO::FETCH_NUM);

$content['title'] = getsubstr($row[0],1);
$content['cat_id'] = $row[1];
$content['bigimage'] = $row[2];
$content['content'] = $row[3];
$content['quality_desc'] = $row[4];
$content['process_desc'] = $row[5];
$content['price_desc'] = $row[6];
$content['brand_list'] = $row[7];

//购物车数量
$sql="select sum(a.nums) from tbl_shopcart as a, tbl_product as b where a.sku_id>0 and a.customer_id =:userid and b.cat_id=:cat_id and b.status=1 and b.id=a.prod_id";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":userid",$userid);
$sth->bindValue(":cat_id",$content['cat_id']);
$sth->execute();
$row = $sth->fetch(PDO::FETCH_NUM);
$content['cart_nums'] = $row[0];

//SKU信息
//按brand_list的格式返回：1:格力|2:海尔|3:海信
$sql="select id,name,min_nums from tbl_sku_list where prod_id=:productid";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":productid",$productid);
$sth->execute();
$skulist = "";
while($row = $sth->fetch(PDO::FETCH_NUM))
{
    $id = $row[0];
    $name = $row[1];
    $min_nums = $row[2];

    $skulist .= "$id:$name:$min_nums|";
}
$skulist = rtrim($skulist,"|"); //去掉最后一个|
$content['sku_list'] = $skulist;

echo json_encode($content, true);

exit_php();
?>