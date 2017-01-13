<?php
//获得订单资源
//获取一段日期的资源情况
/*
//请求：
	[{'date':2017-01-10','zone':2,'lineid':1},{'date':2017-01-11','zone':2,'lineid':1}]
//返回：
	morning:0，表示上午时间约满。
	morning:100, 表示上午时间剩下的时间还有100分钟。
	[{"date":"2017-01-05","morning":0,"afternoon":0,"night":0,"anytime":0},{"date":"2017-01-06","morning":100,"afternoon":0,"night":0,"anytime":0}]
*/
include("./conn.php");

$datas = $_POST['datas']; 

//wluotest
//$datas = '[{"date":"2017-01-10","zone":1,"lineid":1,"needminutes":100},{"date":"2017-01-11","zone":2,"lineid":1,"needminutes":100}]';

db_open();

$de_json = json_decode($datas,TRUE);
$count_json = count($de_json);

$reslist = array();
for ($i = 0; $i < $count_json; $i++)
{
	$date = $de_json[$i]['date'];
	$datetime = strtotime($date);
	$zone = $de_json[$i]['zone'];
	$lineid = $de_json[$i]['lineid'];
	$needminutes = $de_json[$i]['needminutes'];

	$sql="select id, morning_up, morning_down, morning_used, afternoon_up, afternoon_down, afternoon_used, night_up, night_down, night_used, anytime_used from tbl_calendar_jiedan where work_date=:work_date and line_id=:line_id and srvzone_id=:zone_id";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":work_date",$datetime);
    $sth->bindValue(":line_id",$lineid);
    $sth->bindValue(":zone_id",$zone);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);

    $id = $row[0];
    $morning_up = $row[1];
    $morning_down = $row[2];
    $morning_used = $row[3];
    $afternoon_up = $row[4];
    $afternoon_down = $row[5];
    $afternoon_used = $row[6];
    $night_up = $row[7];
    $night_down = $row[8];
    $night_used = $row[9];
    $anytime_used = $row[10];

	$wholeday_down = $morning_down + $afternoon_down +$night_down;
	$wholeday_up = $morning_up +$afternoon_up +$night_up;
	$wholeday_used = $morning_used+$afternoon_used+$night_used+$anytime_used;
	if ($config_anytime == 0)
	{
		$wholeday_used = $morning_used+$afternoon_used+$night_used;
	}

	if ($wholeday_used >= $wholeday_up) //全天超过上限
	{
		$morning = 0;
		$afternoon = 0;
		$night = 0;
		$anytime = 0;
	}
	else
	{
		$morning = geteachtime($morning_used, $morning_down, $morning_up);

		$afternoon = geteachtime($afternoon_used, $afternoon_down, $afternoon_up);

		$night = geteachtime($night_used, $night_down, $night_up);

		$anytime = geteachtime($wholeday_used, $wholeday_up, $wholeday_up);
	}

	$each['date'] = $date;
    $each['morning'] = $morning;
    $each['afternoon'] = $afternoon;
    $each['night'] = $night;
    if ($config_anytime == 1)
    {
    	$each['anytime'] = $anytime;
    }

    $reslist[] = $each;
}

echo json_encode($reslist);
exit_php();

//计算某个时间段的资源
function geteachtime($used, $down, $up)
{
	if ($used >= $down)
	{
		return 0;
	}
	return $up-$used;
}

?>