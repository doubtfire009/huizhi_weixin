<?php
    $config = array(
        'dsn' 			 => "mysql:host=localhost;dbname=dev_mayhomer",
        'db_user'        => 'nanjing',
        'db_password'    => 'nanjing2017',
        'db_conn'        => 'db_conn_obj',
        'domain'         => 'http://m.huizhish.com/',
        'appid'          => 'wxfe42c2506809733d',
        'appsecret'      => '2f42d9957be9bd694e4a28410fd74259',
        'index'			 => 'http://m.huizhish.com/wxphp/index.php'
    );


    $config_zonelist = array(
        "2101" => "虹口区",
        "2110" => "闸北区",
        "2102" => "嘉定区",
        "2103" => "宝山区",
        "2104" => "浦东新区",
        "2105" => "川沙区",
        "2106" => "南汇区",
        "2107" => "青浦区",
        "2108" => "松江区"
    );

    //每个师傅每个时间段的最大工作时间
    $config_timelimits = array(
        "morning" => "120",
        "afternoon" => "120",
        "night" => "120",
        "wholeday" => "480"
        );

    //每个师傅每个时间段的冗余时间
    $config_timemargin = array(
        "morning" => "30",
        "afternoon" => "30",
        "night" => "30",
        "wholeday" => "90"
        );

    //是否打开任意时段可选的开关 1--打开 0--关闭
    $config_anytime = 0;

    //单人单任务的最大允许时间
    $config_maxminutes_oneperson_onetask = 600;

    //用户侧等待付款最大时间（秒）
    $config_maxlefttime = 60*30;
?>