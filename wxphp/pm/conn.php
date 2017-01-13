<?php
	date_default_timezone_set('PRC');

//include(realpath("./")."pm/config.php")
if (!defined('KEIYICLASS_ROOT')) { define('KEIYICLASS_ROOT', dirname(__FILE__) . '/'); } 
include_once(KEIYICLASS_ROOT."config.php");


//连接数据库
function db_open()
{
	global $config;

	try{
		$config['db_conn'] = new PDO($config['dsn'], $config['db_user'], $config['db_password']);
		$config['db_conn']->query("SET NAMES utf8");
	}
	catch (PDOException $e)
	{
		echo "connect error.<br>";
        exit;
    }
}

//断开数据库
function db_close()
{
	$config['db_conn'] = null;
}

//设置订单状态
function changeorderstatus($orderid, $status)
{
	global $config;

	$sql="update tbl_order set order_status=:status where id = :orderid";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(":orderid",$orderid);
    $sth->bindValue(":status",$status);
    $sth->execute();
}

//根据 userid 获得 openid
function getwxuseropenid($userid)
{
	global $config;

	if (!isempty($userid))
	{
	    $sql="select wx_openid from tbl_customer where id = :userid and status=1";
	    $sth = $config['db_conn']->prepare($sql);
	    $sth->bindValue(":userid",$userid);
	    $sth->execute();
	    $row = $sth->fetch(PDO::FETCH_NUM);
	    return $row[0];
	}
	else
	{
		return "";
	}
}

//获得skuname
function getskuname($skuid)
{
	global $config;

	if (!isempty($skuid))
	{
	    $sql="select name from tbl_sku_list where id = :skuid";
	    $sth = $config['db_conn']->prepare($sql);
	    $sth->bindValue(":skuid",$skuid);
	    $sth->execute();
	    $row = $sth->fetch(PDO::FETCH_NUM);
	    return $row[0];
	}
	else
	{
		return "";
	}
}

//获得srvzone
function getsrvzone($zone)
{
	global $config;

    $sql="select id from tbl_srv_zonelist where addr_list like :zone";
    $sth = $config['db_conn']->prepare($sql);
    $sth->bindValue(':zone', '%'.$zone.'%', PDO::PARAM_STR);
    $sth->execute();
    $row = $sth->fetch(PDO::FETCH_NUM);
    return $row[0];
}

//校验是否要验证用户手机
function ischeckmobile($userid)
{
	global $config;

	$sql="select wx_openid,mobile from tbl_customer where id=:userid";
	$sth = $config['db_conn']->prepare($sql);
	$sth->bindValue(":userid",$userid);
	$sth->execute();
	$row = $sth->fetch(PDO::FETCH_NUM);
	$wx_openid = $row[0];
	$mobile = $row[1];
	if (isempty($mobile) || isempty($wx_openid))
	{
		return true;
	}
	return false;
}

//退出php程序
function exit_php()
{
	db_close();
	exit;
}

//GET方式请求微信接口
function wxapiget($wxapi)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $wxapi);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36");
	curl_setopt($ch, CURLOPT_ENCODING, "gzip");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

	$output = curl_exec($ch);
	if (curl_errno($ch))
	{
		return curl_error($ch);
	}
	curl_close($ch);

	$content = (array)json_decode($output, true);
	return $content;

//	print_r($output);
	//foreach ($content['ip_list'] as $key => $value) {
	//	testlog($value);
	//}
}

//POST方式请求微信接口
function wxapipost($wxapi, $data)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $wxapi);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.75 Safari/537.36");

	$output = curl_exec($ch);

	if (curl_errno($ch))
	{
		return curl_error($ch);
	}
	curl_close($ch);

	//由接收端自行处理json编解码
	//$content = (array)json_decode($output, true);
	return $output;
}

//获得API的access_token 调用jssdk时需要使用 
function getapitoken()
{
	global $config;

	//首先读取临时文件中保存的token
	$fhandler = fopen("../tmp/weixintoken.txt", "r");
	if ($fhandler)
	{
		$token = fgets($fhandler);
		$expirestime = fgets($fhandler);
		fclose($fhandler);
	}

	if (!isempty($token) && time() < $expirestime)//没有过期
	{
		return $token;
	}

	//如果没有token或token已经过期，则再去微信请求token
	else
	{
		$url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=".$config['appid']."&secret=".$config['appsecret']."";
		$tokenarr = wxapiget($url);

		$token = $tokenarr['access_token'];
		$expires_in = $tokenarr['expires_in'];
		$expirestime = time() + $expires_in - 200;

		//保存token到临时文件中
		$fhandler = fopen("../tmp/weixintoken.txt", "w");
		if ($fhandler)
		{
			fwrite($fhandler, $token.'\r\n');
			fwrite($fhandler, $expirestime.'\r\n');
			fclose($fhandler);
		}
	}

	return $token;
}

//设置用户端COOKIE 10年有效期
function setusercookie($userid, $openid)
{
	setcookie("userid", $userid, time() + 315360000);
	setcookie("openid", $openid, time() + 315360000);
}
///////////////////////////////////////////////
//根据timeinfoid获得显示时段名称
function gettimeinfo($timeinfo)
{
	switch ($timeinfo) {
		case 1:
			$timeinfoname="上午 8:30-11:30";
			break;
		case 2:
			$timeinfoname="下午 13:00-17:00";
			break;
		case 3:
			$timeinfoname="晚上 19:00-21:00";
			break;
		case 4:
			$timeinfoname="任意时段";
			break;
		default:
			$timeinfoname="任意时段";
			break;
	}

	return $timeinfoname;
}

//根据分类id获得分类名称
function getcatnamebycatid($catid)
{
	switch ($catid) {
		case 1:
			$catname="家电清洗";
			break;
		case 2:
			$catname="地暖保养";
			break;
		case 3:
			$catname="家居清洁";
			break;
		case 4:
			$catname="空气清洁";
			break;
		default:
			$catname="家电清洗";
			break;
	}

	return $catname;
}

//根据订单号查cat_id
function getcatidbyorder($orderid)
{
	global $config;

	$sql2="select a.cat_id from tbl_product as a, tbl_order_item as b where b.order_id=:order_id and b.product_id=a.id limit 1";
	$sth2 = $config['db_conn']->prepare($sql2);
	$sth2->bindValue(":order_id", $orderid);
	$sth2->execute();
	$row2 = $sth2->fetch(PDO::FETCH_NUM);
	$catid = $row2[0];
	
	return $catid;
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

//根据订单的状态标志位获得状态显示名称
function getstatusname($orderstatus)
{
	switch ($orderstatus) {
		case 0:	//待支付订单
		case 1: //已分配资源订单
		case 11: //订单等待线下支付
			$statusname="待支付";
			break;

		case 10: //订单完成支付
		case 20: //成功派单
		case 30: //上门服务中
		case 31: //服务进行中
			$statusname="未完成";
			break;

		case 90: //未付款取消
		case 91: //已付款但未派单时取消
		case 92: //派单后取消
			$statusname="已取消";
			break;

		case 100: //订单完成且余款结清
		case 101: //订单完成，用户评价完成
		case 110: //订单回访完毕
			$statusname="已完成";
			break;
	}

	return $statusname;
}

//获取行政区域名称
function getzonename($zone)
{
	global $config_zonelist;
	foreach ($config_zonelist as $key => $value)
	{
		if ($key == $zone)
		{
			return $value;
		}
	}
	return "";
}

//获得经纬度
function getLatLong($address)
{ 
    if (!is_string($address))
    	return "";

	$url='http://api.map.baidu.com/geocoder/v2/?address='.$address.'&output=json&ak=DB9a87e557c368f3ad394a7c9e82b514';  
    if ($result=file_get_contents($url))  
    {
        $res = explode(',"lat":', substr($result, 40,36));  
        return $res;  
    } 
}

//计算geohash
function encode_geohash($latitude, $longitude, $deep)
{
	$BASE32	= '0123456789bcdefghjkmnpqrstuvwxyz';
	$bits = array(16,8,4,2,1);
	$lat = array(-90.0, 90.0);
	$lon = array(-180.0, 180.0);
	
	$bit = $ch = $i = 0;
	$is_even = 1;
	$i = 0;
	$mid;
	$geohash = '';
	while($i < $deep)
	{
		if ($is_even)
		{
			$mid = ($lon[0] + $lon[1]) / 2;
			if($longitude > $mid)
			{
				$ch |= $bits[$bit];
				$lon[0] = $mid;
			}else{
				$lon[1] = $mid;
			}
 		} else{
			$mid = ($lat[0] + $lat[1]) / 2;
			if($latitude > $mid)
			{
				$ch |= $bits[$bit];
				$lat[0] = $mid;
			}else{
				$lat[1] = $mid;
			}
		}
		
		$is_even = !$is_even;
		if ($bit < 4)
			$bit++;
		else {
			$i++;
			$geohash .= $BASE32[$ch];
			$bit = 0;
			$ch = 0;
		}
	}
	return $geohash;
}

//判断字符串为空
function isempty($str)
{
	if(!isset($str) || empty($str) || $str == null || strlen($str) <= 0)
	{
		return true;
	}
	else
	{
		return false;
	}
}

//字符串左侧补0
function zeroleftstr($str,$len)
{
	$num = $str;
	$bit = $len;
	$num_len = strlen($num);

	$zero = '';
	for($i = $num_len; $i<$bit; $i++)
	{
		$zero .= "0";
	}
	$real_num = $zero.$str;
	return $real_num;
}

function getsubstr($str, $id)
{
	$arr = explode('|', $str);
	return $arr[$id];
}

//调试函数
function testlog($str)
{
	echo $str."<br>";
}

//unicode转化为汉字
function unicode2utf8($str)
{
	if(!$str)
	{
		return $str;
	}
	$decode = json_decode($str);
	if($decode)
	{
		return $decode;
	}
	$str = '["' . $str . '"]';
	$decode = json_decode($str);
	if(count($decode) == 1)
	{
		return $decode[0];
	}
	return $str;
}

function tracelog($tip)
{
	$fhandler = fopen("../tmp/tmp.txt", "a");
    fwrite($fhandler, $tip."\r\n");
	fclose($fhandler);
}
?>