<?php
//退出购物车界面时更新购物车
//[["pid","2","skuid","2","skucartnums","5"]]

include("./pm/conn.php");

$data = $_POST['data']; 

$userid = $_COOKIE["userid"];

$obj=json_decode($data); 

db_open();

for ($i=0; $i<count($obj); $i++)
{
    $pid = $obj[$i][0];
    $skuid = $obj[$i][1];
    $nums = $obj[$i][2];

    //保护
    if ($pid <= 0 || $skuid <= 0)
    {
        continue;
    }

    $curtime = time();

    //先查找，判断是更新还是插入
    $sql="select id from tbl_shopcart where customer_id=:customer_id and prod_id=:prod_id and sku_id=:sku_id";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":customer_id",$userid);
    $sth->bindValue(":prod_id",$pid);
    $sth->bindValue(":sku_id",$skuid);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);
    $id = $row[0];

    //新插入
    if ($id <= 0)
    {
        $sql="insert into tbl_shopcart(customer_id, prod_id, sku_id, nums, date_created) values(:customer_id, :prod_id, :sku_id, :nums, :date_created)";
        $sth = $config['db_conn']->prepare($sql);
        $sth->bindValue(":customer_id",$userid);
        $sth->bindValue(":prod_id",$pid);
        $sth->bindValue(":sku_id",$skuid);
        $sth->bindValue(":nums",$nums);
        $sth->bindValue(":date_created",$curtime);
        $sth->execute(); 
    }  
    else //更新
    {
        $sql="update tbl_shopcart set nums=:nums where id=:id";
        $sth = $config['db_conn']->prepare($sql);
        $sth->bindValue(":nums",$nums);
        $sth->bindValue(":id",$id);
        $sth->execute(); 
    }
}



$content = 1;
echo json_encode($content, true);



exit_php();
?>