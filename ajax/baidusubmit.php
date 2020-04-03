<?php 
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';
include dirname(__FILE__).'/../include/function.php';

$db = Typecho_Db::get();
$action = isset($_POST['action']) ? addslashes(trim($_POST['action'])) : '';
$pluginsname = isset($_POST['pluginsname']) ? addslashes(trim($_POST['pluginsname'])) : '';
$url = isset($_POST['url']) ? addslashes(trim($_POST['url'])) : '';
$cid = isset($_POST['cid']) ? addslashes(trim($_POST['cid'])) : '';
$setbaidusubmit=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../../plugins/'.$pluginsname.'/config/setbaidusubmit.php'),'<?php die; ?>'));

/*
$setuser_config=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../config/setuser_config.php'),'<?php die; ?>'));
$res=checkUser($setuser_config['username'],$setuser_config['password'],$setuser_config['access_token']);
switch($res){
	case 0:
		echo('服务器验证错误');return;
	case 101:
		echo('登录用户名不存在');return;
	case 102:
		echo('登录密码错误');return;
	case 103:
		echo('token不存在');return;
	case 104:
		echo('token已过期');return;
}
*/

$urls=array($url);
if($action=='baidusubmit'){
	$api = sprintf('http://data.zz.baidu.com/urls?site=%s&token=%s', @$setbaidusubmit['url'], @$setbaidusubmit['linktoken']);
}else if($action=='baiduziyuansubmit'){
	$api = sprintf('http://data.zz.baidu.com/urls?appid=%s&token=%s&type=realtime', @$setbaidusubmit['appid'], @$setbaidusubmit['resctoken']);
}

$client = Typecho_Http_Client::get();
if ($client) {
	$client->setData( implode(PHP_EOL, $urls ) )
		->setHeader('Content-Type', 'text/plain')
		->setTimeout(30)
		->send($api);

	$status = $client->getResponseStatus();
	$rs = $client->getResponseBody();
	$arr=json_decode($rs,true);
	if($status==200){
		$error='';
	}else{
		$error=$arr['message'];
	}
	
	$query= $db->select()->from('table.multi_baidusubmit')->where('bscid = ?', $cid); 
	$row = $db->fetchRow($query);
	if(count($row)==0){
		$result = array(
			'bscid'   =>  $cid,
			'url'   =>  $url,
			'instime'     =>  date('Y-m-d H:i:s',time()),
			'error'     =>  $error
		);
		$insert = $db->insert('table.multi_baidusubmit')->rows($result);
		$insertId = $db->query($insert);
		if($action=='baidusubmit'){
			$update = $db->update('table.multi_baidusubmit')->rows(array('linkstatus'   =>  $status))->where('bscid=?',$cid);
			$db->query($update);
		}else if($action=='baiduziyuansubmit'){
			$update = $db->update('table.multi_baidusubmit')->rows(array('rescstatus'   =>  $status))->where('bscid=?',$cid);
			$db->query($update);
		}
	}else{
		$update = $db->update('table.multi_baidusubmit')->rows(array(
			'url'   =>  $url,
			'instime'     =>  date('Y-m-d H:i:s',time()),
			'error'     =>  $error
		))->where('bscid=?',$cid);
		$updateRows= $db->query($update);
		if($action=='baidusubmit'){
			$update = $db->update('table.multi_baidusubmit')->rows(array('linkstatus'   =>  $status))->where('bscid=?',$cid);
			$db->query($update);
		}else if($action=='baiduziyuansubmit'){
			$update = $db->update('table.multi_baidusubmit')->rows(array('rescstatus'   =>  $status))->where('bscid=?',$cid);
			$db->query($update);
		}
	}
	echo $error;
	return;
}
echo -1;
?>