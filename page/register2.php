<?php
include 'common.php';

if ($user->hasLogin() || !$options->allowRegister) {
    $response->redirect($options->siteUrl);
}
$rememberName = htmlspecialchars(Typecho_Cookie::get('__typecho_remember_name'));
$rememberMail = htmlspecialchars(Typecho_Cookie::get('__typecho_remember_mail'));
Typecho_Cookie::delete('__typecho_remember_name');
Typecho_Cookie::delete('__typecho_remember_mail');

$bodyClass = 'body-100';

include 'header.php';
?>
<meta name="format-detection" content="telephone=no">
<style>
    .header {
      text-align: center;
    }
    .header h1 {
      font-size: 200%;
      color: #333;
      margin-top: 30px;
    }
    .header p {
      font-size: 14px;
    }
</style>
<div class="header">
  <div class="am-g">
    <h1><?php echo $options->title;?></h1>
    <p><?php echo $options->description;?></p>
  </div>
  <hr />
</div>
<div class="am-g typecho-login-wrap">
  <div class="am-u-lg-6 am-u-md-8 am-u-sm-centered typecho-login">
    <h3><?php _e('注册'); ?></h3>
    <hr>
    <div class="am-btn-group">
    </div>
    <br />

    <form action="<?php $options->registerAction(); ?>" method="post" name="register" role="form" class="am-form">
      <p>
		<input type="text" name="name" id="name" value="<?php echo $rememberName; ?>" placeholder="<?php _e('用户名'); ?>" autofocus />
      </p>
	  <p>
      <input type="email" name="mail" id="mail" value="" placeholder="<?php _e('Email'); ?>">
		</p>
	  <p>
      <div class="am-cf">
        <input type="submit" name="" value="<?php _e('注册'); ?>" class="am-btn am-btn-primary am-btn-sm am-fl">
      </div>
	  </p>
    </form>
	<p class="more-link">
		<a href="<?php $options->siteUrl(); ?>"><?php _e('返回首页'); ?></a>
		<a href="<?php $options->adminUrl('login.php'); ?>"><?php _e('用户登录'); ?></a>
	</p>
    <hr>
    <p>© <?php echo date("Y");?> <?php echo $options->title;?>.</p>
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
