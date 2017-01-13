<?php
//我的界面

include("./pm/conn.php");

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



echo json_encode($content, true);
exit_php();

?>