<?php
/**
 * 多功能 控制台
 *
 */
class TleMultiFunction_Console extends Typecho_Widget
{
    /**
     * 手机号登陆
     * @return Typecho_Widget_Helper_Form
     */
    public function phoneLoginForm(){
		$db= Typecho_Db::get();
		$prefix = $db->getPrefix();
        /** 构建表单 */
        $options = Typecho_Widget::widget('Widget_Options');
        $form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/' . TleMultiFunction_Plugin::$action, $options->index),Typecho_Widget_Helper_Form::POST_METHOD);

		$form_data=TleMultiFunction_Plugin::getOptions();

        /** 表单项 */
		$enablePhonelogin = new Typecho_Widget_Helper_Form_Element_Radio('enablePhonelogin', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), isset($form_data["enablePhonelogin"])&&$form_data["enablePhonelogin"]=="y"?"y":"n", _t('手机号登录开关（<a href="https://dysms.console.aliyun.com" target="_blank">阿里云短信服务官网</a>）'), _t("启用后仅在官方后台注册页面增加手机号登陆入口，并在数据表".$prefix."users中添加phone字段。"));
        $form->addInput($enablePhonelogin->addRule('enum', _t(''), array('y', 'n')));
		
		$defaultPhoneGroup = new Typecho_Widget_Helper_Form_Element_Select('defaultPhoneGroup', array("subscriber"=>_t('关注者'), "contributor"=>_t('贡献者'), "editor"=>_t('编辑'), "administrator"=>_t('管理员')), @$form_data["defaultPhoneGroup"], _t('默认用户组'), _t('新用户注册所在的用户组'));
		$form->addInput($defaultPhoneGroup);
		
		$aliAccessKeyId = new Typecho_Widget_Helper_Form_Element_Text('aliAccessKeyId', array("value"), @$form_data["aliAccessKeyId"], _t('阿里云短信服务AccessKeyID'));
        $form->addInput($aliAccessKeyId);
		
		$aliAccessKeySecret = new Typecho_Widget_Helper_Form_Element_Password('aliAccessKeySecret', array("value"), @$form_data["aliAccessKeySecret"], _t('阿里云短信服务AccessKeySecret'));
        $form->addInput($aliAccessKeySecret);
		
		$aliIsExistName = new Typecho_Widget_Helper_Form_Element_Radio('aliIsExistName', array(
            'y'=>_t('包含'),
            'n'=>_t('不包含')
        ), isset($form_data["aliIsExistName"])&&$form_data["aliIsExistName"]=="y"?"y":"n", _t('阿里云短信模板是否包含产品名'), _t("在阿里云创建模板时是否存在产品名。"));
        $form->addInput($aliIsExistName->addRule('enum', _t(''), array('y', 'n')));
		
		$aliTemplateCode = new Typecho_Widget_Helper_Form_Element_Text('aliTemplateCode', array("value"), @$form_data["aliTemplateCode"], _t('阿里云短信服务模版CODE'), _t('<small>若包含产品名变量，则需要新建模板内容为：<br /><b>验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！</b><br />的形式；<br />否则为<br /><b>您正在进行身份验证，您的验证码是${code}，打死不要告诉别人哦！</b><br />的形式。</small>'));
        $form->addInput($aliTemplateCode);
		
		$aliSignName = new Typecho_Widget_Helper_Form_Element_Text('aliSignName', array("value"), @$form_data["aliSignName"], _t('阿里云短信服务签名名称'));
        $form->addInput($aliSignName);
		
        /** 动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        /** 设置值 */
        $do->value('phoneLogin');
        $submit->value('保存');

        return $form;
    }
	/**
     * 邮箱登陆
     * @return Typecho_Widget_Helper_Form
     */
    public function mailLoginForm(){
		$db= Typecho_Db::get();
		$prefix = $db->getPrefix();
        /** 构建表单 */
        $options = Typecho_Widget::widget('Widget_Options');
        $form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/' . TleMultiFunction_Plugin::$action, $options->index),Typecho_Widget_Helper_Form::POST_METHOD);
			
		$form_data=TleMultiFunction_Plugin::getOptions();

        /** 生成数量 */
		$enableMaillogin = new Typecho_Widget_Helper_Form_Element_Radio('enableMaillogin', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), isset($form_data["enableMaillogin"])&&$form_data["enableMaillogin"]=="y"?"y":"n", _t('邮箱登录开关'), _t("启用后仅在官方后台注册页面增加邮箱登陆入口。"));
        $form->addInput($enableMaillogin->addRule('enum', _t(''), array('y', 'n')));
		
		$defaultMailGroup = new Typecho_Widget_Helper_Form_Element_Select('defaultMailGroup', array("subscriber"=>_t('关注者'), "contributor"=>_t('贡献者'), "editor"=>_t('编辑'), "administrator"=>_t('管理员')), @$form_data["defaultMailGroup"], _t('默认用户组'), _t('新用户注册所在的用户组'));
		$form->addInput($defaultMailGroup);
		
		$host = new Typecho_Widget_Helper_Form_Element_Text('host', array("value"), @$form_data["host"], _t('服务器(SMTP)'), _t('如: smtp.exmail.qq.com，以下配置完成可支持通过邮箱找回密码，找回密码功能是否开启邮箱登陆皆可。'));
		$form->addInput($host);
		
        $port = new Typecho_Widget_Helper_Form_Element_Text('port', array("value"), isset($form_data["port"])?$form_data["port"]:'465', _t('端口'), _t('如: 25、465(SSL)、587(SSL)'));
		$form->addInput($port);

        $username = new Typecho_Widget_Helper_Form_Element_Text('username', array("value"), @$form_data["username"], _t('帐号'), _t('如: hello@example.com'));
		$form->addInput($username);
		
        $password = new Typecho_Widget_Helper_Form_Element_Password('password', array("value"), @$form_data["password"], _t('密码'));
		$form->addInput($password);

        $secure = new Typecho_Widget_Helper_Form_Element_Select('secure',array(
            'ssl' => _t('SSL'),
            'tls' => _t('TLS'),
            'none' => _t('无')
        ), @$form_data["secure"], _t('安全类型'));
		$form->addInput($secure);

        /** 动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        /** 设置值 */
        $do->value('mailLogin');
        $submit->value('保存');

        return $form;
    }
	/**
     * 社交登陆
     * @return Typecho_Widget_Helper_Form
     */
    public function oAuthLoginForm(){
		$db= Typecho_Db::get();
		$prefix = $db->getPrefix();
        /** 构建表单 */
        $options = Typecho_Widget::widget('Widget_Options');
        $form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/' . TleMultiFunction_Plugin::$action, $options->index),Typecho_Widget_Helper_Form::POST_METHOD);
			
		$form_data=TleMultiFunction_Plugin::getOptions();

        /** 生成数量 */
		//QQ登陆
		$enableQQlogin = new Typecho_Widget_Helper_Form_Element_Radio('enableQQlogin', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), isset($form_data["enableQQlogin"])&&$form_data["enableQQlogin"]=="y"?"y":"n", _t('QQ登录开关（<a href="https://connect.qq.com/" target="_blank">QQ互联官网</a>）'), _t("启用后仅在官方后台注册页面增加QQ登陆入口。"));
        $form->addInput($enableQQlogin->addRule('enum', _t(''), array('y', 'n')));
		
		$defaultQQGroup = new Typecho_Widget_Helper_Form_Element_Select('defaultQQGroup', array("subscriber"=>_t('关注者'), "contributor"=>_t('贡献者'), "editor"=>_t('编辑'), "administrator"=>_t('管理员')), @$form_data["defaultQQGroup"], _t('默认用户组'), _t('新用户注册所在的用户组'));
		$form->addInput($defaultQQGroup);
		
		$qq_appid = new Typecho_Widget_Helper_Form_Element_Text('qq_appid', array("value"), @$form_data["qq_appid"], _t('QQ互联appid'));
        $form->addInput($qq_appid);
		
		$qq_appkey = new Typecho_Widget_Helper_Form_Element_Password('qq_appkey', array("value"), @$form_data["qq_appkey"], _t('QQ互联appkey'));
        $form->addInput($qq_appkey);
		
		$qq_callback = new Typecho_Widget_Helper_Form_Element_Text('qq_callback', array("value"), Typecho_Common::url("/index.php/action/tleMultiFunction-code?do=oAuthCallback&action=qq", $options->index), _t('QQ互联callback回调'), _t('回调地址，复制到QQ互联应用信息中即可，无需更改。备注：若在已有用户的情况下，修改appid和appkey，会由于openid不同导致重复注册用户。'));
        $form->addInput($qq_callback);
		//微博登陆
		$enableWeibologin = new Typecho_Widget_Helper_Form_Element_Radio('enableWeibologin', array(
            'y'=>_t('启用'),
            'n'=>_t('禁用')
        ), isset($form_data["enableWeibologin"])&&$form_data["enableWeibologin"]=="y"?"y":"n", _t('微博登录开关（<a href="https://open.weibo.com" target="_blank">微博开放平台官网</a>）'), _t("启用后仅在官方后台注册页面增加微博登陆入口。"));
        $form->addInput($enableWeibologin->addRule('enum', _t(''), array('y', 'n')));
		
		$defaultWeiboGroup = new Typecho_Widget_Helper_Form_Element_Select('defaultWeiboGroup', array("subscriber"=>_t('关注者'), "contributor"=>_t('贡献者'), "editor"=>_t('编辑'), "administrator"=>_t('管理员')), @$form_data["defaultWeiboGroup"], _t('默认用户组'), _t('新用户注册所在的用户组'));
		$form->addInput($defaultWeiboGroup);
		
		$wb_akey = new Typecho_Widget_Helper_Form_Element_Text('wb_akey', array("value"), @$form_data["wb_akey"], _t('填写在微博开放平台申请的AppKey'));
        $form->addInput($wb_akey);
		
		$wb_skey = new Typecho_Widget_Helper_Form_Element_Password('wb_skey', array("value"), @$form_data["wb_skey"], _t('填写在微博开放平台申请的AppSecret'));
        $form->addInput($wb_skey);
		
		$wb_callback_url = new Typecho_Widget_Helper_Form_Element_Text('wb_callback_url', array("value"), Typecho_Common::url("/index.php/action/tleMultiFunction-code?do=oAuthCallback&action=weibo", $options->index), _t('微博开放平台授权回调接口地址'), _t('填写在微博开放平台配置的授权回调接口地址'));
        $form->addInput($wb_callback_url);
		
        /** 动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        /** 设置值 */
        $do->value('oAuthLogin');
        $submit->value('保存');

        return $form;
    }
	/**
     * 验证码
     * @return Typecho_Widget_Helper_Form
     */
    public function verifyCodeForm(){
		$db= Typecho_Db::get();
		$prefix = $db->getPrefix();
        /** 构建表单 */
        $options = Typecho_Widget::widget('Widget_Options');
        $form = new Typecho_Widget_Helper_Form(Typecho_Common::url('/action/' . TleMultiFunction_Plugin::$action, $options->index),Typecho_Widget_Helper_Form::POST_METHOD);
			
		$form_data=TleMultiFunction_Plugin::getOptions();

        /** 生成数量 */
		$geetestSet=unserialize(@$form_data["enableGeetest"]);
		$geetestVal=array();
		if($geetestSet&&in_array("login",$geetestSet)){array_push($geetestVal,"login");}
		if($geetestSet&&in_array("reg",$geetestSet)){array_push($geetestVal,"reg");}
		if($geetestSet&&in_array("forgot",$geetestSet)){array_push($geetestVal,"forgot");}
		$enableGeetest = new Typecho_Widget_Helper_Form_Element_Checkbox('enableGeetest',
		array(
			'login' => _t('登陆验证'),
			'reg' => _t('注册验证'),
			'forgot' => _t('忘记密码验证')
		),$geetestVal, _t('GeeTest验证码开关（<a href="https://www.geetest.com/" target="_blank">GeeTest极验官网</a>）')
		);
		$form->addInput($enableGeetest->multiMode());
		
		$GT_CAPTCHA_ID = new Typecho_Widget_Helper_Form_Element_Text('GT_CAPTCHA_ID', array("value"), @$form_data["GT_CAPTCHA_ID"], _t('geetestID'));
        $form->addInput($GT_CAPTCHA_ID);
		
		$GT_PRIVATE_KEY = new Typecho_Widget_Helper_Form_Element_Password('GT_PRIVATE_KEY', array("value"), @$form_data["GT_PRIVATE_KEY"], _t('geetestKEY'), _t(''));
        $form->addInput($GT_PRIVATE_KEY);
		
        /** 动作 */
        $do = new Typecho_Widget_Helper_Form_Element_Hidden('do');
        $form->addInput($do);

        /** 提交按钮 */
        $submit = new Typecho_Widget_Helper_Form_Element_Submit();
        $submit->input->setAttribute('class', 'btn primary');
        $form->addItem($submit);

        /** 设置值 */
        $do->value('verifyCode');
        $submit->value('保存');

        return $form;
    }
}