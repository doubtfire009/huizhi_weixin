<?php
//清除订单资源
/*
//请求：
	{"date":"2017-01-12","morning":160,"afternoon":0,"night":0,"anytime":0,"zone":1,"lineid":1}
*/
include("./conn.php");

$datas = $_POST['datas']; 

//wluotest
//$datas = '{"date":"2017-01-12","morning":160,"afternoon":0,"night":0,"zone":1,"lineid":1}';

db_open();

$de_json = (array)json_decode($datas);

$date = $de_json["date"];
$datetime = strtotime($date);
$morning = $de_json["morning"];
$afternoon = $de_json["afternoon"];
$night = $de_json["night"];
$anytime = $de_json["anytime"];
$zone = $de_json["zone"];
$lineid = $de_json["lineid"];


{


	$sql="select id, morning_used, afternoon_used, night_used, anytime_used from tbl_calendar_jiedan where work_date=:work_date and line_id=:line_id and srvzone_id=:zone_id";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":work_date",$datetime);
    $sth->bindValue(":line_id",$lineid);
    $sth->bindValue(":zone_id",$zone);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);

    $id = $row[0];
    $morning_used = $row[1];
    $afternoon_used = $row[2];
    $night_used = $row[3];
    $anytime_used = $row[4];

    $morning_used -= $morning;
    if ($morning_used < 0)
    {
    	$morning_used = 0;
    }

    $afternoon_used -= $afternoon;
    if ($afternoon_used < 0)
    {
    	$afternoon_used = 0;
    }

    $night_used -= $night;
    if ($night_used < 0)
    {
    	$night_used = 0;
    }
    
    $anytime_used -= $anytime;
    if ($anytime_used < 0)
    {
    	$anytime_used = 0;
    }

    $wholeday_used = $morning_used+$afternoon_used+$night_used+$anytime_used;

	try
    {
        $config['db_conn']->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);	//设置异常模式
        $sql="update tbl_calendar_jiedan set morning_used=:morning_used, afternoon_used=:afternoon_used, night_used=:night_used, anytime_used=:anytime_used,wholeday_used=:wholeday_used where id=:id";
        $sth = $config['db_conn']->prepare($sql);
        $sth->bindValue(":morning_used",$morning_used);
        $sth->bindValue(":afternoon_used",$afternoon_used);
        $sth->bindValue(":night_used",$night_used);
        $sth->bindValue(":anytime_used",$anytime_used);
        $sth->bindValue(":wholeday_used",$wholeday_used);
        $sth->bindValue(":id",$id);
        $sth->execute(); 
    }
    catch(PDOException $e)
    {
        tracelog($e->getLine());
        tracelog($e->getMessage());
    }
}

//判断有效性
$content['code'] = 100;


echo json_encode($content, true);
exit_php();

?>