<?php
//获取服务项目的内容
//主题1|主题2 取主题1作为title显示

include("./pm/conn.php");


$cateid = $_GET['cateid'];

db_open();

//类目名称
$sql="select name from tbl_category where id=:cateid";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":cateid",$cateid);
$sth->execute();
$row = $sth->fetch(PDO::FETCH_NUM);
$cat_name = $row[0];

$content['name'] = $cat_name;


//产品信息
$sql="select id,title,logo from tbl_product where cat_id=:cateid and status=1 order by sort desc";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":cateid",$cateid);
$sth->execute();

$product_arr = array();
while($row = $sth->fetch(PDO::FETCH_NUM))
{
    $id = $row[0];
    $title = $row[1];
    $logo = $row[2];

    $each['id'] = $id;
    $each['title'] = getsubstr($title,0);
    $each['logo'] = $logo;
    $product_arr[] = $each;
}

$content['product'] = $product_arr;

echo json_encode($content, true);

exit_php();
?>