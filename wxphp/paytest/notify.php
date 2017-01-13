<?php
ini_set('date.timezone','Asia/Shanghai');
//error_reporting(E_ERROR);

include("../pm/conn.php");

require_once "../lib/WxPay.Api.php";
require_once '../lib/WxPay.Notify.php';
require_once 'log.php';

//初始化日志
$logHandler= new CLogFileHandler("../logs/".date('Y-m-d').'.log');
$log = Log::Init($logHandler, 15);

class PayNotifyCallBack extends WxPayNotify
{
	//查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);
		Log::DEBUG("query:" . json_encode($result));
		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		Log::DEBUG("call back:" . json_encode($data));
		$notfiyOutput = array();
		
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}

		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}

		//更改数据库
		$transaction_id = $data["transaction_id"]; //交易号 source_no
		$time_end = $data["time_end"]; //支付时间 pay_date
		$orderid = $data["attach"]; //订单号
		$cash_fee = $data["cash_fee"]; //支付金额（分）
		$order_status = 100; //支付成功

		$cash_fee = $cash_fee/100; 

		db_open();

        $sql="update tbl_order set source_no=:source_no, pay_date=:pay_date, payment_paid=:payment_paid where id=:orderid";
        $sth = $config['db_conn']->prepare($sql);
        $sth->bindValue(":source_no",$transaction_id);
        $sth->bindValue(":pay_date",$time_end);
        $sth->bindValue(":payment_paid",$cash_fee);
        $sth->bindValue(":orderid",$orderid);
        $sth->execute(); 

		return true;
	}
}

Log::DEBUG("begin notify");
$notify = new PayNotifyCallBack();
$notify->Handle(false);
