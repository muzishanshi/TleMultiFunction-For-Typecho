<?php
/**
 * Typecho多功能插件集成多项功能：百度(熊掌号)链接提交、手机登陆、邮箱登陆、QQ登陆、微博登陆、geetest验证码、忘记密码、网址缩短、论坛、多平台用户管理等。<div class="TleMultiSet"><br /><a href="javascript:;" title="插件因兴趣于闲暇时间所写，故会有代码不规范、不专业和bug的情况，但完美主义促使代码还说得过去，如有bug或使用问题进行反馈即可。">鼠标轻触查看备注</a>&nbsp;<a href="http://club.tongleer.com" target="_blank">论坛</a>&nbsp;<a href="https://www.tongleer.com/api/web/pay.png" target="_blank">打赏</a>&nbsp;<a href="http://mail.qq.com/cgi-bin/qm_share?t=qm_mailme&email=diamond0422@qq.com" target="_blank">反馈</a></div><style>.TleMultiSet a{background: #4DABFF;padding: 5px;color: #fff;}</style>
 * @package TleMultiFunction For Typecho
 * @author 二呆
 * @version 1.0.17<br /><span id="TleMultiUpdateInfo"></span><script>TleMultiXmlHttp=new XMLHttpRequest();TleMultiXmlHttp.open("GET","https://www.tongleer.com/api/interface/TleMultiFunction.php?action=update&version=17",true);TleMultiXmlHttp.send(null);TleMultiXmlHttp.onreadystatechange=function () {if (TleMultiXmlHttp.readyState ==4 && TleMultiXmlHttp.status ==200){document.getElementById("TleMultiUpdateInfo").innerHTML=TleMultiXmlHttp.responseText;}}</script>
 * @link http://www.tongleer.com/
 * @date 2019-08-25
 */
class TleMultiFunction_Plugin implements Typecho_Plugin_Interface
{
	/** @var string 提交路由前缀 */
    public static $action = 'tleMultiFunction-code';
    /** @var string 控制菜单链接 */
    public static $panel  = 'TleMultiFunction/page/console.php';
    // 激活插件
    public static function activate(){
		TleMultiFunction_Plugin::Judge_database();
        Typecho_Plugin::factory('admin/footer.php')->end = array('TleMultiFunction_Plugin', 'addRegInput');
        Typecho_Plugin::factory('Widget_Register')->register = array('TleMultiFunction_Plugin', 'regSubmit');
		Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array(__CLASS__, 'baiduAutoSubmit');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array(__CLASS__, 'baiduAutoSubmit');
		Helper::addRoute('passport_forgot', '/passport/forgot', 'TleMultiFunction_Widget', 'doForgot');
        Helper::addRoute('passport_reset', '/passport/reset', 'TleMultiFunction_Widget', 'doReset');
		$versions=explode("/",Typecho_Widget::widget('Widget_Options')->Version);
		if($versions[1]>="19.10.15"){
			self::$panel='TleMultiFunction/page/console2.php';
		}
		if($versions[1]>="19.10.20"){
			self::$panel='TleMultiFunction/page/console3.php';
		}
		Helper::addAction(self::$action, 'TleMultiFunction_Action');
        Helper::addPanel(1, self::$panel, '多功能设置', '多功能控制台', 'administrator');
		if(!is_dir(dirname(__FILE__)."/config")){mkdir (dirname(__FILE__)."/config", 0777, true );}
        return _t('插件已经激活，需先配置信息！');
    }

    // 禁用插件
    public static function deactivate(){
		//清空用户登录信息
		if(file_exists(dirname(__FILE__).'/config/setuser_config.php')&&self::new_is_writeable(dirname(__FILE__).'/config/')){
			file_put_contents(dirname(__FILE__).'/config/setuser_config.php','<?php die; ?>'.serialize(array(
				'username'=>'',
				'password'=>'',
				'access_token'=>''
			)));
		}
		//恢复原注册页面
		$versions=explode("/",Typecho_Widget::widget('Widget_Options')->Version);
		if($versions[1]<"19.10.15"){
			$registerFileName="register.php";
		}
		if($versions[1]>="19.10.15"){
			$registerFileName="register2.php";
		}
		if($versions[1]>="19.10.20"){
			$registerFileName="register3.php";
		}
		if(copy(dirname(__FILE__).'/page/'.$registerFileName,dirname(__FILE__).'/../../../'.substr(__TYPECHO_ADMIN_DIR__,1,count(__TYPECHO_ADMIN_DIR__)-2).'/register.php')){
		}
		//删除页面模板
		$db = Typecho_Db::get();
		$queryTheme= $db->select('value')->from('table.options')->where('name = ?', 'theme'); 
		$rowTheme = $db->fetchRow($queryTheme);
		@unlink(dirname(__FILE__).'/../../themes/'.$rowTheme['value'].'/page_multi_baidusubmit.php');
		@unlink(dirname(__FILE__).'/../../themes/'.$rowTheme['value'].'/page_multi_dwz.php');
		@unlink(dirname(__FILE__).'/../../themes/'.$rowTheme['value'].'/page_multi_bbs.php');
		@unlink(dirname(__FILE__).'/../../themes/'.$rowTheme['value'].'/page_multi_oauthlogin.php');
		@unlink(dirname(__FILE__).'/../../themes/'.$rowTheme['value'].'/page_multi_phonelogin.php');
		$versions=explode("/",Typecho_Widget::widget('Widget_Options')->Version);
		if($versions[1]>="19.10.15"){
			self::$panel='TleMultiFunction/page/console2.php';
		}
		if($versions[1]>="19.10.20"){
			self::$panel='TleMultiFunction/page/console3.php';
		}
		Helper::removeAction(self::$action);
        Helper::removePanel(1, self::$panel);
		Helper::removeRoute('passport_reset');
        Helper::removeRoute('passport_forgot');
        return _t('插件已被禁用');
    }

    // 插件配置面板
    public static function config(Typecho_Widget_Helper_Form $form){
		$options = Typecho_Widget::widget('Widget_Options');
		$div=new Typecho_Widget_Helper_Layout();
		$div->html('<b><font color="green">此处为旧版本设置页面，新版本请前往<font color="red">控制台->多功能设置</font>进行体验。</font></b>');
		$div->render();
		//登录验证
		$user = new Typecho_Widget_Helper_Form_Element_Text('user', null, '', _t('用户名：'));
        $form->addInput($user->addRule('required', _t('用户名不能为空！')));

        $pass = new Typecho_Widget_Helper_Form_Element_Password('pass', null, '', _t('密码：'));
        $form->addInput($pass->addRule('required', _t('密码不能为空！')));
		
		$token = new Typecho_Widget_Helper_Form_Element_Text('token', null, '', _t('Token：'), _t("自行到<a href='https://www.tongleer.com/reg' target='_blank'>同乐儿</a>注册账号后获取"));
        $form->addInput($token->addRule('required', _t('token不能为空！')));
		
		$db = Typecho_Db::get();
		$prefix = $db->getPrefix();
		
		//百度链接提交模块
        $baidu_submit = new Typecho_Widget_Helper_Form_Element_Radio('baidu_submit', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), 'n', _t('百度链接提交'), _t('1、启用后可前往<a href="'.$options->siteUrl.'admin/manage-pages.php">独立页面</a>进一步配置百度链接提交的相关参数，为您做到心中有数，启用后会创建'.$prefix.'multi_baidusubmit数据表、page_multi_baidusubmit.php主题文件、百度链接提交页面3项，以提供多功能服务，不会添加任何无用项目，谢谢支持，额外加入了百度熊掌号手动提交。<br />2、（可选：链接检测请把<font color="blue">&lt;?php if($this->user->pass("administrator",true)){echo TleMultiFunction_Plugin::baiduSubmitCheck($this,"");}?></font>代码放于主题目录下post.php文件的合适位置。）'));
        $form->addInput($baidu_submit->addRule('enum', _t(''), array('y', 'n')));
		
		//短网址模块
        $dwz = new Typecho_Widget_Helper_Form_Element_Radio('dwz', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), 'n', _t('短网址缩短'), _t("启用后可前往<a href='".$options->siteUrl."admin/manage-pages.php'>独立页面</a>进一步配置短网址缩短的相关参数，为您做到心中有数，启用后会创建".$prefix."multi_dwz数据表、page_multi_dwz.php主题文件、短网址页面3项，以提供多功能服务，不会添加任何无用项目，谢谢支持，<font color='blue'>短网址功能暂时需要每月手动更新token</font>，带来的不便敬请谅解。"));
        $form->addInput($dwz->addRule('enum', _t(''), array('y', 'n')));
		
		//论坛模块
        $bbs = new Typecho_Widget_Helper_Form_Element_Radio('bbs', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), 'n', _t('论坛'), _t("启用后可前往<a href='".$options->siteUrl."admin/manage-pages.php'>独立页面</a>进一步配置短网址缩短的相关参数，为您做到心中有数，启用后会创建page_multi_bbs.php主题文件、论坛页面2项，以提供多功能服务，不会添加任何无用项目，谢谢支持。"));
        $form->addInput($bbs->addRule('enum', _t(''), array('y', 'n')));
		
		//第三方登录模块
		$oauthlogin = new Typecho_Widget_Helper_Form_Element_Radio('oauthlogin', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), 'n', _t('第三方登录'), _t("启用后可前往<a href='".$options->siteUrl."admin/manage-pages.php'>独立页面</a>进一步配置短网址缩短的相关参数，为您做到心中有数，启用后会创建".$prefix."multi_oauthlogin数据表、page_multi_oauthlogin.php主题文件、第三方登录页面3项，以提供多功能服务，不会添加任何无用项目，谢谢支持。"));
        $form->addInput($oauthlogin->addRule('enum', _t(''), array('y', 'n')));
		
		//手机号登录模块
		$phonelogin = new Typecho_Widget_Helper_Form_Element_Radio('phonelogin', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), 'n', _t('手机号登录'), _t("启用后可前往<a href='".$options->siteUrl."admin/manage-pages.php'>独立页面</a>进一步配置手机号登录的相关参数，为您做到心中有数，启用后会创建page_multi_phonelogin.php主题文件、手机号登录页面、用户表".$prefix."users的phone字段3项，以提供多功能服务，不会添加任何无用项目，谢谢支持。"));
        $form->addInput($phonelogin->addRule('enum', _t(''), array('y', 'n')));
	
		$user = @isset($_POST['user']) ? addslashes(trim($_POST['user'])) : '';
		$pass = @isset($_POST['pass']) ? addslashes(trim($_POST['pass'])) : '';
		$token = @isset($_POST['token']) ? addslashes(trim($_POST['token'])) : '';
		if($user!=''&&$pass!=''&&$token!=''){
			$code=self::checkUserLogin($user,$pass,$token);
			if($code==100){
				if(get_magic_quotes_gpc()){
					$user=stripslashes($user);
					$pass=stripslashes($pass);
					$token=stripslashes($token);
				}
				file_put_contents(dirname(__FILE__).'/config/setuser_config.php','<?php die; ?>'.serialize(array(
					'username'=>$user,
					'password'=>$pass,
					'access_token'=>$token
				)));
				//百度链接提交模块
				$baidu_submit = @isset($_POST['baidu_submit']) ? addslashes(trim($_POST['baidu_submit'])) : '';
				self::moduleBaiduSubmit($db,$baidu_submit);
				//短网址模块
				$dwz = @isset($_POST['dwz']) ? addslashes(trim($_POST['dwz'])) : '';
				self::moduleDwz($db,$dwz);
				//论坛模块
				$bbs = @isset($_POST['bbs']) ? addslashes(trim($_POST['bbs'])) : '';
				self::moduleBBS($db,$bbs);
				//第三方登录模块
				$oauthlogin = @isset($_POST['oauthlogin']) ? addslashes(trim($_POST['oauthlogin'])) : '';
				self::moduleOAuthLogin($db,$oauthlogin);
				//手机号登录模块
				$phonelogin = @isset($_POST['phonelogin']) ? addslashes(trim($_POST['phonelogin'])) : '';
				self::modulePhoneLogin($db,$phonelogin);
			}else{
				die('登录失败');
			}
		}
    }
	
	// 个人用户配置面板
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    // 获得插件配置信息
    public static function getConfig(){
        return Typecho_Widget::widget('Widget_Options')->plugin('TleMultiFunction');
    }
	
	private static function Judge_database(){
        $db= Typecho_Db::get();
        $getAdapterName = $db->getAdapterName();
        if(preg_match('/^M|m?ysql$/',$getAdapterName)){
            return true;
        }else{
            throw new Typecho_Plugin_Exception(_t('对不起，使用了不支持的数据库，无法使用此功能，仅支持mysql数据库。'));
        }
    }
	
	/*手机号登录方法*/
	public static function modulePhoneLogin($db,$phonelogin){
		switch($phonelogin){
			case 'y':
				$config=$db->getConfig();
				self::alterColumn($db,$config[0]->database,$db->getPrefix().'users','phone','varchar(16) DEFAULT NULL');
				//判断目录权限，并将插件文件写入主题目录
				self::funWriteThemePage($db,'page_multi_phonelogin.php');
				//如果数据表没有添加页面就插入
				self::funWriteDataPage($db,'手机号登录','multi_phonelogin','page_multi_phonelogin.php');
				break;
		}
	}
	
	/*第三方登录方法*/
	public static function moduleOAuthLogin($db,$oauthlogin){
		switch($oauthlogin){
			case 'y':
				//创建第三方登录所用数据表
				self::createTableOAuthLogin($db);
				//判断目录权限，并将插件文件写入主题目录
				self::funWriteThemePage($db,'page_multi_oauthlogin.php');
				//如果数据表没有添加页面就插入
				self::funWriteDataPage($db,'第三方登录','multi_oauthlogin','page_multi_oauthlogin.php');
				break;
		}
	}
	
	/*论坛方法*/
	public static function moduleBBS($db,$bbs){
		switch($bbs){
			case 'y':
				//判断目录权限，并将插件文件写入主题目录
				self::funWriteThemePage($db,'page_multi_bbs.php');
				//如果数据表没有添加页面就插入
				self::funWriteDataPage($db,'论坛','multi_bbs','page_multi_bbs.php');
				break;
		}
	}
	
	/*短网址方法*/
	public static function moduleDwz($db,$dwz){
		switch($dwz){
			case 'y':
				//创建短网址所用数据表
				self::createTableDwz($db);
				//判断目录权限，并将插件文件写入主题目录
				self::funWriteThemePage($db,'page_multi_dwz.php');
				//如果数据表没有添加页面就插入
				self::funWriteDataPage($db,'短网址缩短','multi_dwz','page_multi_dwz.php');
				//重写404页面以达到短址重定向目的
				self::funWriteThemePage($db,'404.php');
				break;
		}
	}
	
	/*百度链接提交方法*/
	public static function moduleBaiduSubmit($db,$baidu_submit){
		switch($baidu_submit){
			case 'y':
				//创建百度链接提交所用数据表
				self::createTableBaiduSubmit($db);
				//判断目录权限，并将插件文件写入主题目录
				self::funWriteThemePage($db,'page_multi_baidusubmit.php');
				//如果数据表没有添加页面就插入
				self::funWriteDataPage($db,'百度链接提交','multi_baidusubmit','page_multi_baidusubmit.php');
				break;
		}
	}
	
	/*创建第三方登录缩短所用数据表*/
	public static function createTableOAuthLogin($db){
		$prefix = $db->getPrefix();
		//$db->query('DROP TABLE IF EXISTS '.$prefix.'multi_baidusubmit');
		$db->query('CREATE TABLE IF NOT EXISTS '.$prefix.'multi_oauthlogin(
			`oauthid` varchar(64) COLLATE utf8_general_ci NOT NULL,
			`oauthuid` bigint(20) COLLATE utf8_general_ci NOT NULL,
		    `oauthnickname` varchar(64) COLLATE utf8_general_ci DEFAULT NULL,
		    `oauthfigureurl` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
		    `oauthgender` varchar(8) COLLATE utf8_general_ci DEFAULT NULL,
		    `oauthtype` enum("qq","weibo","weixin") COLLATE utf8_general_ci DEFAULT NULL,
		    PRIMARY KEY (`oauthid`)
		) DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci');
	}
	
	/*创建短网址缩短所用数据表*/
	public static function createTableDwz($db){
		$prefix = $db->getPrefix();
		//$db->query('DROP TABLE IF EXISTS '.$prefix.'multi_baidusubmit');
		$db->query('CREATE TABLE IF NOT EXISTS '.$prefix.'multi_dwz(
			`shortid` bigint(20) NOT NULL AUTO_INCREMENT,
			`longurl` varchar(255) DEFAULT NULL,
			`shorturl` varchar(255) DEFAULT NULL,
			`isred` enum("y","n") DEFAULT "n",
			`instime` datetime DEFAULT NULL COMMENT "插入时间",
			PRIMARY KEY (`shortid`)
		) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8');
	}
	
	/*创建百度链接提交所用数据表*/
	public static function createTableBaiduSubmit($db){
		$prefix = $db->getPrefix();
		//$db->query('DROP TABLE IF EXISTS '.$prefix.'multi_baidusubmit');
		$db->query('CREATE TABLE IF NOT EXISTS '.$prefix.'multi_baidusubmit(
			`bsid` int(11) NOT NULL AUTO_INCREMENT,
			`bscid` int(11) NOT NULL,
			`url` varchar(200) COLLATE utf8_general_ci DEFAULT NULL,
			`linkstatus` varchar(3) DEFAULT NULL,
			`rescstatus` varchar(3) DEFAULT NULL,
			`instime` datetime DEFAULT NULL,
			`error` varchar(255) COLLATE utf8_general_ci DEFAULT NULL,
			PRIMARY KEY (`bsid`)
		) AUTO_INCREMENT=1 DEFAULT CHARSET=utf8');
	}
	
	/*修改数据表字段*/
	public static function alterColumn($db,$dbname,$table,$column,$define){
		$prefix = $db->getPrefix();
		$query= "select * from information_schema.columns WHERE TABLE_SCHEMA='".$dbname."' and table_name = '".$table."' AND column_name = '".$column."'";
		$row = $db->fetchRow($query);
		if(count($row)==0){
			$db->query('ALTER TABLE `'.$table.'` ADD COLUMN `'.$column.'` '.$define.';');
		}
	}
	
	/*公共方法：将页面写入数据库*/
	public static function funWriteDataPage($db,$title,$slug,$template){
		$query= $db->select('slug')->from('table.contents')->where('template = ?', $template); 
		$row = $db->fetchRow($query);
		if(count($row)==0){
			$contents = array(
				'title'      =>  $title,
				'slug'      =>  $slug,
				'created'   =>  time(),
				'text'=>  '<!--markdown-->',
				'password'  =>  '',
				'authorId'     =>  Typecho_Cookie::get('__typecho_uid'),
				'template'     =>  $template,
				'type'     =>  'page',
				'status'     =>  'hidden',
			);
			$insert = $db->insert('table.contents')->rows($contents);
			$insertId = $db->query($insert);
			$slug=$contents['slug'];
		}else{
			$slug=$row['slug'];
		}
	}
	/*公共方法：将页面写入后台目录*/
	public static function funWriteAdminPage($db,$filename,$isindex){
		/*将跳转新注册页面的链接写入原register.php*/
		$query= $db->select('slug')->from('table.contents')->where('template = ?', 'page_multi_phonelogin.php'); 
		$row = $db->fetchRow($query);
		if(!self::new_is_writeable(dirname(__FILE__).'/../../../'.substr(__TYPECHO_ADMIN_DIR__,1,count(__TYPECHO_ADMIN_DIR__)-2).'/'.$filename)){
			die('后台目录不可写，请更改目录权限。');
		}
		$querySiteUrl= $db->select('value')->from('table.options')->where('name = ?', 'siteUrl'); 
		$rowSiteUrl = $db->fetchRow($querySiteUrl);
		if($isindex=='y'){
			$siteUrl=$rowSiteUrl['value'].'/index.php/'.$row['slug'].'.html';
		}else{
			$siteUrl=$rowSiteUrl['value'].'/'.$row['slug'].'.html';
		}
		$registerphp='
			<?php
			include "common.php";
			if ($user->hasLogin() || !$options->allowRegister) {
				$response->redirect($options->siteUrl);
			}else{
				header("Location: '.$siteUrl.'");
			}
			?>
		';
		$regphp = fopen(dirname(__FILE__).'/../../../'.substr(__TYPECHO_ADMIN_DIR__,1,count(__TYPECHO_ADMIN_DIR__)-2).'/'.$filename, "w") or die("不能写入".$filename."文件");
		fwrite($regphp, $registerphp);
		fclose($regphp);
	}
	/*公共方法：将页面写入主题目录*/
	public static function funWriteThemePage($db,$filename){
		$queryTheme= $db->select('value')->from('table.options')->where('name = ?', 'theme'); 
		$rowTheme = $db->fetchRow($queryTheme);
		if(!self::new_is_writeable(dirname(__FILE__).'/../../themes/'.$rowTheme['value'])){
			Typecho_Widget::widget('Widget_Notice')->set(_t('主题目录不可写，请更改目录权限。'.__TYPECHO_THEME_DIR__.'/'.$rowTheme['value']), 'success');
		}
		if($filename=='404.php'||!file_exists(dirname(__FILE__).'/../../themes/'.$rowTheme['value']."/".$filename)){
			$regfile = fopen(dirname(__FILE__)."/page/".$filename, "r") or die("不能读取".$filename."文件");
			$regtext=fread($regfile,filesize(dirname(__FILE__)."/page/".$filename));
			fclose($regfile);
			$regpage = fopen(dirname(__FILE__).'/../../themes/'.$rowTheme['value']."/".$filename, "w") or die("不能写入".$filename."文件");
			fwrite($regpage, $regtext);
			fclose($regpage);
		}
	}
	/**
	 * 判断 文件/目录 是否可写（取代系统自带的 is_writeable 函数）
	 * @param string $file 文件/目录
	 * @return boolean
	 */
	public static function new_is_writeable($file) {
		if (is_dir($file)){
			$dir = $file;
			if ($fp = @fopen("$dir/test.txt", 'w')) {
				@fclose($fp);
				@unlink("$dir/test.txt");
				$writeable = 1;
			} else {
				$writeable = 0;
			}
		} else {
			if ($fp = @fopen($file, 'a+')) {
				@fclose($fp);
				$writeable = 1;
			} else {
				$writeable = 0;
			}
		}
	 
		return $writeable;
	}
	/*登录验证*/
	public static function checkUserLogin($user,$pass,$token){
		$data=array(
			"user"=>$user,
			"pass"=>$pass,
			"token"=>$token
		);
		$url = 'http://api.tongleer.com/open/login.php';
		$client = Typecho_Http_Client::get();
		if ($client) {
			//$data=json_encode($data);
			$str = "";
			foreach ( $data as $key => $value ) { 
				$str.= "$key=" . urlencode( $value ). "&" ;
			}
			$data = substr($str,0,-1);
			$client->setData($data)
				//->setHeader('Content-Type','application/json')
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
	public static function Tips_cxa($error){
        $notice = is_array($error) ? array_values($error) : array($error);
        Typecho_Cookie::set('__typecho_notice', Json::encode($notice));
        Typecho_Cookie::set('__typecho_notice_type', 'notice');
        Typecho_Cookie::set('__typecho_remember_name', $_POST['name']);
        Typecho_Cookie::set('__typecho_remember_mail', $_POST['mail']);
        InvitationCode_Plugin::goBack_c();
    }
     /**
     * 返回来路
     *
     * @access public
     * @param string $suffix 附加地址
     * @param string $default 默认来路
     */
    public static function goBack_c($suffix = NULL, $default = NULL)
    {
        //获取来源
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';

        //判断来源
        if (!empty($referer)) {
            // ~ fix Issue 38
            if (!empty($suffix)) {
                $parts = parse_url($referer);
                $myParts = parse_url($suffix);

                if (isset($myParts['fragment'])) {
                    $parts['fragment'] = $myParts['fragment'];
                }

                if (isset($myParts['query'])) {
                    $args = array();
                    if (isset($parts['query'])) {
                        parse_str($parts['query'], $args);
                    }

                    parse_str($myParts['query'], $currentArgs);
                    $args = array_merge($args, $currentArgs);
                    $parts['query'] = http_build_query($args);
                }

                $referer = Typecho_Common::buildUrl($parts);
            }

            InvitationCode_Plugin::redirect_c($referer, false);
        } else if (!empty($default)) {
            InvitationCode_Plugin::redirect_c($default);
        }
        exit;
    }
  
      /**
     * 重定向函数
     *
     * @access public
     * @param string $location 重定向路径
     * @param boolean $isPermanently 是否为永久重定向
     * @return void
     */
    public static function redirect_c($location, $isPermanently = false)
    {
        /** Typecho_Common */
        $location = Typecho_Common::safeUrl($location);

        if ($isPermanently) {
            header('Location: ' . $location, false, 301);
            exit;
        } else {
            header('Location: ' . $location, false, 302);
            exit;
        }
    }
	public static function getOptions(){
		$db = Typecho_Db::get();
		$query= $db->select('value')->from('table.options')->where('name = ?', 'plugin-custom:TleMultiFunction'); 
		$row = $db->fetchRow($query);
		$themeOption=array();
		if($row){
			$themeOption = unserialize(stripslashes($row["value"]));
		}
		unset($db);
		return $themeOption;
	}
	public static function saveOptions($data){
		$db = Typecho_Db::get();
		$query= $db->select('value')->from('table.options')->where('name = ?', 'plugin-custom:TleMultiFunction'); 
		$row = $db->fetchRow($query);
		$themeOption=array();
		if($row){
			$update = $db->update('table.options')->rows(array('value'=>addslashes(serialize($data))))->where('name=?',"plugin-custom:TleMultiFunction");
			$updateRows= $db->query($update);
		}else{
			$insert = $db->insert('table.options')->rows(array('value' => addslashes(serialize($data)), 'name' => 'plugin-custom:TleMultiFunction'));
			$insertId = $db->query($insert);
		}
		unset($db);
	}
	
	//自动百度提交新文章或页面
	public static function baiduAutoSubmit($contents, $widget){
		date_default_timezone_set('Asia/Shanghai');
		$db = Typecho_Db::get();
		//判断是否开启插件
		$queryPlugins= $db->select('value')->from('table.options')->where('name = ?', 'plugins'); 
		$rowPlugins = $db->fetchRow($queryPlugins);
		$plugins=@unserialize($rowPlugins['value']);
		if(!isset($plugins['activated']['TleMultiFunction'])){
			return false;
		}
		//判断是否开启百度链接提交插件
		$queryTleMultiFunction= $db->select('value')->from('table.options')->where('name = ?', 'plugin:TleMultiFunction'); 
		$rowTleMultiFunction = $db->fetchRow($queryTleMultiFunction);
		$tleMultiFunction=@unserialize($rowTleMultiFunction['value']);
		if($tleMultiFunction['baidu_submit']=='n'){
			return false;
		}
		//判断是否设置百度参数
		$setbaidusubmit=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/config/setbaidusubmit.php'),'<?php die; ?>'));
		if(!$setbaidusubmit||$setbaidusubmit['url']==''||$setbaidusubmit['linktoken']==''){
			return false;
		}
		//判断是否提交过百度
		$query= $db->select()->from('table.multi_baidusubmit')->where('url = ?', $widget->permalink); 
		$row = $db->fetchRow($query);
		if(count($row)==0){
			//提交百度
			$urls = array( $widget->permalink );
			$api = sprintf('http://data.zz.baidu.com/urls?site=%s&token=%s', $setbaidusubmit['url'], $setbaidusubmit['linktoken']);
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
				//记录到本地数据库
				$query= $db->select()->from('table.multi_baidusubmit')->where('bscid = ?', $widget->cid); 
				$row = $db->fetchRow($query);
				if(count($row)==0){
					$result = array(
						'bscid'   =>  $widget->cid,
						'url'   =>  $widget->permalink,
						'instime'     =>  date('Y-m-d H:i:s',time()),
						'error'     =>  $error,
						'linkstatus'=>$status
					);
					$insert = $db->insert('table.multi_baidusubmit')->rows($result);
					$insertId = $db->query($insert);
				}else{
					$update = $db->update('table.multi_baidusubmit')->rows(array(
						'url'   =>  $widget->permalink,
						'instime'     =>  date('Y-m-d H:i:s',time()),
						'error'     =>  $error,
						'linkstatus'=>$status
					))->where('bscid=?',$widget->cid);
					$updateRows= $db->query($update);
				}
				return true;
			}
		}
        return false;
    }
	
	//百度提交检查判断当前文章是否被百度收录，若没有被收录则可点击提交至百度，加速收录！
	public static function baiduSubmitCheck($obj,$content){
		$db = Typecho_Db::get();
		//判断是否开启插件
		$queryPlugins= $db->select('value')->from('table.options')->where('name = ?', 'plugins'); 
		$rowPlugins = $db->fetchRow($queryPlugins);
		$plugins=@unserialize($rowPlugins['value']);
		if(!isset($plugins['activated']['TleMultiFunction'])){
			return null;
		}
		//判断是否开启百度链接提交插件
		$queryTleMultiFunction= $db->select('value')->from('table.options')->where('name = ?', 'plugin:TleMultiFunction'); 
		$rowTleMultiFunction = $db->fetchRow($queryTleMultiFunction);
		$tleMultiFunction=@unserialize($rowTleMultiFunction['value']);
		if($tleMultiFunction['baidu_submit']=='n'){
			return null;
		}
		//判断是否收录
		$url='http://www.baidu.com/s?wd='.$obj->permalink;
		$curl=curl_init();
		curl_setopt($curl,CURLOPT_URL,$url);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,1);
		$rs=curl_exec($curl);
		curl_close($curl);
		if(!strpos($rs,'没有找到')){
			$content="<p align=right><a style=color:red target=_blank href=https://www.baidu.com/s?wd=".$obj->permalink.">百度已收录(仅管理员可见)</a></p>".$content; 
		}else{
			$content="<p align=right><b><a style=color:red target=_blank href=http://zhanzhang.baidu.com/sitesubmit/index?sitename=".$obj->permalink.">百度未收录!点击此处提交</a></b>(仅管理员可见)</p>".$content;
		}
		return $content;
	}

	/**
     * 注册提交方法
     * @access public
     * @return void
     */
    public static function regSubmit($dataStruct){
		session_start();
		$db = Typecho_Db::get();
        $options = Typecho_Widget::widget('Widget_Options');
        $option = $options->plugin('TleMultiFunction');
		$get = self::getOptions();
		
		$activates = array_keys(Typecho_Plugin::export()['activated']);
		if(!in_array('TleMultiFunction', $activates)){
			return $dataStruct;
		}
		if(!$options->allowRegister){
			$error=['error'=>'尚未开启用户注册！'];
			TleMultiFunction_Plugin::Tips_cxa($error);
		}
		
		$name = isset($_POST['name']) ? addslashes($_POST['name']) : '';
		$smscode = isset($_POST['smscode']) ? addslashes($_POST['smscode']) : '';
		$nickname = isset($_POST['nickname']) ? addslashes($_POST['nickname']) : '';
		$weburl = isset($_POST['weburl']) ? addslashes($_POST['weburl']) : '';
		
		if($weburl){
			if(!preg_match("/^http(s)?:\\/\\/.+/",$weburl)){
				$error=['error'=>'个人主页地址请用http(s)://开头！'];
				TleMultiFunction_Plugin::Tips_cxa($error);
			}
			$dataStruct["url"]=$weburl;
		}
		
		$phoneRegExp=preg_match("/^1[345678]{1}\d{9}$/",$name);
		$mailRegExp=preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$name);
		
		if(!$phoneRegExp&&!$mailRegExp){
			$error=['error'=>'用户名称仅支持手机号和邮箱格式！'];
			TleMultiFunction_Plugin::Tips_cxa($error);
		}
        if($phoneRegExp){
			if(isset($get["enablePhonelogin"])&&$get["enablePhonelogin"]=="y"){
				if (empty($smscode)) {
					$error=['error'=>'未填填写短信验证码！'];
					TleMultiFunction_Plugin::Tips_cxa($error);
				}
				if(!isset($_SESSION['phonecode'])||strcasecmp($_SESSION['phonecode'],$smscode)!=0){
					$error=['error'=>'短信验证码错误！'];
					TleMultiFunction_Plugin::Tips_cxa($error);
				}
				if (isset($_SESSION["newphone"])&&$name!=$_SESSION["newphone"]) {
					$error=['error'=>'填写手机号和发送验证码的手机号不一致！'];
					TleMultiFunction_Plugin::Tips_cxa($error);
				}
				$queryUser = $db->select()->from('table.users')->where('phone = ?', $name); 
				$rowUser = $db->fetchRow($queryUser);
				if($rowUser){
					$error=['error'=>'手机已存在！'];
					TleMultiFunction_Plugin::Tips_cxa($error);
				}else{
					$dataStruct["phone"]=$name;
				}
				if($nickname){
					$dataStruct["screenName"]=$nickname;
				}else{
					$dataStruct["screenName"]=substr_replace($name,'****',3,4);
				}
				$dataStruct["group"]=$get["defaultPhoneGroup"];
				$queryUser = $db->select()->from('table.users')->where('mail = ?', $dataStruct["mail"]); 
				$rowUser = $db->fetchRow($queryUser);
				if($rowUser){
					for($i=1;;$i++){
						$dataStruct["mail"]=time().'@yourdomain.com';
						$queryUser = $db->select()->from('table.users')->where('mail = ?', $dataStruct["mail"]); 
						$rowUser = $db->fetchRow($queryUser);
						if($rowUser){
							continue;
						}else{
							break;
						}
					}
				}
			}else{
				$error=['error'=>'尚未开启手机号注册！'];
				TleMultiFunction_Plugin::Tips_cxa($error);
			}
			$_SESSION['phonecode'] = mt_rand(100000,999999);
        }
		if($mailRegExp){
			if(isset($get["enableMaillogin"])&&$get["enableMaillogin"]=="y"){
				if (empty($smscode)) {
					$error=['error'=>'未填填写邮箱验证码！'];
					TleMultiFunction_Plugin::Tips_cxa($error);
				}
				if(!isset($_SESSION['mailcode'])||strcasecmp($_SESSION['mailcode'],$smscode)!=0){
					$error=['error'=>'邮箱验证码错误！'];
					TleMultiFunction_Plugin::Tips_cxa($error);
				}
				if (isset($_SESSION["newmail"])&&$name!=$_SESSION["newmail"]) {
					$error=['error'=>'填写邮箱和发送验证码的邮箱不一致！'];
					TleMultiFunction_Plugin::Tips_cxa($error);
				}
				$queryUser = $db->select()->from('table.users')->where('mail = ?', $name); 
				$rowUser = $db->fetchRow($queryUser);
				if($rowUser){
					$error=['error'=>'邮箱已存在！'];
					TleMultiFunction_Plugin::Tips_cxa($error);
				}else{
					$dataStruct["mail"]=$name;
				}
				if($nickname){
					$dataStruct["screenName"]=$nickname;
				}else{
					$dataStruct["screenName"]=$name;
				}
				$dataStruct["group"]=$get["defaultMailGroup"];
			}else{
				$error=['error'=>'尚未开启邮箱注册！'];
				TleMultiFunction_Plugin::Tips_cxa($error);
			}
			$_SESSION['mailcode'] = mt_rand(100000,999999);
		}
        return $dataStruct;
    }
	/**
     * 注册表单
     * @access public
     * @return void
     */
    public static function addRegInput(){
        $options = Typecho_Widget::widget('Widget_Options');
		$plug_url = $options->pluginUrl;
		$get = self::getOptions();
      	$url=$_SERVER['PHP_SELF'];
		$login=str_replace('/','\/',__TYPECHO_ADMIN_DIR__).'login.php';
      	$register=str_replace('/','\/',__TYPECHO_ADMIN_DIR__).'register.php';
		$activates = array_keys(Typecho_Plugin::export()['activated']);
		if(!in_array('TleMultiFunction', $activates)){
			return;
		}
		$ja=<<<a
<script>
	$.getScript("$plug_url/TleMultiFunction/assets/js/gt.js");
</script>
a;
		if(preg_match("/{$login}/",$url)){
			$forgot=Typecho_Common::url('passport/forgot', $options->index);
			if((isset($get["enablePhonelogin"])&&$get["enablePhonelogin"]=="y")||(isset($get["enableMaillogin"])&&$get["enableMaillogin"]=="y")){
				$nameinput="";
				if(isset($get["enablePhonelogin"])&&$get["enablePhonelogin"]=="y"){
					$nameinput.="/手机号";
				}
				if(isset($get["enableMaillogin"])&&$get["enableMaillogin"]=="y"){
					$nameinput.="/邮箱";
				}
				$ja.=<<<a
<script>
	$("#name").attr("placeholder","用户名$nameinput");
</script>
a;
			}
			$geetestSet=unserialize(@$get["enableGeetest"]);
			if($geetestSet&&in_array("login",$geetestSet)&&!empty($get["GT_CAPTCHA_ID"])&&!empty($get["GT_PRIVATE_KEY"])){
				$ja.=<<<a
<script>
	var pwdInput=document.getElementById('password');
	var codeInputs='<div id="embed-captcha"></div>';
	var pInput = document.createElement("p");
	pInput.id = "gtInput";
	pwdInput.parentNode.appendChild(pInput);
	document.getElementById('gtInput').innerHTML=codeInputs;
	
	var handlerEmbed = function (captchaObj) {
        captchaObj.appendTo("#embed-captcha");
		$("form[name='login']").submit(function(){
			var validate = captchaObj.getValidate();
			if (!validate) {
				alert("请先完成验证");
				return false;
			}
			return true;
		});
    };
	$.ajax({
		url: "$plug_url/TleMultiFunction/ajax/geetest.php?action=init&t=" + (new Date()).getTime(),/*加随机数防止缓存*/
		type: "get",
		dataType: "json",
		success: function (data) {
			console.log(data);
			initGeetest({
				gt: data.gt,
				challenge: data.challenge,
				new_captcha: data.new_captcha,
				product: "embed", /*产品形式，包括：float，embed，popup。注意只对PC版验证码有效*/
				offline: !data.success
			}, handlerEmbed);
		}
	});
</script>
a;
			}
			$ja.=<<<a
<script>
	$(".more-link").append(' <a href="$forgot">忘记密码</a>');
</script>
a;
		if(isset($get["enableQQlogin"])&&$get["enableQQlogin"]=="y"){
			$qq_state=md5(uniqid(rand(), TRUE));
			$get["qq_state"]=$qq_state;
			TleMultiFunction_Plugin::saveOptions($get);
			$qqloginurl = 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id='.$get['qq_appid'].'&redirect_uri='.urlencode($get['qq_callback']).'&state='.$qq_state;
			$ja.=<<<a
<script>
	$(".typecho-login").append('&nbsp;<a href="$qqloginurl"><img src="$plug_url/TleMultiFunction/assets/images/qq.png" width="30" /></a>&nbsp;');
</script>
a;
		}
		if(isset($get["enableWeibologin"])&&$get["enableWeibologin"]=="y"){
			include_once( 'include/saetv2.ex.class.php' );
			$o = new SaeTOAuthV2( $get['wb_akey'] , $get['wb_skey'] );
			$wb_url = $o->getAuthorizeURL( $get['wb_callback_url'] );
			$ja.=<<<a
<script>
	$(".typecho-login").append('&nbsp;<a href="$wb_url"><img src="$plug_url/TleMultiFunction/assets/images/weibo.png" width="30" /></a>&nbsp;');
</script>
a;
		}
			echo $ja;
        }else if(preg_match("/{$register}/",$url)){
            if((isset($get["enablePhonelogin"])&&$get["enablePhonelogin"]=="y")||(isset($get["enableMaillogin"])&&$get["enableMaillogin"]=="y")){
				$nameinput="";
				if(isset($get["enablePhonelogin"])&&$get["enablePhonelogin"]=="y"){
					$nameinput.="/手机号";
				}
				if(isset($get["enableMaillogin"])&&$get["enableMaillogin"]=="y"){
					$nameinput.="/邮箱";
				}
                $ja.=<<<a
<script>
	var mailInput=document.getElementById('mail');
	var codeInputs='<p><label for="smscode" class="sr-only">验证码</label><input type="text" id="smscode" name="smscode" placeholder="验证码(必填)" value="" class="text-l w-50" /><button id="sendsmscode" class="btn btn-l w-50">发送验证码</button></p><p><label for="nickname" class="sr-only">昵称</label><input type="text" id="nickname" name="nickname" placeholder="昵称(选填)" value="" class="text-l w-100" /></p><p><label for="weburl" class="sr-only">个人主页</label><input type="text" id="weburl" name="weburl" placeholder="个人主页(选填,http://开头)" value="" class="text-l w-100" /></p>';
	var pInput = document.createElement("p");
	pInput.id = "codeInput";
	mailInput.parentNode.appendChild(pInput);
	if(document.getElementById('maillabel')){
		document.getElementById('maillabel').style="display:none";
	}
	if(document.getElementById('namelabel')){
		document.getElementById('namelabel').style="display:none";
	}
	document.getElementById('codeInput').innerHTML=codeInputs;
	var timestamp = (new Date()).valueOf();
	mailInput.value=timestamp+"@yourdomain.com";mailInput.style.display="none";
	$("#name").attr("placeholder","用户名称$nameinput(必填)");

	$("#sendsmscode").click(function(){
		var action="phone";
		var name=$("#name").val();
		if(name.indexOf("@")!=-1){
			action="mail";
		}
		$.post("$plug_url/TleMultiFunction/ajax/sendsms_new.php",{action:action,name:name},function(data){
			var data=JSON.parse(data);
			if(data.error_code==0){
				settime();
			}else{
				alert(data.message);
			}
		});
		return false;
	});
	var timer;
	var countdown=60;
	function settime() {
		if (countdown == 0) {
			$("#sendsmscode").html('重新发送验证码');
			$("#sendsmscode").attr('disabled',false);
			countdown = 60;
			clearTimeout(timer);
			return;
		} else {
			$("#sendsmscode").html(countdown+"秒后重新发送");
			$("#sendsmscode").attr('disabled',true);
			countdown--; 
		} 
		timer=setTimeout(function() { 
			settime();
		},1000) 
	}
	/*限制键盘只能按数字键、小键盘数字键、退格键*/
	$("#smscode").keyup(function(){
		$("#smscode").val($("#smscode").val().replace(/[^\d.]/g,""));
		$("#smscode").val($("#smscode").val().replace(/\.{2,}/g,"."));
		$("#smscode").val($("#smscode").val().replace(/^\./g,""));
		$("#smscode").val($("#smscode").val().replace(".","$#$").replace(/\./g,"").replace("$#$","."));
		$("#smscode").val($("#smscode").val().replace(/^(\-)*(\d+)\.(\d\d).*$/,"$1$2.$3"));
	});
</script>
a;
				$geetestSet=unserialize(@$get["enableGeetest"]);
				if($geetestSet&&in_array("reg",$geetestSet)){
					$ja.=<<<a
<script>
	$(".submit").prepend('<p><div id="embed-captcha"></div></p>');
	
	var handlerEmbed = function (captchaObj) {
        captchaObj.appendTo("#embed-captcha");
		$("form[name='register']").submit(function(){
			var validate = captchaObj.getValidate();
			if (!validate) {
				alert("请先完成验证");
				return false;
			}
			return true;
		});
    };
	$.ajax({
		url: "$plug_url/TleMultiFunction/ajax/geetest.php?action=init&t=" + (new Date()).getTime(),/*加随机数防止缓存*/
		type: "get",
		dataType: "json",
		success: function (data) {
			console.log(data);
			initGeetest({
				gt: data.gt,
				challenge: data.challenge,
				new_captcha: data.new_captcha,
				product: "embed", /*产品形式，包括：float，embed，popup。注意只对PC版验证码有效*/
				offline: !data.success
			}, handlerEmbed);
		}
	});
</script>
a;
				}
				echo $ja;
            }
        }
    }
}