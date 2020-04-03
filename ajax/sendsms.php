<?php 
namespace Aliyun\DySDKLite\Sms;
session_start();
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';
require_once "../include/SignatureHelper.php";
use Aliyun\DySDKLite\SignatureHelper;

$action = isset($_POST['action']) ? addslashes(trim($_POST['action'])) : '';
$name = isset($_POST['name']) ? addslashes(trim($_POST['name'])) : '';//发送到的用户名
$sitetitle = isset($_POST['sitetitle']) ? addslashes(trim($_POST['sitetitle'])) : '';
$pluginsname = isset($_POST['pluginsname']) ? addslashes(trim($_POST['pluginsname'])) : '';

//重置短信验证码
$randCode = '';
$chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPRSTUVWXYZ23456789';
for ( $i = 0; $i < 5; $i++ ){
	$randCode .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
}
$_SESSION[$action.'code'] = strtoupper($randCode);

$setphonelogin=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../../plugins/'.$pluginsname.'/config/setphonelogin.php'),'<?php die; ?>'));
$result=sendSms($setphonelogin['accessKeyId'],$setphonelogin['accessKeySecret'],$setphonelogin['templatecode'],$setphonelogin['signname'],$name,$sitetitle,$_SESSION[$action.'code'],$setphonelogin['iscontain']);
if($result->Code=="OK"){
	$_SESSION['new'.$action] = $name;
	$json=json_encode(array("error_code"=>0,"message"=>"发送验证码成功"));
	echo $json;
}else if($result->Code=="isv.BUSINESS_LIMIT_CONTROL"){
	$json=json_encode(array("error_code"=>-3,"message"=>"发送验证码失败，业务限流。"));
	echo $json;
}else{
	$json=json_encode(array("error_code"=>-2,"message"=>"发送验证码失败".$result->Code));
	echo $json;
}
/**
 * 发送短信
 */
function sendSms($accessKeyId,$accessKeySecret,$templatecode,$signname,$name,$sitetitle,$code,$iscontain) {
    $params = array ();
    // *** 需用户填写部分 ***
    // fixme 必填: 请参阅 https://ak-console.aliyun.com/ 取得您的AK信息
    // fixme 必填: 短信接收号码
    $params["PhoneNumbers"] = $name;
    // fixme 必填: 短信签名，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
    $params["SignName"] = $signname;
    // fixme 必填: 短信模板Code，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
    $params["TemplateCode"] = $templatecode;
    // fixme 可选: 设置模板参数, 假如模板中存在变量需要替换则为必填项
    if($iscontain=="y"){
		$params['TemplateParam'] = Array (
			"code" => $code,
			"product" => $sitetitle
		);
	}else{
		$params['TemplateParam'] = Array (
			"code" => $code
		);
	}
    // fixme 可选: 设置发送短信流水号
    $params['OutId'] = "12345";
    // fixme 可选: 上行短信扩展码, 扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段
    $params['SmsUpExtendCode'] = "1234567";
    // *** 需用户填写部分结束, 以下代码若无必要无需更改 ***
    if(!empty($params["TemplateParam"]) && is_array($params["TemplateParam"])) {
        $params["TemplateParam"] = json_encode($params["TemplateParam"], JSON_UNESCAPED_UNICODE);
    }
    // 初始化SignatureHelper实例用于设置参数，签名以及发送请求
    $helper = new SignatureHelper();
    // 此处可能会抛出异常，注意catch
    $content = $helper->request(
        $accessKeyId,
        $accessKeySecret,
        "dysmsapi.aliyuncs.com",
        array_merge($params, array(
            "RegionId" => "cn-hangzhou",
            "Action" => "SendSms",
            "Version" => "2017-05-25",
        ))
        // fixme 选填: 启用https
        // ,true
    );
    return $content;
}
?>