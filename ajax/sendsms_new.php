<?php 
namespace Aliyun\DySDKLite\Sms;
session_start();
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';
require_once "../include/SignatureHelper.php";
use Aliyun\DySDKLite\SignatureHelper;
use Typecho_Db;
use TleMultiFunction_Plugin;
use Typecho_Widget;
use Typecho_Common;
use PHPMailer;

$options = Typecho_Widget::widget('Widget_Options');
$db = Typecho_Db::get();
$get=TleMultiFunction_Plugin::getOptions();

$queryTitle= $db->select('value')->from('table.options')->where('name = ?', 'title'); 
$rowTitle = $db->fetchRow($queryTitle);

$action = isset($_POST['action']) ? addslashes(trim($_POST['action'])) : '';
$name = isset($_POST['name']) ? addslashes(trim($_POST['name'])) : '';
$submit = isset($_POST['submit']) ? addslashes(trim($_POST['submit'])) : '';
$smscode = isset($_POST['smscode']) ? addslashes(trim($_POST['smscode'])) : '';
$indexUrl = isset($_POST['indexUrl']) ? addslashes(trim($_POST['indexUrl'])) : '';

switch($submit){
	case "phone":
		if(!isset($get["enablePhonelogin"])||(isset($get["enablePhonelogin"])&&$get["enablePhonelogin"]!="y")){
			$json=json_encode(array("error_code"=>-1,"message"=>"未开启手机号登陆"));
			echo $json;
			return;
		}
		if(!preg_match("/^1[345678]{1}\d{9}$/",$name)){
			$json=json_encode(array("error_code"=>-2,"message"=>"手机号格式不正确"));
			echo $json;
			return;
		}
		if(!isset($_SESSION['phonecode'])||strcasecmp($_SESSION['phonecode'],$smscode)!=0){
			$json=json_encode(array("error_code"=>-3,"message"=>"短信验证码错误"));
			echo $json;
			return;
		}
		if (isset($_SESSION["newphone"])&&$name!=$_SESSION["newphone"]) {
			$json=json_encode(array("error_code"=>-4,"message"=>"填写手机号和发送验证码的手机号不一致"));
			echo $json;
			return;
		}
		$queryUser= $db->select()->from('table.users')->where('phone = ?', $name); 
		$rowUser = $db->fetchRow($queryUser);
		if(!$rowUser){
			$json=json_encode(array("error_code"=>-5,"message"=>"未找到该手机号"));
			echo $json;
			return;
		}
		$hashString = $rowUser['name'] . $rowUser['mail'] . $rowUser['password'];
		$hashValidate = Typecho_Common::hash($hashString);
		$token = base64_encode($rowUser['uid'] . '.' . $hashValidate . '.' . $options->gmtTime);
		$url = Typecho_Common::url('/passport/reset?token=' . $token, $indexUrl);
		$json=json_encode(array("error_code"=>0,"message"=>"OK","url"=>$url));
		echo $json;
		$_SESSION[$action.'code'] = mt_rand(100000,999999);
		break;
	default:
		$_SESSION[$action.'code'] = mt_rand(100000,999999);
		switch($action){
			case "phone":
				if(isset($get["enablePhonelogin"])&&$get["enablePhonelogin"]=="y"){
					if(!preg_match("/^1[345678]{1}\d{9}$/",$name)){
						$json=json_encode(array("error_code"=>-1,"message"=>"手机号格式不正确"));
						echo $json;
						break;
					}
					$result=sendPhoneSms(@$get['aliAccessKeyId'],@$get['aliAccessKeySecret'],@$get['aliTemplateCode'],@$get['aliSignName'],$name,$rowTitle["value"],$_SESSION[$action.'code'],@$get['aliIsExistName']);
					if($result->Code=="OK"){
						$_SESSION['new'.$action] = $name;
						$json=json_encode(array("error_code"=>0,"message"=>"发送验证码成功"));
						echo $json;
						break;
					}else if($result->Code=="isv.BUSINESS_LIMIT_CONTROL"){
						$json=json_encode(array("error_code"=>-3,"message"=>"发送验证码失败，业务限流。"));
						echo $json;
						break;
					}else{
						$json=json_encode(array("error_code"=>-2,"message"=>"发送验证码失败".$result->Code));
						echo $json;
						break;
					}
				}else{
					$json=json_encode(array("error_code"=>-3,"message"=>"未开启手机号登陆"));
					echo $json;
					break;
				}
				break;
			case "mail":
				if(isset($get["enableMaillogin"])&&$get["enableMaillogin"]=="y"){
					if(!preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$name)){
						$json=json_encode(array("error_code"=>-1,"message"=>"邮箱格式不正确"));
						echo $json;
						break;
					}
					$result=sendMailSms(@$get['host'],@$get['port'],@$get['username'],@$get['password'],@$get['secure'],$_SESSION[$action.'code'],$name,$rowTitle["value"]);
					
					if($result){
						$_SESSION['new'.$action] = $name;
						$json=json_encode(array("error_code"=>0,"message"=>"发送验证码成功"));
						echo $json;
						break;
					}else{
						$json=json_encode(array("error_code"=>-2,"message"=>"发送验证码失败"));
						echo $json;
						break;
					}
				}else{
					$json=json_encode(array("error_code"=>-3,"message"=>"未开启邮箱登陆"));
					echo $json;
					break;
				}
				break;
		}
}
/**
 * 发送短信
 */
function sendPhoneSms($accessKeyId,$accessKeySecret,$templatecode,$signname,$name,$sitetitle,$code,$iscontain) {
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
/**
 * 发送邮件
 */
function sendMailSms($mailsmtp,$mailport,$mailuser,$mailpass,$secure,$code,$email,$title){
	require_once '../include/PHPMailer/PHPMailerAutoload.php';

	$phpMailer = new PHPMailer();
	
	$get=TleMultiFunction_Plugin::getOptions();

	/* SMTP设置 */
	$phpMailer->isSMTP();
	$phpMailer->SMTPAuth = true;
	$phpMailer->Host = $mailsmtp;
	$phpMailer->Port = $mailport;
	$phpMailer->Username = $mailuser;
	$phpMailer->Password = $mailpass;
	$phpMailer->isHTML(true);

	if ('none' != $secure) {
		$phpMailer->SMTPSecure = $secure;
	}

	$phpMailer->setFrom($mailuser, $title);
	$phpMailer->addAddress($email, $email);

	$phpMailer->Subject = '【'.$title.'】获取验证码';
	$phpMailer->Body    = '<p>' . $email . ' 您好，欢迎使用【'.$title.'】验证码服务，您的验证码是：'.$code;

	if(!$phpMailer->send()) {
		return false;
	} else {
		return true;
	}
}
?>