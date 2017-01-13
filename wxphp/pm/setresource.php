<?php
//设置订单资源
/*
//请求：
	{"date":"2017-01-10","zone":2,"lineid":1,"needminutes":30,"timeinfo":1}
//返回json：
	{'0':'2017-01-12','1':0,'2':150,'3':0,'4':45}
	'1':上午占用的时间
	'2':下午用的时间
	'3':晚上占用的时间
	'4':任意时段占用的时间
*/
include("./conn.php");

$datas = $_POST['datas']; 

//wluotest
//$datas = '{"date":"2017-01-12","zone":"1","lineid":1,"needminutes":"100","timeinfo":1}';

db_open();

$de_json = (array)json_decode($datas);

{
	$date = $de_json["date"];
	$datetime = strtotime($date);
	$zone = $de_json["zone"];
	$lineid = $de_json["lineid"];
	$timeinfo = $de_json["timeinfo"];
	$needminutes = $de_json["needminutes"] + $config['minutes_onroad']; //加上路途时间


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

	$single_up['morning'] = $config_timelimits['morning'] + $config_timemargin['morning'];
	$single_up['afternoon'] = $config_timelimits['afternoon'] + $config_timemargin['afternoon'];
	$single_up['night'] = $config_timelimits['night'] + $config_timemargin['night'];
	$single_up['wholeday'] = $single_up['morning']+$single_up['afternoon']+$single_up['night'];


	$wholeday_down = $morning_down + $afternoon_down +$night_down;
	$wholeday_up = $morning_up +$afternoon_up +$night_up;
	$wholeday_used = $morning_used+$afternoon_used+$night_used+$anytime_used;
	if ($config_anytime == 0)
	{
		$wholeday_used = $morning_used+$afternoon_used+$night_used;
	}

	$morning = 0;
	$afternoon = 0;
	$night = 0;
	$anytime = 0;
	if ($wholeday_used < $wholeday_up) //全天不超过上限
	{
		switch ($timeinfo) 
		{
			case 0: //任意
				$arr = geteachtime($needminutes, $wholeday_used, $wholeday_up, $wholeday_up, $single_up['wholeday'] );
				$anytime = $arr[0]+$arr[1];
				break;
			
			case 1: //上午
				$arr = geteachtime($needminutes, $morning_used, $morning_down, $morning_up, $single_up['morning']);
				$morning = $arr[0];
				$anytime = $arr[1];
				break;
			
			case 2: //下午
				$arr = geteachtime($needminutes, $afternoon_used, $afternoon_down, $afternoon_up, $single_up['afternoon']);
				$afternoon = $arr[0];
				$anytime = $arr[1];
				break;
			
			case 3: //晚上
				$arr = geteachtime($needminutes, $night_used, $night_down, $night_up, $single_up['night']);
				$night = $arr[0];
				$anytime = $arr[1];
				break;
		}		
	}

	//更新数据库
	if ($morning>0 || $afternoon>0 || $night>0 || $anytime>0)
	{
		$morning_used += $morning;
		$afternoon_used += $afternoon;
		$night_used += $night;
		$anytime_used += $anytime;
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

	$each = Array('0'=>$date, '1'=>$morning, '2'=>$afternoon, '3'=>$night, '4'=>$anytime);
    if ($config_anytime == 0)
    {
    	$each = Array('0'=>$date, '1'=>$morning, '2'=>$afternoon, '3'=>$night);
    }
    echo json_encode($each, JSON_FORCE_OBJECT);
}

exit_php();

//计算某个时间段需要的资源
function geteachtime($need, $used, $down, $up, $singup)
{
	$needuse = 0;
	$anytime_use =0;


	if ($used >= $down)
	{
		return [0,0];
	}

	if ($need < $config_maxminutes_oneperson_onetask) 
	{
		if ($need > $singup) 
		{
			$needuse = $singup;
			if ($config_anytime == 1)
			{
				$delta = $need - $singup;
				$anytime_use = $delta;
			}
		} 
		else 
		{
			$needuse = $need;	
		}
	}
	else
	{
		if ($used > $up)
		{
			$needuse = $up;
			if ($config_anytime == 1)
			{
				$delta = $need - $up;
				$anytime_use = $delta;
			}
		} 
		else 
		{
			$needuse = $need;
		}
	}

	$usedarr[0] = $needuse;
	$usedarr[1] = $anytime_use;

	return $usedarr;
}

?>