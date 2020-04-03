<?php
/**
 * TleMultiFunction Plugin
 * 多功能行为
 *
 */
class TleMultiFunction_Action extends Typecho_Widget implements Widget_Interface_Do
{
    /** @var  数据操作对象 */
    private $_db;

    /** @var  插件配置信息 */
    private $_cfg;
    
    /** @var  系统配置信息 */
    private $_options;

    /**
     * 初始化
     * @return $this
     */
    public function init(){
        $this->_db = Typecho_Db::get();
        $this->_options = $this->widget('Widget_Options');
        $this->_cfg = Helper::options()->plugin('TleMultiFunction');
    }

    /**
     * action 入口
     *
     * @access public
     * @return void
     */
    public function action(){
        $this->on($this->request->is('do=phoneLogin'))->phoneLogin();
		$this->on($this->request->is('do=mailLogin'))->mailLogin();
		$this->on($this->request->is('do=oAuthLogin'))->oAuthLogin();
		$this->on($this->request->is('do=oAuthCallback'))->oAuthCallback();
		$this->on($this->request->is('do=forget'))->forget();
		$this->on($this->request->is('do=delUser'))->delUser();
		$this->on($this->request->is('do=verifyCode'))->verifyCode();
    }

    /**
     * 手机号登录方法
     */
    public function phoneLogin(){
        if (Typecho_Widget::widget('TleMultiFunction_Console')->phoneLoginForm()->validate()) {
            $this->response->goBack();
        }

        $this->init();
		
		$form_data = $this->request->from('enablePhonelogin','defaultPhoneGroup','aliAccessKeyId','aliAccessKeySecret','aliIsExistName','aliTemplateCode','aliSignName');
		
		$enablePhonelogin=$form_data["enablePhonelogin"];
		$defaultPhoneGroup=$form_data["defaultPhoneGroup"];
		$aliAccessKeyId=$form_data["aliAccessKeyId"];
		$aliAccessKeySecret=$form_data["aliAccessKeySecret"];
		$aliIsExistName=$form_data["aliIsExistName"];
		$aliTemplateCode=$form_data["aliTemplateCode"];
		$aliSignName=$form_data["aliSignName"];
		
		if(get_magic_quotes_gpc()){
			$enablePhonelogin=stripslashes($form_data["enablePhonelogin"]);
			$defaultPhoneGroup=stripslashes($form_data["defaultPhoneGroup"]);
			$aliAccessKeyId=stripslashes($form_data["aliAccessKeyId"]);
			$aliAccessKeySecret=stripslashes($form_data["aliAccessKeySecret"]);
			$aliIsExistName=stripslashes($form_data["aliIsExistName"]);
			$aliTemplateCode=stripslashes($form_data["aliTemplateCode"]);
			$aliSignName=stripslashes($form_data["aliSignName"]);
		}
		
		$get=TleMultiFunction_Plugin::getOptions();
		
		$get["enablePhonelogin"]=$enablePhonelogin;
		$get["defaultPhoneGroup"]=$defaultPhoneGroup;
		$get["aliAccessKeyId"]=$aliAccessKeyId;
		$get["aliAccessKeySecret"]=$aliAccessKeySecret;
		$get["aliIsExistName"]=$aliIsExistName;
		$get["aliTemplateCode"]=$aliTemplateCode;
		$get["aliSignName"]=$aliSignName;
		
		if($get["enablePhonelogin"]=="y"){
			$config=$this->_db->getConfig();
			TleMultiFunction_Plugin::alterColumn($this->_db,$config[0]->database,$this->_db->getPrefix().'users','phone','varchar(16) DEFAULT NULL');
		}
		
		TleMultiFunction_Plugin::saveOptions($get);
        
		$result = true;
		/** 提示信息 */
		$this->widget('Widget_Notice')->set(true === $result ? _t('保存成功') : _t('保存失败'), true === $result ? 'success' : 'notice');
		
        /** 转向原页 */
        $this->response->goBack();
    }
	/**
     * 邮箱登录方法
     */
    public function mailLogin(){
        if (Typecho_Widget::widget('TleMultiFunction_Console')->mailLoginForm()->validate()) {
            $this->response->goBack();
        }

        $this->init();
		
		$form_data = $this->request->from('enableMaillogin','defaultMailGroup','host','port','username','password','secure');
		
		$enableMaillogin=$form_data["enableMaillogin"];
		$defaultMailGroup=$form_data["defaultMailGroup"];
		$host=$form_data["host"];
		$port=$form_data["port"];
		$username=$form_data["username"];
		$password=$form_data["password"];
		$secure=$form_data["secure"];
		
		if(get_magic_quotes_gpc()){
			$enableMaillogin=stripslashes($form_data["enableMaillogin"]);
			$defaultMailGroup=stripslashes($form_data["defaultMailGroup"]);
			$host=stripslashes($form_data["host"]);
			$port=stripslashes($form_data["port"]);
			$password=stripslashes($form_data["password"]);
			$username=stripslashes($form_data["username"]);
			$secure=stripslashes($form_data["secure"]);
		}
		
		$get=TleMultiFunction_Plugin::getOptions();
		
		$get["enableMaillogin"]=$enableMaillogin;
		$get["defaultMailGroup"]=$defaultMailGroup;
		$get["host"]=$host;
		$get["port"]=$port;
		$get["password"]=$password;
		$get["username"]=$username;
		$get["secure"]=$secure;
		
		TleMultiFunction_Plugin::saveOptions($get);
        
		$result = true;
		/** 提示信息 */
		$this->widget('Widget_Notice')->set(true === $result ? _t('保存成功') : _t('保存失败'), true === $result ? 'success' : 'notice');
		
        /** 转向原页 */
        $this->response->goBack();
    }
	/**
     * 社交登录方法
     */
    public function oAuthLogin(){
        if (Typecho_Widget::widget('TleMultiFunction_Console')->oAuthLoginForm()->validate()) {
            $this->response->goBack();
        }

        $this->init();
		
		$form_data = $this->request->from('enableQQlogin','defaultQQGroup','qq_appid','qq_appkey','qq_callback','enableWeibologin','defaultWeiboGroup','wb_akey','wb_skey','wb_callback_url');
		
		$enableQQlogin=$form_data["enableQQlogin"];
		$defaultQQGroup=$form_data["defaultQQGroup"];
		$qq_appid=$form_data["qq_appid"];
		$qq_appkey=$form_data["qq_appkey"];
		$qq_callback=$form_data["qq_callback"];
		$enableWeibologin=$form_data["enableWeibologin"];
		$defaultWeiboGroup=$form_data["defaultWeiboGroup"];
		$wb_akey=$form_data["wb_akey"];
		$wb_skey=$form_data["wb_skey"];
		$wb_callback_url=$form_data["wb_callback_url"];
		
		if(get_magic_quotes_gpc()){
			$enableQQlogin=stripslashes($form_data["enableQQlogin"]);
			$defaultQQGroup=stripslashes($form_data["defaultQQGroup"]);
			$qq_appid=stripslashes($form_data["qq_appid"]);
			$qq_appkey=stripslashes($form_data["qq_appkey"]);
			$qq_callback=stripslashes($form_data["qq_callback"]);
			$enableWeibologin=stripslashes($form_data["enableWeibologin"]);
			$defaultWeiboGroup=stripslashes($form_data["defaultWeiboGroup"]);
			$wb_akey=stripslashes($form_data["wb_akey"]);
			$wb_skey=stripslashes($form_data["wb_skey"]);
			$wb_callback_url=stripslashes($form_data["wb_callback_url"]);
		}
		
		$get=TleMultiFunction_Plugin::getOptions();
		
		$get["enableQQlogin"]=$enableQQlogin;
		$get["defaultQQGroup"]=$defaultQQGroup;
		$get["qq_appid"]=$qq_appid;
		$get["qq_appkey"]=$qq_appkey;
		$get["qq_callback"]=$qq_callback;
		$get["enableWeibologin"]=$enableWeibologin;
		$get["defaultWeiboGroup"]=$defaultWeiboGroup;
		$get["wb_akey"]=$wb_akey;
		$get["wb_skey"]=$wb_skey;
		$get["wb_callback_url"]=$wb_callback_url;
		
		if($get["enableQQlogin"]=="y"||$get["enableWeibologin"]=="y"){
			$config=$this->_db->getConfig();
			TleMultiFunction_Plugin::createTableOAuthLogin($this->_db);
		}
		
		TleMultiFunction_Plugin::saveOptions($get);
        
		$result = true;
		/** 提示信息 */
		$this->widget('Widget_Notice')->set(true === $result ? _t('保存成功') : _t('保存失败'), true === $result ? 'success' : 'notice');
		
        /** 转向原页 */
        $this->response->goBack();
    }
	/**
     * 社交登录回调方法
     */
    public function oAuthCallback(){
		include dirname(__FILE__).'/include/function.php';
        $this->init();
		
		$form_data = $this->request->from('action','code','state');
		
		$get=TleMultiFunction_Plugin::getOptions();
		
		switch($form_data["action"]){
			case "qq":
				if($form_data["code"]!=''&&$form_data["state"]!=''){
					if(!$form_data["state"] || $form_data["state"] != $get['qq_state']){
						die("30001");
					}
					$tokenData=getQQAccessToken($get['qq_appid'],$get['qq_appkey'],$get['qq_callback'],$form_data["code"]);
					$qqUserData=getQQOpenID($tokenData['access_token']);
					$oauthid=$qqUserData->openid;
					$userinfo=getQQUserInfo($tokenData['access_token'],$get['qq_appid'],$oauthid);
					
					$name=$userinfo['nickname'];
					$gender=$userinfo['gender'];
					$figureurl=$userinfo['figureurl_qq_2'];
					$oauthQuery= $this->_db->select()->from('table.multi_oauthlogin')->where('oauthid = ?', $oauthid);
					$oauthUser = $this->_db->fetchRow($oauthQuery);
					if($oauthUser){
						/*登录*/
						/** 如果已经登录 */
						$Widget_User=$this->widget('Widget_User');
						if ($Widget_User->hasLogin()) {
							$this->response->redirect($this->_options->index);
						}
						$query= $this->_db->select()->from('table.users')->where('uid = ?', $oauthUser['oauthuid']);
						$user = $this->_db->fetchRow($query);
						
						$authCode = function_exists('openssl_random_pseudo_bytes') ?
							bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
						$user['authCode'] = $authCode;

						Typecho_Cookie::set('__typecho_uid', $user['uid'], 0);
						Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), 0);

						/*更新最后登录时间以及验证码*/
						$this->_db->query($this->_db
						->update('table.users')
						->expression('logged', 'activated')
						->rows(array('authCode' => $authCode))
						->where('uid = ?', $user['uid']));
						
						/*压入数据*/
						$this->push($user);
						$this->_user = $user;
						$this->_hasLogin = true;
						$this->pluginHandle()->loginSucceed($this, $user["name"], '', false);
						
						$this->widget('Widget_Notice')->set(_t('用户已存在，已为您登录 '), 'success');
						/*跳转验证后地址*/
						if (NULL != $this->request->referer) {
							$this->response->redirect($this->request->referer);
						} else if (!$Widget_User->pass('contributor', true)) {
							/*不允许普通用户直接跳转后台*/
							$this->response->redirect($this->_options->profileUrl);
						} else {
							$this->response->redirect($this->_options->adminUrl);
						}
					}else{
						/*注册*/
						/** 如果已经登录 */
						$Widget_User=$this->widget('Widget_User');
						if ($Widget_User->hasLogin() || !$this->_options->allowRegister) {
							$this->response->redirect($this->_options->index);
						}
						$hasher = new PasswordHash(8, true);
						$generatedPassword = Typecho_Common::randString(7);

						$newname=$name;
						$dataStruct["group"]=$get["defaultPhoneGroup"];
						$queryUser = $this->_db->select()->from('table.users')->where('name = ?', $name); 
						$rowUser = $this->_db->fetchRow($queryUser);
						if($rowUser){
							for($i=1;;$i++){
								$newname=$name.$i;
								$queryUser = $this->_db->select()->from('table.users')->where('name = ?', $newname); 
								$rowUser = $this->_db->fetchRow($queryUser);
								if($rowUser){
									continue;
								}else{
									break;
								}
							}
						}
						$dataStruct = array(
							'name'      =>  $newname,
							'mail'      =>  $newname.'@example.com',
							'screenName'=>  $newname,
							'password'  =>  $hasher->HashPassword($generatedPassword),
							'created'   =>  time(),
							'group'     =>  $get['defaultQQGroup']
						);
						
						$insert = $this->_db->insert('table.users')->rows($dataStruct);
						$userId = $this->_db->query($insert);
						
						$dataOAuth = array(
							'oauthid'      =>  $oauthid,
							'oauthuid'      =>  $userId,
							'oauthnickname'=>  $newname,
							'oauthfigureurl'  =>  $figureurl,
							'oauthgender'   =>  $gender,
							'oauthtype'     =>  'qq'
						);
						
						$insert = $this->_db->insert('table.multi_oauthlogin')->rows($dataOAuth);
						$insertId = $this->_db->query($insert);

						$this->pluginHandle()->finishRegister($this);

						$Widget_User->login($newname, $generatedPassword);

						Typecho_Cookie::delete('__typecho_first_run');
						
						$this->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $this->screenName, $generatedPassword), 'success');
						$this->response->redirect($this->_options->adminUrl);
					}
				}
				break;
			case "weibo":
				include_once( 'include/saetv2.ex.class.php' );
				$o = new SaeTOAuthV2( $get["wb_akey"] , $get["wb_skey"] );

				if (isset($form_data["code"])) {
					$keys = array();
					$keys['code'] = $form_data["code"];
					$keys['redirect_uri'] = $get["wb_callback_url"];
					try {
						$token = $o->getAccessToken( 'code', $keys ) ;
					} catch (OAuthException $e) {
					}
				}

				if (isset($token)) {
					setcookie( 'weibojs_'.$o->client_id, http_build_query($token) );
					//获得用户信息
					$c = new SaeTClientV2( $get["wb_akey"] , $get["wb_skey"] , $token['access_token'] );
					$ms  = $c->home_timeline(); // done
					$uid_get = $c->get_uid();
					$oauthid = $uid_get['uid'];
					$user_message = $c->show_user_by_id( $oauthid);//根据ID获取用户等基本信息
					if(!isset($user_message["error"])){
						$weibo_id=$user_message["id"];
						$name=$user_message["name"];
						$gender=$user_message["gender"];
						$figureurl=$user_message["profile_image_url"];
						$weibo_url="http://weibo.com/".$user_message["profile_url"];
						$weibo_description=$user_message["description"];
					}else{
						die("授权失败");
					}
				} else {
					die("授权失败");
				}
				$oauthQuery= $this->_db->select()->from('table.multi_oauthlogin')->where('oauthid = ?', $oauthid);
				$oauthUser = $this->_db->fetchRow($oauthQuery);
				if($oauthUser){
					//微博登录
					/** 如果已经登录 */
					$Widget_User=$this->widget('Widget_User');
					if ($Widget_User->hasLogin()) {
						/** 直接返回 */
						$this->response->redirect($this->_options->index);
					}
					$query= $this->_db->select()->from('table.users')->where('uid = ?', $oauthUser['oauthuid']);
					$user = $this->_db->fetchRow($query);
					$authCode = function_exists('openssl_random_pseudo_bytes') ?
						bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
					$user['authCode'] = $authCode;

					Typecho_Cookie::set('__typecho_uid', $user['uid'], 0);
					Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), 0);

					/*更新最后登录时间以及验证码*/
					$this->_db->query($this->_db
					->update('table.users')
					->expression('logged', 'activated')
					->rows(array('authCode' => $authCode))
					->where('uid = ?', $user['uid']));
					
					/*压入数据*/
					$this->push($user);
					$this->_user = $user;
					$this->_hasLogin = true;
					$this->pluginHandle()->loginSucceed($this, $user["name"], '', false);
					
					$this->widget('Widget_Notice')->set(_t('用户已存在，已为您登录 '), 'success');
					/*跳转验证后地址*/
					if (NULL != $this->request->referer) {
						$this->response->redirect($this->request->referer);
					} else if (!$Widget_User->pass('contributor', true)) {
						/*不允许普通用户直接跳转后台*/
						$this->response->redirect($this->_options->profileUrl);
					} else {
						$this->response->redirect($this->_options->adminUrl);
					}
				}else{
					//微博注册
					/** 如果已经登录 */
					$Widget_User=$this->widget('Widget_User');
					if ($Widget_User->hasLogin() || !$this->_options->allowRegister) {
						/** 直接返回 */
						$this->response->redirect($this->_options->index);
					}
					$hasher = new PasswordHash(8, true);
					$generatedPassword = Typecho_Common::randString(7);
					
					$nickname=$name;
					$queryName= $this->_db->select()->from('table.users')->where('name = ?', $nickname)->orWhere('screenName = ?', $nickname);
					$rowName = $this->_db->fetchRow($queryName);
					if($rowName){
						for($i=1;;$i++){
							$nickname=$name.$i;
							$queryName= $this->_db->select()->from('table.users')->where('name = ?', $nickname)->orWhere('screenName = ?', $nickname);
							$rowName = $this->_db->fetchRow($queryName);
							if(count($rowName)==0){
								break;
							}
						}
					}

					$mail=$nickname.'@example.com';
					$dataStruct = array(
						'name'      =>  $nickname,
						'mail'      =>  $mail,
						'screenName'=>  $nickname,
						'password'  =>  $hasher->HashPassword($generatedPassword),
						'created'   =>  time(),
						'group'     =>  $get['defaultWeiboGroup']
					);
					
					$insert = $this->_db->insert('table.users')->rows($dataStruct);
					$userId = $this->_db->query($insert);
					
					$dataOAuth = array(
						'oauthid'      =>  $oauthid,
						'oauthuid'      =>  $userId,
						'oauthnickname'=>  $nickname,
						'oauthfigureurl'  =>  $figureurl,
						'oauthgender'   =>  $gender,
						'oauthtype'     =>  'weibo'
					);
					
					$insert = $this->_db->insert('table.multi_oauthlogin')->rows($dataOAuth);
					$insertId = $this->_db->query($insert);

					$this->pluginHandle()->finishRegister($this);

					$Widget_User->login($nickname, $generatedPassword);

					Typecho_Cookie::delete('__typecho_first_run');
					
					$this->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $this->screenName, $generatedPassword), 'success');
					$this->response->redirect($this->_options->adminUrl);
				}
				break;
		}
    }
	/**
     * 删除用户方法
     */
    public function delUser(){
        $this->init();
		
		$get=TleMultiFunction_Plugin::getOptions();
		
		$id = isset($_GET['id']) ? addslashes(trim($_GET['id'])) : 0;
		if($id){
			$delete = $this->_db->delete('table.multi_oauthlogin')->where('oauthuid = ?', $id);
			$deletedRows = $this->_db->query($delete);
			$delete = $this->_db->delete('table.users')->where('uid = ?', $id);
			$deletedRows = $this->_db->query($delete);
		}
		
		$result = true;
		/** 提示信息 */
		$this->widget('Widget_Notice')->set(true === $result ? _t('删除成功') : _t('删除失败'), true === $result ? 'success' : 'notice');
		
        /** 转向原页 */
        $this->response->goBack();
    }
	/**
     * 验证码方法
     */
    public function verifyCode(){
        if (Typecho_Widget::widget('TleMultiFunction_Console')->verifyCodeForm()->validate()) {
            $this->response->goBack();
        }

        $this->init();
		
		$form_data = $this->request->from('GT_CAPTCHA_ID','GT_PRIVATE_KEY');
		$enableGeetest = isset($_POST['enableGeetest']) ? $_POST['enableGeetest'] : array();
		
		$GT_CAPTCHA_ID=$form_data["GT_CAPTCHA_ID"];
		$GT_PRIVATE_KEY=$form_data["GT_PRIVATE_KEY"];
		if(get_magic_quotes_gpc()){
			$GT_CAPTCHA_ID=stripslashes($form_data["GT_CAPTCHA_ID"]);
			$GT_PRIVATE_KEY=stripslashes($form_data["GT_PRIVATE_KEY"]);
		}
		
		$get=TleMultiFunction_Plugin::getOptions();
		
		$get["enableGeetest"]=serialize($enableGeetest);
		$get["GT_CAPTCHA_ID"]=$GT_CAPTCHA_ID;
		$get["GT_PRIVATE_KEY"]=$GT_PRIVATE_KEY;
		
		TleMultiFunction_Plugin::saveOptions($get);
        
		$result = true;
		/** 提示信息 */
		$this->widget('Widget_Notice')->set(true === $result ? _t('保存成功') : _t('保存失败'), true === $result ? 'success' : 'notice');
		
        /** 转向原页 */
        $this->response->goBack();
    }
}