<?php
/**
 * 多功能-第三方登录
 *
 * @package custom
 */
?>
<?php
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
if($tleMultiFunction['oauthlogin']=='n'){
	die('未启用第三方登陆功能');
}
$setoauth=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/'.$pluginsname.'/config/setoauth.php'),'<?php die; ?>'));

if(strpos($this->permalink,'?')){
	$url=substr($this->permalink,0,strpos($this->permalink,'?'));
}else{
	$url=$this->permalink;
}
?>
<?php
$code = isset($_GET['code']) ? addslashes(trim($_GET['code'])) : '';
$state = isset($_GET['state']) ? addslashes(trim($_GET['state'])) : '';
if($code!=''&&$state!=''){
	$state=explode("|",$state);
	$db = Typecho_Db::get();
	
	if(!$state || $state[0] != $setoauth['qqstate']){
		die('30001');
	}
	$tokenData=getQQAccessToken($setoauth['qq_appid'],$setoauth['qq_appkey'],$setoauth['qq_callback'],$_GET['code']);
	$qqUserData=getQQOpenID($tokenData['access_token']);
	$oauthid=$qqUserData->openid;
	$userinfo=getQQUserInfo($tokenData['access_token'],$setoauth['qq_appid'],$oauthid);
	
	$name=$userinfo['nickname'];
	$gender=$userinfo['gender'];
	$figureurl=$userinfo['figureurl_qq_2'];
	$oauthQuery= $this->db->select()->from('table.multi_oauthlogin')->where('oauthid = ?', $oauthid);
	$oauthUser = $db->fetchRow($oauthQuery);
	if($oauthUser){
		/*登录*/
		$query= $this->db->select()->from('table.users')->where('uid = ?', $oauthUser['oauthuid']);
		$user = $db->fetchRow($query);
		/** 如果已经登录 */
		if ($this->user->hasLogin()) {
			/** 直接返回 */
			$this->response->redirect($state[1]);
		}
		
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
		$this->pluginHandle()->loginSucceed($this, $user["name"], '', false);
		
		$this->widget('Widget_Notice')->set(_t('用户已存在，已为您登录 '), 'success');
		/*跳转验证后地址*/
		if (NULL != $this->request->referer) {
			$this->response->redirect($this->request->referer);
		} else if (!$this->user->pass('contributor', true)) {
			/*不允许普通用户直接跳转后台*/
			$this->response->redirect($this->options->profileUrl);
		} else {
			$this->response->redirect($state[1]);
		}
	}else{
		/*注册*/
		/** 如果已经登录 */
		if ($this->user->hasLogin() || !$this->options->allowRegister) {
			/** 直接返回 */
			$this->response->redirect($state[1]);
		}
		$hasher = new PasswordHash(8, true);
		$generatedPassword = Typecho_Common::randString(7);

		$newname=$name;
		$queryUser = $this->db->select()->from('table.users')->where('name = ?', $name); 
		$rowUser = $this->db->fetchRow($queryUser);
		if($rowUser){
			for($i=1;;$i++){
				$newname=$name.$i;
				$queryUser = $this->db->select()->from('table.users')->where('name = ?', $newname); 
				$rowUser = $this->db->fetchRow($queryUser);
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
			'group'     =>  'subscriber'
		);
		
		$insert = $db->insert('table.users')->rows($dataStruct);
		$userId = $db->query($insert);
		
		$dataOAuth = array(
			'oauthid'      =>  $oauthid,
			'oauthuid'      =>  $userId,
			'oauthnickname'=>  $newname,
			'oauthfigureurl'  =>  $figureurl,
			'oauthgender'   =>  $gender,
			'oauthtype'     =>  'qq'
		);
		
		$insert = $db->insert('table.multi_oauthlogin')->rows($dataOAuth);
		$insertId = $db->query($insert);

		$this->pluginHandle()->finishRegister($this);

		$this->user->login($newname, $generatedPassword);

		Typecho_Cookie::delete('__typecho_first_run');
		
		$this->widget('Widget_Notice')->set(_t('用户 <strong>%s</strong> 已经成功注册, 密码为 <strong>%s</strong>', $this->screenName, $generatedPassword), 'success');
		$this->response->redirect($state[1]);
	}
}else{
	$page = isset($_GET['page']) ? addslashes(trim($_GET['page'])) : '';
	if($page==''){
		if ($this->user->pass('administrator')){
			?>
			<h3>管理员配置</h3>
			<hr />
			<p>
				第一步：配置参数
			</p>
			<?php
			$id = isset($_GET['id']) ? addslashes(trim($_GET['id'])) : 0;
			if($id!=0){
				$delete = $this->db->delete('table.multi_oauthlogin')->where('oauthuid = ?', $id);
				$deletedRows = $this->db->query($delete);
				echo "<script>location.href='".$this->permalink."';</script>";exit;
			}
			$action = isset($_POST['action']) ? addslashes(trim($_POST['action'])) : '';
			if($action=='setoauthlogin'){
				$qq_appid = isset($_POST['qq_appid']) ? addslashes(trim($_POST['qq_appid'])) : '';
				$qq_appkey = isset($_POST['qq_appkey']) ? addslashes(trim($_POST['qq_appkey'])) : '';
				$qq_callback = isset($_POST['qq_callback']) ? addslashes(trim($_POST['qq_callback'])) : '';
				if($qq_appid&&$qq_appkey&&$qq_callback){
					file_put_contents(dirname(__FILE__).'/../../plugins/'.$pluginsname.'/config/setoauth.php','<?php die; ?>'.serialize(array(
						'qq_appid'=>$qq_appid,
						'qq_appkey'=>$qq_appkey,
						'qq_callback'=>$qq_callback
					)));
				}
			}
			?>
			<script src="http://apps.bdimg.com/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
			<link rel="stylesheet" href="//cdnjs.loli.net/ajax/libs/mdui/0.4.2/css/mdui.min.css">
			<script src="//cdnjs.loli.net/ajax/libs/mdui/0.4.2/js/mdui.min.js"></script>
			<!-- content section -->
			<section>
				<div class="mdui-shadow-10" style="width:300px;">
					<div class="mdui-typo mdui-valign mdui-color-blue mdui-text-color-white">
					  <h6 class="mdui-center">第三方登录设置</h6>
					</div>
					<form action="" method="post" class="mdui-p-x-1 mdui-p-y-1">
						<div class="mdui-textfield mdui-textfield-floating-label">
						  <label class="mdui-textfield-label"><?php _e('QQ互联appid'); ?></label>
						  <input class="mdui-textfield-input" id="qq_appid" name="qq_appid" type="text" required value="<?php if(@$qq_appid!=''){echo $qq_appid;}else{echo @$setoauth['qq_appid'];} ?>"/>
						  <div class="mdui-textfield-error">QQ互联appid不能为空</div>
						</div>
						<div class="mdui-textfield mdui-textfield-floating-label">
						  <label class="mdui-textfield-label"><?php _e('QQ互联appkey'); ?></label>
						  <input class="mdui-textfield-input" id="qq_appkey" name="qq_appkey" type="text" required value="<?php if(@$qq_appkey!=''){echo $qq_appkey;}else{echo @$setoauth['qq_appkey'];} ?>"/>
						  <div class="mdui-textfield-error">QQ互联appkey不能为空</div>
						</div>
						<div class="mdui-textfield mdui-textfield-floating-label">
						  <label class="mdui-textfield-label"><?php _e('QQ互联callback回调'); ?></label>
						  <input class="mdui-textfield-input" id="qq_callback" name="qq_callback" type="text" required value="<?=$this->permalink;?>" readOnly />
						  <small>回调地址，复制到QQ互联应用信息中即可，无需更改。备注：若在已有用户的情况下，修改appid和appkey，会由于openid不同导致重复注册用户。</small>
						  <div class="mdui-textfield-error">QQ互联callback回调不能为空</div>
						</div>
						<div class="mdui-row-xs-1">
						  <div class="mdui-col">
							<input type="hidden" name="action" value="setoauthlogin" />
							<button id="setoauthlogin" class="mdui-btn mdui-btn-block mdui-btn-raised mdui-color-theme-accent mdui-ripple mdui-color-blue mdui-text-color-white"><?php _e('修改设置'); ?></button>
						  </div>
						</div>
					</form>
				</div>
			</section>
			<!-- end content section -->
			<script>
			$("#setoauthlogin").click(function(){
				$('form').submit();
			});
			function delUser(id){
				if(confirm('确认要删除该用户吗？')){
					location.href="<?=$this->permalink;?>?id="+id;
				}
			}
			</script>
			<p>
				第二步：将以下代码放到想要添加QQ登录的地方即可。
				<?php
				$protocolurl=isProtocol().$_SERVER["HTTP_HOST"].$_SERVER["REQUEST_URI"];
				?>
			</p>
			<p>
				<textarea rows="2" cols="100"><a href="<?=$this->permalink;?>?page=qqlogin&protocolurl=<?=$protocolurl;?>"><img src="http://me.tongleer.com/mob/resource/images/qq_login_blue.png" /></a></textarea>
			</p>
			<p>
				测试：<a href="?page=qqlogin&protocolurl=<?=$protocolurl;?>"><img src="http://me.tongleer.com/mob/resource/images/qq_login_blue.png" /></a>（备注：已登录或禁止注册时不会进行登录和注册。）
			</p>
			<hr />
			<h3>第三方登录用户管理</h3>
			<small>
				删除后要去typecho后台用户管理删除对应用户，或通过typecho后台用户管理删除用户后要在此处删除QQ登录注册的用户，保持同步。
			</small>
			<hr />
			<div class="mdui-table-fluid">
			<table class="mdui-table">
			  <thead>
				<tr>
				  <th>用户名</th>
				  <th>昵称</th>
				  <th>头像</th>
				  <th>注册时间</th>
				  <th>操作</th>
				</tr>
			  </thead>
			  <tbody>
				<?php
				$queryArticle= "select * from ".$this->db->getPrefix()."multi_oauthlogin as ol inner join ".$this->db->getPrefix()."users as u on ol.oauthuid = u.uid";
				$page_now = isset($_GET['page_now']) ? intval($_GET['page_now']) : 1;
				if($page_now<1){
					$page_now=1;
				}
				$resultTotal = $this->db->fetchAll($queryArticle);
				$page_rec=20;
				$totalrec=count($resultTotal);
				$page=ceil($totalrec/$page_rec);
				if($page_now>$page){
					$page_now=$page;
				}
				if($page_now<=1){
					$before_page=1;
					if($page>1){
						$after_page=$page_now+1;
					}else{
						$after_page=1;
					}
				}else{
					$before_page=$page_now-1;
					if($page_now<$page){
						$after_page=$page_now+1;
					}else{
						$after_page=$page;
					}
				}
				$i=($page_now-1)*$page_rec<0?0:($page_now-1)*$page_rec;
				$query= "select * from ".$this->db->getPrefix()."multi_oauthlogin as ol inner join ".$this->db->getPrefix()."users as u on ol.oauthuid = u.uid order by u.created desc limit ".$i.",".$page_rec;
				$result = $this->db->fetchAll($query);
				foreach($result as $value){
				?>
				<tr>
				  <td><?=$value['name'];?></td>
				  <td><?=$value['screenName'];?></td>
				  <td><img src="<?=$value['oauthfigureurl'];?>" width="20" /></td>
				  <td><?=date('Y-m-d H:i:s',$value['created']);?></td>
				  <td>
					<a href="javascript:delUser('<?=$value['oauthuid'];?>');">删除</a>
				  </td>
				</tr>
				<?php
				}
				?>
			  </tbody>
			</table>
			<div>
			  共 <?=$totalrec;?> 条记录
			  <div>
				<ul style="list-style:none">
				  <?php if($page_now!=1){?>
					<li style="float:left;margin-right:10px;"><a href="<?=$url;?>?page_now=1">首页</a></li>
				  <?php }?>
				  <?php if($page_now>1){?>
					<li style="float:left;margin-right:10px;"><a href="<?=$url;?>?page_now=<?=$before_page;?>">&laquo; 上一页</a></li>
				  <?php }?>
				  <?php if($page_now<$page){?>
					<li style="float:left;margin-right:10px;"><a href="<?=$url;?>?page_now=<?=$after_page;?>">下一页 &raquo;</a></li>
				  <?php }?>
				  <?php if($page_now!=$page){?>
					<li style="float:left;margin-right:10px;"><a href="<?=$url;?>?page_now=<?=$page;?>">尾页</a></li>
				  <?php }?>
				</ul>
			  </div>
			</div>
		  </div>
			<?php
		}
	}else if($page=='qqlogin'){
		$qqstate=md5(uniqid(rand(), TRUE));
		file_put_contents(dirname(__FILE__).'/../../plugins/'.$pluginsname.'/config/setoauth.php','<?php die; ?>'.serialize(array(
			'qq_appid'=>$setoauth['qq_appid'],
			'qq_appkey'=>$setoauth['qq_appkey'],
			'qq_callback'=>$setoauth['qq_callback'],
			'qqstate'=>$qqstate
		)));
		$protocolurl = isset($_GET['protocolurl']) ? addslashes(trim($_GET['protocolurl'])) : '';
		$login_url = 'https://graph.qq.com/oauth2.0/authorize?response_type=code&client_id='.$setoauth['qq_appid'].'&redirect_uri='.urlencode($setoauth['qq_callback']).'&state='.$qqstate."|".$protocolurl;
		header("Location:$login_url");
	}
}
?>