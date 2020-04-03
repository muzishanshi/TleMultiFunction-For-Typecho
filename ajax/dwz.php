<?php 
/**
 * 网址缩短
 */
date_default_timezone_set('Asia/Shanghai');
include '../../../../config.inc.php';
include dirname(__FILE__).'/../include/function.php';

$db = Typecho_Db::get();

$action = isset($_POST['action']) ? addslashes($_POST['action']) : '';
if($action=='shorturl'){
	$longurls = isset($_POST['longurls']) ? addslashes($_POST['longurls']) : '';
	$isred = isset($_POST['isred']) ? addslashes($_POST['isred']) : '';
	$customdwz = isset($_POST['customdwz']) ? addslashes($_POST['customdwz']) : '';
	$type = isset($_POST['type']) ? addslashes($_POST['type']) : '';
	$domain = isset($_POST['domain']) ? addslashes($_POST['domain']) : '';
	$setuser_config=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../config/setuser_config.php'),'<?php die; ?>'));
	$data=array(
		"urls"=>$longurls,
		"isred"=>$isred,
		"custom"=>$customdwz,
		"types"=>$type,
		"domain"=>$domain,
		"user"=>$setuser_config['username'],
		"pass"=>$setuser_config['password'],
		"token"=>$setuser_config['access_token']
    );
	$client = Typecho_Http_Client::get();
	if ($client) {
		$str = "";
		foreach ( $data as $key => $value ) { 
			$str.= "$key=" . urlencode( $value ). "&" ;
		}
		$data = substr($str,0,-1);
		$client->setData($data)
			->setTimeout(30)
			->send('http://api.tongleer.com/open/dwz.php');
		$status = $client->getResponseStatus();
		$rs = $client->getResponseBody();
		$arr=json_decode($rs,true);
		$res='';
		if($arr['code']==100){
			foreach($arr['data'] as $key=>$val){
				$res=$res.','.$val;
			}
			$res=substr($res,1);
			//整理长网址
			$longurlarr=explode(',',$longurls);
			$newlongurl=array();
			foreach($longurlarr as $key=>$val){
				array_push($newlongurl,$val);
			}
			//整理短网址
			$shortarr=explode(',',$res);
			$newshortdata=array();
			foreach($shortarr as $key=>$val){
				if($val!=''&&!strstr($val,"http")){
					array_push($newshortdata,$val);
				}
			}
			//插入数据表
			for ($i=0;$i<count($newshortdata);$i++) { 
				$query= $db->select()->from('table.multi_dwz')->where('shorturl = ?', $newshortdata[$i]); 
				$row = $db->fetchRow($query);
				if(count($row)==0){
					$result = array(
						'longurl'   =>  $newlongurl[$i],
						'shorturl'   =>  $newshortdata[$i],
						'isred'     =>  $isred,
						'instime'     =>  date('Y-m-d H:i:s',time())
					);
					$insert = $db->insert('table.multi_dwz')->rows($result);
					$insertId = $db->query($insert);
				}else{
					$update = $db->update('table.multi_dwz')->rows(array('isred'   =>  $isred))->where('shorturl=?',$newshortdata[$i]);
					$db->query($update);
				}
			}
			echo $domain.'|'.$res;
			return;
		}else if($arr['code']==106){
			echo 106;
			return;
		}
	}
	echo -1;
	return;
}
?>