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
/*获取IP */
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
 * 获取客户端IP地址
 * @param int $type [IP地址类型]
 * @param bool $strict [是否以严格模式获取]
 * @return mixed [客户端IP地址]
 */
function client_ip($type = 0, $strict = false){
    $ip = null;
    // 0 返回字段型地址(127.0.0.1)
    // 1 返回长整形地址(2130706433)
    $type = $type ? 1 : 0;
    if ($strict) {
        /* 防止IP地址伪装的严格模式 */
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
    /* IP地址合法性验证 */
    $long = sprintf("%u", ip2long($ip));
    $ip = $long ? [$ip, $long] : ['0.0.0.0', 0];
    return $ip[$type];
}
/**
 * cURL请求函数
 * @param string $url [请求的URL地址]
 * @param array $params [请求的参数]
 * @param bool $post [是否采用POST形式]
 * @return mixed [请求结果|失败返回FALSE]
 */
function curl_tool($url, $params = [], $post = false){
    /* 创建cURL句柄 */
    $ch = curl_init();

    /* 设置URL连接参数 */
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);// 设置尝试连接等待时间
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);// 设置cURL函数执行的最长时间
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);// 将执行结果以字符串返回
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);// 根据响应头信息进行重定向

    /* POST与GET请求 */
    $params = http_build_query($params);// 将请求参数转换为字符串形式
    if ($post) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
    } else {
        $url = $url . ($params ? '?' : '') . $params;
    }
    curl_setopt($ch, CURLOPT_URL, $url);

    /* 抓取URL并关闭资源 */
    $response = curl_exec($ch);
    // if ($response === false) echo curl_error($ch);
    curl_close($ch);

    return $response;
}
?>