<?php
/**
 * 多功能-短网址缩短
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
if($tleMultiFunction['dwz']=='n'){
	die('未启用短网址功能');
}

$setuser_config=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/'.$pluginsname.'/config/setuser_config.php'),'<?php die; ?>'));
?>

<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=no">
	<meta name="generator" content="Myurl" />
	<title>短网址_短链接生成_网址缩短工具</title>
    <meta name="keywords" content="缩短网址,网址压缩,网址缩短,短网址,短域名,短地址,短URL,免费缩短网址,短链接生成器,短网址生成,免费缩址,域名伪装,域名转向,网站推广,短链接,长网址变短网址">
	<meta name="description" content="免费专业的网址缩短服务,在线生成短网址,开放API接口,可批量生成短链接,不限制接口请求数,跳转快,稳定可靠,防屏蔽,防拦截!">
	<link rel="alternate icon" href="https://www.tongleer.com/wp-content/themes/D8/img/favicon.png" type="image/png" /><link rel="shortcut icon" href="https://www.tongleer.com/wp-content/themes/D8/img/favicon.ico" type="image/x-icon">
	<link rel="stylesheet" href="https://css.letvcdn.com/lc04_yinyue/201612/19/20/00/bootstrap.min.css">
	<script src="https://apps.bdimg.com/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
	<style type="text/css"></style>
	<style type="text/css">
		*{margin:0;padding:0}
		body,form,html{height:100%;}
		.row{padding-top:120px;min-height:70%}
		.footer{text-align:center;width: 80%;margin: 0 auto;}
		.footer a{color: #009688;}
		.page-header{margin-bottom:40px;text-align: right;}
		.expand-transition{-webkit-transition:all .5s ease;transition:all .5s ease}
		.expand-enter,.expand-leave{opacity:0}

		@media (max-width:768px){.h3-xs{font-size:20px}
			.row-xs{padding-top:70px}
		}
		.modal{display:block}
		.alert.top{position:fixed;top:30px;margin:0 auto;left:0;right:0;width:50%;z-index:1050}
		@media (max-width:768px){.alert.top-xs{width:80%}
		}
		.en-markup-crop-options{top:18px!important;left:50%!important;margin-left:-100px!important;width:200px!important;border:2px rgba(255,255,255,.38) solid!important;border-radius:4px!important}
		.en-markup-crop-options div div:first-of-type{margin-left:0!important}
	</style>
</head>
<body>
<section>
	<div id="app" class="container">
		<div class="alert top top-xs alert-dismissible alert-danger expand-transition" style="display:none" id="error-tips">

		</div>
		<div class="row row-xs">
			<div class="col-lg-6 col-md-6 col-sm-6 col-xs-10 col-xs-offset-1 col-sm-offset-3 col-md-offset-3 col-lg-offset-3">
				<div class="page-header">
                  <h3 class="text-center h3-xs">短网址生成工具</h3><span>带检测报毒的短网址</span>
				</div>
				<div class="form-group " id="input-wrap"> 
					<label class="control-label" for="inputContent">请输入长网址:</label> 
					<textarea type="text" id="longurls" class="form-control" placeholder="请输入带http://(https://)的地址，每行一个"></textarea>
					<label class="control-label" for="inputContent">自定义短址:</label>
					<input id="customdwz" type="text" maxlength="6"/>
					<label class="control-label" for="inputContent">
					  <input id="sinasite" type="checkbox" value="sina"/>新浪
					</label>
					<label class="control-label" for="inputContent">
					  <input id="baidusite" type="checkbox" value="baidu"/>百度
					</label>
					<label class="control-label" for="inputContent">
					  <input id="isred" type="checkbox"/>防红
					</label>
				</div>
				<div class="text-right">
					<div class="input_group_addon btn btn-primary" id="shortgo" data-url="<?php $this->options->siteUrl(); ?>">缩短网址</div>
				</div>
			</div>
			<div class="modal expand-transition" id="dwzresultcontainer" style="display:none;">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header"> <button type="button" class="close" data-dismiss="modal" onclick="closeWrapper()" aria-hidden="true">×</button>
							<h4 class="modal-title">生成成功！</h4>
						</div>
						<div class="modal-body">
							<div class="form-group" id="dwzresultcontent">
								
							</div>
						</div>
						<div class="modal-footer">
							
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</section>
<div class="footer" style="color:#888;">
		<p>
			
		</p>
		声明：本站永久免费，短网址均由用户生成，所跳转网站均与本站无关，亦不承担法律责任!<br>
		© Copyright 2018 <a href="http://www.tongleer.com">同乐儿</a> - All Rights Reserved. 
</div>
<script>
function closeWrapper(){
	$('#dwzresultcontainer').css('display','none');
}
/*用中文分号替换英文分号、中英文逗号或者回车，以达到判断textarea中的回车换行。*/
function ReplaceSeperator(mobiles) {
	var i;
	var result = "";
	var c;
	for (i = 0; i < mobiles.length; i++) {
		c = mobiles.substr(i, 1);
		if (c == ";" || c == "," || c == "，" || c == "\n")
			result = result + ",";
		else if (c != "\r")
			result = result + c;
	}
	return result;
}
$("#shortgo").click(function(){
	/*长网址*/
	var longurls=$("#longurls").val();
	if(longurls==''){
		alert('请填写要缩短的长网址');
		return;
	}
	var longurlarr=ReplaceSeperator(longurls).split(',');
	for(var i=0;i<longurlarr.length;i++){
		var Expression=/http(s)?:\/\/([\w-]+\.)+[\w-]+(\/[\w- .\/?%&=]*)?/;
		var objExp=new RegExp(Expression);
		if(objExp.test(longurlarr[i]) != true){
			alert("长网址格式不正确！");
			return;
		}
	}
	/*自定义短址*/
	var customdwz=$('#customdwz').val();
	if(customdwz!=''&&ReplaceSeperator(longurls).indexOf(',')!=-1){
		alert('自定义短址只能输入一行长网址');
		return;
	}
	if (escape(customdwz).indexOf( "%u" )!=-1){
		alert( "不支持的自定义短址类型" );
		return;
	}
	/*缩短类型*/
	var type,selfsite,baidusite,sinasite;
	selfsite='default';
	if($('#baidusite').is(':checked')){baidusite=$('#baidusite').val();}else{baidusite='';}
	if($('#sinasite').is(':checked')){sinasite=$('#sinasite').val();}else{sinasite='';}
	type=selfsite+','+baidusite+','+sinasite;
	if(type.substring(0,1)==','){
		type=type.substring(1);
	}
	if(type.substring(type.length-1)==','){
		type=type.substring(0,type.length-1);
	}
	/*防红*/
	var isred;
	if($('#isred').is(':checked')){isred='y';}else{isred='n';}
	$.post("<?php $this->options->siteUrl(); ?>usr/plugins/<?=$pluginsname;?>/ajax/dwz.php",{action:"shorturl",longurls:ReplaceSeperator(longurls),isred:isred,customdwz:customdwz,type:type,domain:$(this).attr('data-url')},function(data){
		if(data==-1){
			$('#dwzresultcontainer').css('display','block');
			$("#dwzresultcontent").html('权限不足');
		}else if(data==106){
			$('#dwzresultcontainer').css('display','block');
			$("#dwzresultcontent").html('自定义短址已存在');
		}else{
			$('#dwzresultcontainer').css('display','block');
			$("#dwzresultcontent").html('');
			var arr=data.split('|');
			var dwz=arr[1].split(',');
			for(var i=0;i<dwz.length;i++){
				var url=dwz[i];
				if(url.indexOf('http')==-1){
					if(url==-1||url==''){
						url='不支持';
					}else{
						url=arr[0]+url;
					}
				}
				$("#dwzresultcontent").append(url+'<br />');
			}
		}
	});
});
</script>
</body>
</html>