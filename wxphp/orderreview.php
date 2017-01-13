<?php
//订单评论

include("./pm/conn.php");

$orderid = $_GET['orderid']; 
$starnums = $_GET['starnums']; 
$review = $_GET['review']; 
$comment = $_GET['comment']; 

$userid = $_COOKIE["userid"];

db_open();

//取订单信息
if ($orderid > 0)
{
    //不允许重复
    $sql="select id from tbl_review where order_id=:orderid";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":orderid", $orderid);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);
    $id = $row[0];

    if ($id <= 0)
    {
        $curtime = time();

        $sql="insert into tbl_review(order_id, score, tags, content, date_created) values(:order_id,:score,:tags,:content,:date_created)";
        $sth = $config['db_conn']->prepare($sql);
        $sth->bindValue(":order_id", $orderid);
        $sth->bindValue(":score", $starnums);
        $sth->bindValue(":tags", $review);
        $sth->bindValue(":content", $comment);
        $sth->bindValue(":date_created", $curtime);
        $sth->execute();

        //修改订单状态
        changeorderstatus($orderid, 101);
    }
}

$content = 1;
echo json_encode($content, true);

exit_php();
?>