<?php
include 'common.php';

if ($user->hasLogin() || !$options->allowRegister) {
    $response->redirect($options->siteUrl);
}
$rememberName = htmlspecialchars(Typecho_Cookie::get('__typecho_remember_name'));
$rememberMail = htmlspecialchars(Typecho_Cookie::get('__typecho_remember_mail'));
Typecho_Cookie::delete('__typecho_remember_name');
Typecho_Cookie::delete('__typecho_remember_mail');

$body_type = 'login';

include 'header.php';
?>
	<div class="am-g tpl-g">
        <!-- 风格切换 -->
        <div class="tpl-skiner">
            <div class="tpl-skiner-toggle am-icon-cog">
            </div>
            <div class="tpl-skiner-content">
                <div class="tpl-skiner-content-title">
                    选择主题
                </div>
                <div class="tpl-skiner-content-bar">
                    <span class="skiner-color skiner-white" data-color="theme-white"></span>
                    <span class="skiner-color skiner-black" data-color="theme-black"></span>
                </div>
            </div>
        </div>
        <div class="tpl-login typecho-login-wrap">
            <div class="tpl-login-content typecho-login">
                <div class="tpl-login-title">注册用户</div>
                <span class="tpl-login-content-info">
                  创建一个新的用户
				</span>
                <form class="am-form tpl-form-line-form" action="<?php $options->registerAction(); ?>" method="post" name="register" role="form">
                    <div class="am-form-group">
                        <input type="text" name="name" id="name" value="<?php echo $rememberName; ?>" placeholder="<?php _e('用户名'); ?>" autofocus />
                    </div>
					<div class="am-form-group">
						<input type="email" class="tpl-form-input" name="mail" id="mail" value="" placeholder="<?php _e('Email'); ?>">
                    </div>
                    <div class="am-form-group">
                        <button type="submit" class="am-btn am-btn-primary  am-btn-block tpl-btn-bg-color-success  tpl-login-btn"><?php _e('注册'); ?></button>
                    </div>
                </form>
				<p class="more-link">
					<a href="<?php $options->siteUrl(); ?>"><?php _e('返回首页'); ?></a>
					&bull;
					<a href="<?php $options->adminUrl('login.php'); ?>"><?php _e('用户登录'); ?></a>
				</p>
				<hr>
				<p>© <?php echo date("Y");?> <?php echo $options->title;?>.</p>
            </div>
        </div>
    </div>
<?php 
include 'common-js.php';
?>
<script>
$(document).ready(function () {
    $('#name').focus();
});
</script>
<?php
include 'footer.php';
?>