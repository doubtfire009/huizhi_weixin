<?php
//购物车主界面请求数据
//主题1|主题2 取主题1作为title显示

/* 输出格式
{"product_list":
	[
		{
			"pid":"2",
			"icon":"",
			"title":"\u6cb9\u70df\u673a",
			"skulist":
				[
					{
						"skuid":"1",
						"skuname":"\u4e2d\u5f0f\u6cb9\u70df\u673a",
						"min_nums":"3",
						"base_mins":"30",
						"step_mins":"10",
						"base_price":"0.00",
						"step_price":"20.00",
						"skucartnums":null
					},
					{
						"skuid":"2",
						"skuname":"\u5b9a\u91d1",
						"min_nums":"1",
						"base_mins":"60",
						"step_mins":"20",
						"base_price":"0.00",
						"step_price":"100.00",
						"skucartnums":null
					},
					{
						"skuid":"3",
						"skuname":"\u4fa7\u5438\u5f0f",
						"min_nums":"1",
						"base_mins":"30",
						"step_mins":"0",
						"base_price":"0.00",
						"step_price":"10.00",
						"skucartnums":null
					}
				]
		},
		{
			"pid":"3",
			"icon":"",
			"title":"\u7a7a\u8c03",
			"skulist":[]
		},
		{
			"pid":"4",
			"icon":"",
			"title":"\u7535\u51b0\u7bb1",
			"skulist":[]
		}
	]
}
*/

include("./pm/conn.php");

$cateid = $_GET['cateid'];

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

//该类目下的产品信息
$sql="select id, icon, title from tbl_product where cat_id=:cat_id and status=1 order by sort desc";
$sth = $config['db_conn']->prepare($sql);
$sth->bindValue(":cat_id",$cateid);
$sth->execute();

$productlist = array();
while($row = $sth->fetch(PDO::FETCH_NUM))
{
    $pid = $row[0];
    $icon = $row[1];
    $title = getsubstr($row[2],0);

    //该产品的SKULIST
	$sql2="select id,name,min_nums,base_mins,step_mins,base_price,step_price from tbl_sku_list where prod_id=:productid";
	$sth2 = $config['db_conn']->prepare($sql2);
	$sth2->bindValue(":productid",$pid);
	$sth2->execute();
	$skulist = array();
	$totalcartnums = 0;
	while ($row2 = $sth2->fetch(PDO::FETCH_NUM))
	{
	    $skuid = $row2[0];
	    $skuname = $row2[1];
	    $min_nums = $row2[2];
	    $base_mins = $row2[3];
	    $step_mins = $row2[4];
	    $base_price = $row2[5];
	    $step_price = $row2[6];

		//该用户购物车数据
		$sql3="select nums from tbl_shopcart where sku_id=:sku_id and customer_id=:customer_id and prod_id=:prod_id";
		$sth3 = $config['db_conn']->prepare($sql3);
		$sth3->bindValue(":sku_id",$skuid);
		$sth3->bindValue(":customer_id",$userid);
		$sth3->bindValue(":prod_id",$pid);
		$sth3->execute();
		$row3 = $sth3->fetch(PDO::FETCH_NUM);
		$skucartnums = $row3[0];
		if (isempty($skucartnums))
		{
			$skucartnums = 0;
		}

		$eachsku['skuid'] = $skuid;
		$eachsku['skuname'] = $skuname;
		$eachsku['min_nums'] = $min_nums;
		$eachsku['base_mins'] = $base_mins;
		$eachsku['step_mins'] = $step_mins;
		$eachsku['base_price'] = $base_price;
		$eachsku['step_price'] = $step_price;
		$eachsku['skucartnums'] = $skucartnums;
		$skulist[] = $eachsku;
		$totalcartnums += $skucartnums;
	}

    $each['pid'] = $pid;
    $each['icon'] = $icon;
    $each['title'] = $title;
    $each['totalcartnums'] = $totalcartnums;
    $each['skulist'] = $skulist;

    $productlist[] = $each;
}
$content['product_list'] = $productlist;

echo json_encode($content, true);

exit_php();
?>
