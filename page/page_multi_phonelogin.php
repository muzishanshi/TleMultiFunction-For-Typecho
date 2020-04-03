<?php
/**
 * 手机注册页面
 *
 * @package custom
 */
?>
<?php
@session_start();
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
$pluginsname='TleMultiFunction';
include dirname(__FILE__).'/../../plugins/'.$pluginsname.'/include/function.php';

$queryPlugins= $this->db->select('value')->from('table.options')->where('name = ?', 'plugins'); 
$rowPlugins = $this->db->fetchRow($queryPlugins);
$plugins=@unserialize($rowPlugins['value']);
if(!isset($plugins['activated']['TleMultiFunction'])){
	die('未启用多功能插件');
}

$queryTleMultiFunction= $this->db->select('value')->from('table.options')->where('name = ?', 'plugin:TleMultiFunction'); 
$rowTleMultiFunction = $this->db->fetchRow($queryTleMultiFunction);
$tleMultiFunction=@unserialize($rowTleMultiFunction['value']);
if($tleMultiFunction['phonelogin']=='n'){
	die('未启用手机号登录功能');
}
$setphonelogin=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/'.$pluginsname.'/config/setphonelogin.php'),'<?php die; ?>'));
?>
<?php $this->need('header.php'); ?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/css/amazeui.min.css"/>
<script src="https://libs.baidu.com/jquery/1.11.1/jquery.min.js"></script>
<?php
$db = Typecho_Db::get();

if ($this->user->hasLogin()) {
	if ($this->user->group=='administrator'){
		$action = isset($_POST['action']) ? addslashes(trim($_POST['action'])) : '';
		if($action=='submit'){
			$accessKeyId = isset($_POST['accessKeyId']) ? addslashes(trim($_POST['accessKeyId'])) : '';
			$accessKeySecret = isset($_POST['accessKeySecret']) ? addslashes(trim($_POST['accessKeySecret'])) : '';
			$isindex = isset($_POST['isindex']) ? addslashes(trim($_POST['isindex'])) : '';
			$iscontain = isset($_POST['iscontain']) ? addslashes(trim($_POST['iscontain'])) : '';
			$templatecode = isset($_POST['templatecode']) ? addslashes(trim($_POST['templatecode'])) : '';
			$signname = isset($_POST['signname']) ? addslashes(trim($_POST['signname'])) : '';
			if($accessKeyId&&$accessKeySecret&&$isindex&&$iscontain&&$templatecode&&$signname){
				file_put_contents(dirname(__FILE__).'/../../plugins/'.$pluginsname.'/config/setphonelogin.php','<?php die; ?>'.serialize(array(
					'accessKeyId'=>$accessKeyId,
					'accessKeySecret'=>$accessKeySecret,
					'isindex'=>$isindex,
					'iscontain'=>$iscontain,
					'templatecode'=>$templatecode,
					'signname'=>$signname
				)));
				TleMultiFunction_Plugin::funWriteAdminPage($db,'register.php',$isindex);
				echo "<script>location.href='';</script>";
			}
		}
		?>
		<div class="am-g">
		  <div class="am-u-md-8 am-u-sm-centered">
			<form class="am-form" method="post" action="">
			  <fieldset class="am-form-set">
				<div class="am-form-group">
					<input type="text" name="accessKeyId" required value="<?php if(@$accessKeyId!=''){echo $accessKeyId;}else{echo @$setphonelogin['accessKeyId'];} ?>" placeholder="阿里云短信服务AccessKeyID">
				</div>
				<div class="am-form-group">
					<input type="text" name="accessKeySecret" required value="<?php if(@$accessKeySecret!=''){echo $accessKeySecret;}else{echo @$setphonelogin['accessKeySecret'];} ?>" placeholder="阿里云短信服务AccessKeySecret">
				</div>
				<div class="am-form-group" style="background-color:#fff;">
				  <span>前台文章url是否存在index.php：</span>
				  <label class="am-radio-inline">
					<input type="radio" name="isindex" value="y" data-am-ucheck <?php if(!@$setphonelogin||@$setphonelogin['isindex']=='y'){echo 'checked';} ?>> 存在
				  </label>
				  <label class="am-radio-inline">
					<input type="radio" name="isindex" value="n" data-am-ucheck <?php if(@$setphonelogin['isindex']=='n'){echo 'checked';} ?>>不存在
				  </label>
				</div>
				<div class="am-form-group" style="background-color:#fff;">
				  <span>阿里云短信模板是否包含产品名：</span>
				  <label class="am-radio-inline">
					<input type="radio" name="iscontain" value="y" data-am-ucheck <?php if(@$setphonelogin['iscontain']=='y'){echo 'checked';} ?>> 包含
				  </label>
				  <label class="am-radio-inline">
					<input type="radio" name="iscontain" value="n" data-am-ucheck <?php if(!@$setphonelogin||@$setphonelogin['iscontain']=='n'){echo 'checked';} ?>>不包含
				  </label>
				</div>
				<div class="am-form-group">
					<input type="text" name="templatecode" required value="<?php if(@$templatecode!=''){echo $templatecode;}else{echo @$setphonelogin['templatecode'];} ?>" placeholder="阿里云短信服务模版CODE">
					<small>
						若包含产品名变量，则需要新建模板内容为：<br />
						<b>验证码${code}，您正在进行${product}身份验证，打死不要告诉别人哦！</b><br />
						的形式；<br />
						否则为<br />
						<b>您正在进行身份验证，您的验证码是${code}，打死不要告诉别人哦！</b><br />
						的形式。
					</small>
				</div>
				<div class="am-form-group">
					<input type="text" name="signname" value="<?php if(@$signname!=''){echo $signname;}else{echo @$setphonelogin['signname'];} ?>" placeholder="阿里云短信服务签名名称">
				</div>
			  </fieldset>
			  <input type="hidden" value="submit" required name="action" />
			  <button type="submit" class="am-btn am-btn-primary am-btn-block">修改配置</button>
			</form>
		  </div>
		</div>
		<?php
	}else{
		/* 如果普通用户已经登录，直接返回 */
		$this->response->redirect($this->options->index);
	}
}else{
	$action = isset($_POST['action']) ? addslashes(trim($_POST['action'])) : '';
	if($action=='regbysms'){
		$name = isset($_POST['name']) ? addslashes(trim($_POST['name'])) : '';
		$code = isset($_POST['code']) ? addslashes(trim($_POST['code'])) : '';
		if($name&&$code){
			$sessionCode = isset($_SESSION['phonecode']) ? $_SESSION['phonecode'] : '';
			if(strcasecmp($code,$sessionCode)==0){
				if (isset($_SESSION["newphone"])&&$name!=$_SESSION["newphone"]) {
					echo'<script>alert("填写手机号和发送验证码的手机号不一致！");location.href="";</script>';
					exit;
				}
				$query= $db->select('uid')->from('table.users')->where('name = ?', $name); 
				$user = $db->fetchRow($query);
				if($user){
					/*登录*/
					$authCode = function_exists('openssl_random_pseudo_bytes') ?
						bin2hex(openssl_random_pseudo_bytes(16)) : sha1(Typecho_Common::randString(20));
					$user['authCode'] = $authCode;

					Typecho_Cookie::set('__typecho_uid', $user['uid'], 0);
					Typecho_Cookie::set('__typecho_authCode', Typecho_Common::hash($authCode), 0);

					/*更新最后登录时间以及验证码*/
					$db->query($db
					->update('table.users')
					->expression('logged', 'activated')
					->rows(array('authCode' => $authCode))
					->where('uid = ?', $user['uid']));
					
					/*压入数据*/
					$this->push($user);
					$this->_user = $user;
					$this->_hasLogin = true;
					$this->pluginHandle()->loginSucceed($this, $name, '', false);
					
					/*重置短信验证码*/
					$randCode = '';
					$chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPRSTUVWXYZ23456789';
					for ( $i = 0; $i < 5; $i++ ){
						$randCode .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
					}
					$_SESSION['code'] = strtoupper($randCode);

					$this->widget('Widget_Notice')->set(_t('用户已存在，已为您登录 '), 'success');
					/*跳转验证后地址*/
					if (NULL != $this->request->referer) {
						$this->response->redirect($this->request->referer);
					} else if (!$this->user->pass('contributor', true)) {
						/*不允许普通用户直接跳转后台*/
						$this->response->redirect($this->options->profileUrl);
					} else {
						$this->response->redirect($this->options->adminUrl);
					}
				}else{
					/*注册*/
					/** 如果已经登录 */
					if ($this->user->hasLogin() || !$this->options->allowRegister) {
						/** 直接返回 */
						$this->response->redirect($this->options->index);
					}
					$hasher = new PasswordHash(8, true);
					$generatedPassword = Typecho_Common::randString(7);

					$dataStruct = array(
						'name'      =>  $name,
						'mail'      =>  $name.'@tongleer.com',
						'screenName'=>  $name,
						'password'  =>  $hasher->HashPassword($generatedPassword),
						'created'   =>  time(),
						'group'     =>  'subscriber',
						'phone'     =>  $name
					);
					
					$insert = $db->insert('table.users')->rows($dataStruct);
					$insertId = $db->query($insert);

					$this->pluginHandle()->finishRegister($this);

					$this->user->login($name, $generatedPassword);

					Typecho_Cookie::delete('__typecho_first_run');
					
					/*重置短信验证码*/
					$randCode = '';
					$chars = 'abcdefghijkmnpqrstuvwxyzABCDEFGHIJKLMNPRSTUVWXYZ23456789';
					for ( $i = 0; $i < 5; $i++ ){
						$randCode .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
					}
					$_SESSION['code'] = strtoupper($randCode);

					$this->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $this->screenName, $generatedPassword), 'success');
					$this->response->redirect($this->options->adminUrl);
				}
			}else{
				echo'<script>alert("验证码错误！");</script>';
			}
		}
	}
	?>
	<!-- content section -->
	<section>
		<div style="width:300px;margin:0 auto;">
			<form action="" method="post">
				<div class="am-panel-hd">
				  <h4 class="am-panel-title" data-am-collapse="{parent: '#accordion', target: '#do-not-say-1'}">
					用户注册
				  </h4>
				</div>
				<div class="am-input-group">
				  <span class="am-input-group-label"><i class="am-icon-mobile-phone am-icon-fw"></i></span>
				  <input id="name" name="name" type="text" class="am-form-field" value="<?php echo @$_POST['name']; ?>" required placeholder="<?php _e('手机号'); ?>">
				</div>
				<div class="am-input-group">
				  <input id="code" name="code" type="text" class="am-form-field" value="<?php echo @$_POST['code']; ?>" required placeholder="<?php _e('短信验证码'); ?>">
				  <span id="sendsmsmsg" class="am-input-group-label">发送</span>
				</div>
				<input type="hidden" id="sitetitle" value="<?php $this->options->title();?>" />
				<input type="hidden" name="action" value="regbysms" />
				<input type="hidden" id="pluginsname" value="<?=$pluginsname;?>" />
				<button id="reg" type="button" class="am-btn am-btn-success am-btn-block"><?php _e('注册'); ?></button>
			</form>
		</div>
	</section>
	<!-- end content section -->
	
	<script>
	$(function(){
		$("#sendsmsmsg").click(function(){
			var name=$("#name").val();
			var regexp = /^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/; 
			if(!regexp.test(name)){
				alert('请输入有效的手机号码！'); 
				return false; 
			}
			$.post("<?php $this->options->siteUrl(); ?>usr/plugins/<?=$pluginsname;?>/ajax/sendsms.php",{action:"phone",name:name,sitetitle:$('#sitetitle').val(),pluginsname:$('#pluginsname').val()},function(data){
				var data=JSON.parse(data);
				if(data.error_code==0){
					settime();
				}else{
					alert(data.message);
				}
			});
		});
		$("#reg").click(function(e){
			var name=$("#name").val();
			var regexp = /^(((13[0-9]{1})|(15[0-9]{1})|(18[0-9]{1}))+\d{8})$/; 
			if(!regexp.test(name)){
				alert('请输入有效的手机号码！'); 
				return; 
			}
			var yzm = $("input[name=code]").val().replace(/(^\s*)|(\s*$)/g, "");
			if(yzm==""){
				alert("请输入短信验证码");
				return;
			}
			$('form').submit();
		});
		var timer;
		var countdown=60;
		function settime() {
			if (countdown == 0) {
				$("#sendsmsmsg").html("发送");
				$("#sendsmsmsg").attr('disabled',false);
				countdown = 60;
				clearTimeout(timer);
				return;
			} else {
				$("#sendsmsmsg").html(countdown+"秒");
				$("#sendsmsmsg").attr('disabled',true);
				$("#sendsmsmsg").unbind("click");
				countdown--; 
			} 
			timer=setTimeout(function() { 
				settime() 
			},1000) 
		}
	});
	</script>
<?php
}
?>
<script src="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/js/amazeui.min.js" type="text/javascript"></script>
<?php $this->need('footer.php'); ?>