<?php 
/**
 * 使用Get的方式返回：challenge和capthca_id 此方式以实现前后端完全分离的开发模式 专门实现failback
 * @author Tanxu
 */
session_start();
error_reporting(0);
include '../../../../config.inc.php';
require_once dirname(dirname(__FILE__)) . '/include/class.geetestlib.php';
include '../include/function.php';

$get=TleMultiFunction_Plugin::getOptions();

$action = isset($_GET['action']) ? addslashes(trim($_GET['action'])) : "";
switch($action){
	case "init":
		$GtSdk = new GeetestLib($get["GT_CAPTCHA_ID"], $get["GT_PRIVATE_KEY"]);
		$data = array(
				"user_id" => "test",//test
				"client_type" => "web", //web,h5,web_view,native
				"ip_address" => getIP()//127.0.0.1
			);
		$status = $GtSdk->pre_process($data, 1);
		$_SESSION['gtserver'] = $status;
		$_SESSION['user_id'] = $data['user_id'];
		echo $GtSdk->get_response_str();
		break;
	case "verifylogin":
		
		break;
}

 ?>