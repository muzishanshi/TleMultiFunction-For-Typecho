<?php if (!defined('__TYPECHO_ROOT_DIR__')) exit; ?>
<?php
if (!defined('__DIR__')) {
    define('__DIR__', dirname(__FILE__));
}

define('__TYPECHO_ADMIN__', true);
define('__ADMIN_DIR__', __TYPECHO_ROOT_DIR__ . __TYPECHO_ADMIN_DIR__);

/** 初始化组件 */
Typecho_Widget::widget('Widget_Init');
Typecho_Widget::widget('Widget_Options')->to($options);
Typecho_Widget::widget('Widget_Security')->to($security);
Typecho_Widget::widget('Widget_Menu')->to($menu);

list($prefixVersion, $suffixVersion) = explode('/', $options->version);

$menu->title = _t('找回密码');

include __ADMIN_DIR__ . '/header.php';

$options = Typecho_Widget::widget('Widget_Options');
$plug_url = $options->pluginUrl;
$get=TleMultiFunction_Plugin::getOptions();
?>
<style>
    body {
        font-family: "Microsoft YaHei", tahoma, arial, 'Hiragino Sans GB', '\5b8b\4f53', sans-serif;
    }
    .typecho-logo {
        margin: 50px 0 30px;
        text-align: center;
    }
    .typecho-table-wrap {
        padding: 50px 30px;
    }
    .typecho-page-title h2 {
        margin: 0 0 30px;
        font-weight: 500;
        font-size: 20px;
        text-align: center;
    }
    label:after {
        content: " *";
        color: #ed1c24;
    }
    .btn {
        width: 100%;
        height: auto;
        padding: 10px 16px;
        font-size: 18px;
        line-height: 1.33;
    }
</style>
<div class="body container">
    <div class="typecho-logo">
        <h1><a href="<?php $options->siteUrl(); ?>"><?php $options->title(); ?></a></h1>
    </div>

    <div class="row typecho-page-main">
        <div class="col-mb-12 col-tb-6 col-tb-offset-3 typecho-content-panel">
            <div class="typecho-table-wrap">
                <div class="typecho-page-title">
                    <h2>找回密码</h2>
                </div>
                <?php @$this->forgotForm()->render(); ?>
				<ul class="typecho-option" id="typecho-option-item-phone">
					<li>
						<label class="typecho-label" for="phone">手机号</label>
						<input id="phone" name="phone" type="text" class="text" />
						<p class="description">账号对应的(注册时的)手机号</p>
					</li>
					<li>
						<label class="typecho-label" for="smscode">手机验证码</label>
						<input id="smscode" name="smscode" type="text" class="text" />
						<p class="description">
							<button id="sendsmscode" class="btn">发送验证码</button>
						</p>
					</li>
				</ul>
				<ul class="typecho-option typecho-option-submit" id="typecho-option-item-submit">
					<?php
					$geetestSet=unserialize(@$get["enableGeetest"]);
					if($geetestSet&&in_array("forgot",$geetestSet)){
					?>
					<li>
						<div id="embed-captcha"></div>
					</li>
					<?php
					}
					?>
					<li>
						<button id="findpwdbyphone" type="submit" class="btn primary">通过手机找回</button>
					</li>
				</ul>
            </div>
        </div>
    </div>
</div>
<?php
include __ADMIN_DIR__ . '/common-js.php';
?>
<script>
$.getScript("<?=$plug_url;?>/TleMultiFunction/assets/js/gt.js");
/*限制键盘只能按数字键、小键盘数字键、退格键*/
$("#smscode").keyup(function(){
	$("#smscode").val($("#smscode").val().replace(/[^\d.]/g,""));
	$("#smscode").val($("#smscode").val().replace(/\.{2,}/g,"."));
	$("#smscode").val($("#smscode").val().replace(/^\./g,""));
	$("#smscode").val($("#smscode").val().replace(".","$#$").replace(/\./g,"").replace("$#$","."));
	$("#smscode").val($("#smscode").val().replace(/^(\-)*(\d+)\.(\d\d).*$/,"$1$2.$3"));
});
/*发送手机验证码*/
$("#sendsmscode").click(function(){
	var phone=$("#phone").val();
	$.post("<?=$plug_url;?>/TleMultiFunction/ajax/sendsms_new.php",{action:"phone",name:phone},function(data){
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
/*通过手机找回(geetest验证)*/
var handlerEmbed = function (captchaObj) {
	captchaObj.appendTo("#embed-captcha");
	$("#findpwdbyphone").click(function(){
		var validate = captchaObj.getValidate();
		<?php
		$geetestSet=unserialize(@$get["enableGeetest"]);
		if($geetestSet&&in_array("forgot",$geetestSet)){
		?>
		if (!validate) {
			alert("请先完成验证");
			return false;
		}
		<?php
		}
		?>
		var phone=$("#phone").val();
		var smscode=$("#smscode").val();
		var indexUrl="<?=$options->index;?>";
		$.post("<?=$plug_url;?>/TleMultiFunction/ajax/sendsms_new.php",{submit:"phone",action:"phone",name:phone,smscode:smscode,indexUrl:indexUrl},function(data){
			var data=JSON.parse(data);
			if(data.error_code==0){
				location.href=data.url;
			}else{
				alert(data.message);
			}
		});
	});
};
$.ajax({
	url: "<?=$plug_url;?>/TleMultiFunction/ajax/geetest.php?action=init&t=" + (new Date()).getTime(),/*加随机数防止缓存*/
	type: "get",
	dataType: "json",
	success: function (data) {
		console.log(data);
		initGeetest({
			gt: data.gt,
			challenge: data.challenge,
			new_captcha: data.new_captcha,
			product: "embed", /*产品形式，包括：float，embed，popup。注意只对PC版验证码有效*/
			width:$("#findpwdbyphone").parent().width()+"px",
			offline: !data.success
		}, handlerEmbed);
	}
});
</script>
<?php
include __ADMIN_DIR__ . '/footer.php';
?>
