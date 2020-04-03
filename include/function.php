<?php
function showOAuthAvatar($options,$oauthtype){
	switch($oauthtype){
		case "qq":
			return $options->pluginUrl."/TleMultiFunction/assets/images/qq.png";
			break;
		case "weibo":
			return $options->pluginUrl."/TleMultiFunction/assets/images/weibo.png";
			break;
		case "weixin":
			return $options->pluginUrl."/TleMultiFunction/assets/images/weixin.png";
			break;
	}
}
function isProtocol(){
    if (isHttps()) {
        return "https://";
    } else {
        return "https://";
    }
}
function isHttps(){
    if (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') {
        return true;
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
        return true;
    } elseif (!empty($_SERVER['HTTP_FRONT_END_HTTPS']) && strtolower($_SERVER['HTTP_FRONT_END_HTTPS']) !== 'off') {
        return true;
    }
    return false;
}
function getQQUserInfo($access_token,$oauth_consumer_key,$openid){
	$data=array(
		"access_token"=>$access_token,
		"oauth_consumer_key"=>$oauth_consumer_key,
		"openid"=>$openid
	);
	$url = 'https://graph.qq.com/user/get_user_info';
	$client = Typecho_Http_Client::get();
	if ($client) {
		$str = "";
		foreach ( $data as $key => $value ) { 
			$str.= "$key=" . urlencode( $value ). "&" ;
		}
		$data = substr($str,0,-1);
		$client->setData($data)
			->setTimeout(30)
			->send($url);
		$status = $client->getResponseStatus();
		$rs = $client->getResponseBody();
		$arr=json_decode($rs,true);
		return $arr;
	}
	return 0;
}
function getQQOpenID($access_token){
	$data=array(
		"access_token"=>$access_token
	);
	$url = 'https://graph.qq.com/oauth2.0/me';
	$client = Typecho_Http_Client::get();
	if ($client) {
		$str = "";
		foreach ( $data as $key => $value ) { 
			$str.= "$key=" . urlencode( $value ). "&" ;
		}
		$data = substr($str,0,-1);
		$client->setData($data)
			->setTimeout(30)
			->send($url);
		$status = $client->getResponseStatus();
		$rs = $client->getResponseBody();
		if(strpos($rs, "callback") !== false){
			$lpos = strpos($rs, "(");
			$rpos = strrpos($rs, ")");
			$rs = substr($rs, $lpos + 1, $rpos - $lpos -1);
		}
		$user = json_decode($rs);
		if(!isset($user->error)){
			return $user;
		}
	}
	return 0;
}
function getQQAccessToken($qq_appid,$qq_appkey,$qq_callback,$code){
	$data=array(
		"grant_type"=>'authorization_code',
		"client_id"=>$qq_appid,
		"client_secret"=>$qq_appkey,
		"code"=>$code,
		"redirect_uri"=>$qq_callback
	);
	$url = 'https://graph.qq.com/oauth2.0/token';
	$client = Typecho_Http_Client::get();
	if ($client) {
		$str = "";
		foreach ( $data as $key => $value ) { 
			$str.= "$key=" . urlencode( $value ). "&" ;
		}
		$data = substr($str,0,-1);
		$client->setData($data)
			->setTimeout(30)
			->send($url);
		$status = $client->getResponseStatus();
		$rs = $client->getResponseBody();
		parse_str($rs,$arr);
		return $arr;
	}
	return 0;
}
function checkUser($user,$pass,$token){
	$data=array(
		"user"=>$user,
		"pass"=>$pass,
		"token"=>$token
	);
	$url = 'http://api.tongleer.com/open/login.php';
	$client = Typecho_Http_Client::get();
	if ($client) {
		$str = "";
		foreach ( $data as $key => $value ) { 
			$str.= "$key=" . urlencode( $value ). "&" ;
		}
		$data = substr($str,0,-1);
		$client->setData($data)
			//->setHeader('Authorization','Bearer '.$token)
			->setTimeout(30)
			->send($url);
		$status = $client->getResponseStatus();
		$rs = $client->getResponseBody();
		$arr=json_decode($rs,true);
		return $arr['code'];
	}
	return 0;
}
/*��ȡIP */
function getIP() {
    if (@$_SERVER["HTTP_X_FORWARDED_FOR"]) 
        $ip = $_SERVER["HTTP_X_FORWARDED_FOR"]; 
    else if (@$_SERVER["HTTP_CLIENT_IP"]) 
        $ip = $_SERVER["HTTP_CLIENT_IP"]; 
    else if ($_SERVER["REMOTE_ADDR"]) 
        $ip = $_SERVER["REMOTE_ADDR"]; 
    else if (getenv("HTTP_X_FORWARDED_FOR"))
        $ip = getenv("HTTP_X_FORWARDED_FOR"); 
    else if (getenv("HTTP_CLIENT_IP")) 
        $ip = getenv("HTTP_CLIENT_IP"); 
    else if (getenv("REMOTE_ADDR"))
        $ip = getenv("REMOTE_ADDR"); 
    else 
        $ip = "Unknown"; 
    return $ip; 
}
/**
 * ��ȡ�ͻ���IP��ַ
 * @param int $type [IP��ַ����]
 * @param bool $strict [�Ƿ����ϸ�ģʽ��ȡ]
 * @return mixed [�ͻ���IP��ַ]
 */
function client_ip($type = 0, $strict = false){
    $ip = null;
    // 0 �����ֶ��͵�ַ(127.0.0.1)
    // 1 ���س����ε�ַ(2130706433)
    $type = $type ? 1 : 0;
    if ($strict) {
        /* ��ֹIP��ַαװ���ϸ�ģʽ */
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos) {
                unset($arr[$pos]);
            }
            $ip = trim(current($arr));
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    /* IP��ַ�Ϸ�����֤ */
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
    return $ip[$type];
}
/**
 * cURL������
 * @param string $url [�����URL��ַ]
 * @param array $params [����Ĳ���]
 * @param bool $post [�Ƿ����POST��ʽ]
 * @return mixed [������|ʧ�ܷ���FALSE]
 */
function curl_tool($url, $params = [], $post = false){
    /* ����cURL��� */
    $ch = curl_init();

    /* ����URL���Ӳ��� */
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);// ���ó������ӵȴ�ʱ��
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);// ����cURL����ִ�е��ʱ��
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);// ��ִ�н�����ַ�������
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);// ������Ӧͷ��Ϣ�����ض���

    /* POST��GET���� */
    $params = http_build_query($params);// ���������ת��Ϊ�ַ�����ʽ
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    } else {
        $url = $url . ($params ? '?' : '') . $params;
    }
    curl_setopt($ch, CURLOPT_URL, $url);

    /* ץȡURL���ر���Դ */
    $response = curl_exec($ch);
    // if ($response === false) echo curl_error($ch);
    curl_close($ch);

    return $response;
}
?>