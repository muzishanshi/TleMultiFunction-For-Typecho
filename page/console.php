<?php
include 'header.php';
include 'menu.php';
include __TYPECHO_ROOT_DIR__.__TYPECHO_PLUGIN_DIR__.'/TleMultiFunction/include/function.php';

$current = $request->get('type', 'index');
$co = $request->get('co', '');
$title = $current == 'index' ? $menu->title : $menu->title;
$db = Typecho_Db::get();
?>

<div class="main">
    <div class="body container">
        <div class="typecho-page-title">
            <h2><?=$title?></h2>
        </div>
        <div class="row typecho-page-main" role="main">
            <div class="col-mb-12">
                <ul class="typecho-option-tabs fix-tabs clearfix">
                    <li <?=($current == 'index' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel); ?>"><?php _e('手机登录'); ?></a></li>
					<li <?=($current == 'mail' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=mail'); ?>"><?php _e('邮箱登录'); ?></a></li>
                    <li <?=($current == 'social' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=social'); ?>"><?php _e('社交登录'); ?></a></li>
					<li <?=($current == 'verifycode' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=verifycode'); ?>"><?php _e('验证码'); ?></a></li>
					<li <?=($current == 'userlist' ? ' class="current"' : '')?>><a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=userlist'); ?>"><?php _e('用户列表'); ?></a></li>
                    <li><a href="<?php $options->adminUrl('options-plugin.php?config=TleMultiFunction') ?>"><?php _e('旧插件设置'); ?></a></li>
                </ul>
            </div>

            <?php
				if ($current == 'index'){
			?>
                <div class="typecho-edit-theme">
                    <div class="col-mb-12 col-tb-8 col-9 content">
                        <?php Typecho_Widget::widget('TleMultiFunction_Console')->phoneLoginForm()->render(); ?>
                    </div>
                </div>
            <?php
				}else if($current == 'mail'){
				?>
				<div class="typecho-edit-theme">
                    <div class="col-mb-12 col-tb-8 col-9 content">
                        <?php Typecho_Widget::widget('TleMultiFunction_Console')->mailLoginForm()->render(); ?>
                    </div>
                </div>
			<?php
				}else if($current == 'social'){
				?>
				<div class="typecho-edit-theme">
                    <div class="col-mb-12 col-tb-8 col-9 content">
                        <?php Typecho_Widget::widget('TleMultiFunction_Console')->oAuthLoginForm()->render(); ?>
                    </div>
                </div>
			<?php
				}else if($current == 'verifycode'){
				?>
				<div class="typecho-edit-theme">
                    <div class="col-mb-12 col-tb-8 col-9 content">
                        <?php Typecho_Widget::widget('TleMultiFunction_Console')->verifyCodeForm()->render(); ?>
                    </div>
                </div>
			<?php
				}else if($current == 'userlist'){
					Typecho_Widget::widget('TleMultiFunction_Console')->to($files);
				?>
				<div class="typecho-edit-theme">
					<div class="col-mb-12 col-tb-8 col-9 content">
						<div class="typecho-list-operate clearfix">
							<form method="post" action="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=userlist'); ?>">
								<div class="search" role="search">
									<?php if ('' != $request->keywords): ?>
									<a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=userlist'); ?>"><?php _e('&laquo; 取消筛选'); ?></a>
									<?php endif; ?>
									<input type="text" class="text-s" placeholder="<?php _e('请输入关键字'); ?>" value="<?php echo htmlspecialchars($request->keywords); ?>" name="keywords" />
									<select name="oauthtype">
										<option value=""><?php _e('所有用户'); ?></option>
										<option value="qq"<?php if($request->oauthtype == "qq"): ?> selected="true"<?php endif; ?>>QQ用户</option>
										<option value="weibo"<?php if($request->oauthtype == "weibo"): ?> selected="true"<?php endif; ?>>微博用户</option>
										<option value="weixin"<?php if($request->oauthtype == "weixin"): ?> selected="true"<?php endif; ?>>微信用户</option>
										<option value="unbound"<?php if($request->oauthtype == "unbound"): ?> selected="true"<?php endif; ?>>未绑定用户</option>
									</select>
									<button type="submit" class="btn btn-s"><?php _e('筛选'); ?></button>
								</div>
							</form>
						</div>
						<div class="typecho-table-wrap">
							<table class="typecho-list-table">
								<thead>
								<tr>
									<th>&nbsp;</th>
									<th>用户名</th>
									<th>昵称</th>
									<th>头像</th>
									<th>注册时间</th>
									<th><?php _e('电子邮件'); ?></th>
									<th><?php _e('用户组'); ?></th>
									<th>操作</th>
								</tr>
								</thead>
								<tbody>
									<?php
									$keywords = isset($_POST['keywords']) ? addslashes($_POST['keywords']) : '';
									if($keywords){
										$keywords=" and (name like '%".$keywords."%' or screenName like '%".$keywords."%')";
									}
									$oauthtype = isset($_GET['oauthtype']) ? addslashes($_GET['oauthtype']) : '';
									if($oauthtype==""){
										$queryArticle= "select * from ".$db->getPrefix()."users where 1=1".$keywords;
									}else if($oauthtype=="unbound"){
										$queryArticle= "select * from ".$db->getPrefix()."users where 1=1".$keywords." and uid not in (select oauthuid from ".$db->getPrefix()."multi_oauthlogin)";
									}else{
										$oauthtype=" and oauthtype='".$oauthtype."'";
										$queryArticle= "select * from ".$db->getPrefix()."multi_oauthlogin as ol inner join ".$db->getPrefix()."users as u on ol.oauthuid = u.uid where 1=1".$keywords.$oauthtype;
									}
									$page_now = isset($_GET['page_now']) ? intval($_GET['page_now']) : 1;
									if($page_now<1){
										$page_now=1;
									}
									$resultTotal = $db->fetchAll($queryArticle);
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
									if($oauthtype==""){
										$query= "select * from ".$db->getPrefix()."users where 1=1".$keywords." order by created desc limit ".$i.",".$page_rec;
									}else if($oauthtype=="unbound"){
										$query= "select * from ".$db->getPrefix()."users where 1=1".$keywords." and uid not in (select oauthuid from ".$db->getPrefix()."multi_oauthlogin) order by created desc limit ".$i.",".$page_rec;
									}else{
										$query= "select * from ".$db->getPrefix()."multi_oauthlogin as ol inner join ".$db->getPrefix()."users as u on ol.oauthuid = u.uid where 1=1".$keywords.$oauthtype." order by u.created desc limit ".$i.",".$page_rec;
									}
									$result = $db->fetchAll($query);
									foreach($result as $value){
										$postsNum=$db->fetchObject($db->select(array('COUNT(cid)' => 'num'))
											->from('table.contents')
											->where('table.contents.type = ?', 'post')
											->where('table.contents.status = ?', 'publish')
											->where('table.contents.authorId = ?', $value['uid']))->num;
										if(isset($value['oauthtype'])){
											$oAuthAvatar=showOAuthAvatar($options,$value["oauthtype"]);
										}
										$host = 'https://secure.gravatar.com';
										$url = '/avatar/';
										$size = '50';
										$rating = 'g';
										$hash = md5(strtolower($value["mail"]));
										$avatar = $host . $url . $hash . '?s=' . $size . '&r=' . $rating . '&d=mm';
										?>
										<tr>
										  <td><a href="<?php $options->adminUrl('manage-posts.php?uid='.$value['uid']); ?>" class="balloon-button left size-<?php echo Typecho_Common::splitByCount($postsNum, 1, 10, 20, 50, 100); ?>"><?=$postsNum; ?></a></td>
										  <td><a href="<?php $options->adminUrl('user.php?uid='.$value['uid']); ?>"><?=$value['name'];?></a></td>
										  <td><?=$value['screenName'];?></td>
										  <td>
											<?php
											if($oauthtype==""){
												$queryOAuth = $db->select()->from('table.multi_oauthlogin')->where('oauthuid = ?', $value['uid']);
												$rowOAuth = $db->fetchRow($queryOAuth);
												if($rowOAuth){
													$oAuthAvatar=showOAuthAvatar($options,$rowOAuth["oauthtype"]);
													?>
													<img src="<?=$oAuthAvatar;?>" width="20" /><img src="<?=$rowOAuth['oauthfigureurl']?$rowOAuth['oauthfigureurl']:$avatar;?>" width="20" />
													<?php
												}else{
													?>
													<img src="<?=$avatar;?>" width="20" />
													<?php
												}
											}else if($oauthtype=="unbound"){
												?>
												<img src="<?=$avatar;?>" width="20" />
												<?php
											}else{
												?>
												<img src="<?=$oAuthAvatar;?>" width="20" /><img src="<?=$value['oauthfigureurl']?$value['oauthfigureurl']:$avatar;?>" width="20" />
												<?php
											}
											?>
										  </td>
										  <td><?=date('Y-m-d H:i:s',$value['created']);?></td>
										  <td><?php if($value['mail']): ?><a href="mailto:<?=$value['mail']; ?>"><?=$value['mail']; ?></a><?php else: _e('暂无'); endif; ?></td>
											<td>
												<?php
												switch ($value['group']) {
													case 'administrator':_e('管理员');break;
													case 'editor':_e('编辑');break;
													case 'contributor':_e('贡献者');break;
													case 'subscriber':_e('关注者');break;
													case 'visitor':_e('访问者');break;
													default:break;
												}
												?>
											</td>
										  <td>
											<a href="javascript:delUser('<?=$value['uid'];?>');">删除</a>
										  </td>
										</tr>
										<?php
									}
									?>
								</tbody>
							</table>
							<div class="typecho-list-operate">
								<div>共 <?=$totalrec;?> 条记录</div>
								<ul style="list-style:none" class="typecho-pager">
								  <?php if($page_now!=1){?>
									<li style="float:left;margin-right:10px;"><a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=userlist&page_now=1'); ?>">首页</a></li>
								  <?php }?>
								  <?php if($page_now>1){?>
									<li style="float:left;margin-right:10px;"><a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=userlist&page_now='.$before_page); ?>">&laquo; 上一页</a></li>
								  <?php }?>
								  <?php if($page_now<$page){?>
									<li style="float:left;margin-right:10px;"><a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=userlist&page_now='.$after_page); ?>">下一页 &raquo;</a></li>
								  <?php }?>
								  <?php if($page_now!=$page){?>
									<li style="float:left;margin-right:10px;"><a href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=userlist&page_now='.$page); ?>">尾页</a></li>
								  <?php }?>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<?php
				}
			?>
        </div>
    </div>
</div>

<?php
include 'copyright.php';
include 'common-js.php';
include 'footer.php';
?>
<script>
$("form select[name='oauthtype']").change(function(){
	location.href="<?php $options->adminUrl('extending.php?panel=' . TleMultiFunction_Plugin::$panel . '&type=userlist'); ?>&oauthtype="+$(this).val();
});
function delUser(id){
	if(confirm('确认要删除该用户吗？')){
		location.href="<?php $security->index('/action/tleMultiFunction-code?do=delUser'); ?>&id="+id;
	}
}
</script>