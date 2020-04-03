<?php
/**
 * 多功能-百度链接提交
 *
 * @package custom
 */
?>
<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
if (!$this->user->pass('administrator')) exit;
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
if($tleMultiFunction['baidu_submit']=='n'){
	die('未启用百度链接提交功能');
}

/*
$setuser_config=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/'.$pluginsname.'/config/setuser_config.php'),'<?php die; ?>'));
$result=checkUser($setuser_config['username'],$setuser_config['password'],$setuser_config['access_token']);
switch($result){
	case 0:
		die('服务器验证错误');
		break;
	case 101:
		die('登录用户名不存在');
		break;
	case 102:
		die('登录密码错误');
		break;
	case 103:
		die('token不存在');
		break;
	case 104:
		die('token已过期');
		break;
}
*/

$setbaidusubmit=@unserialize(ltrim(file_get_contents(dirname(__FILE__).'/../../plugins/'.$pluginsname.'/config/setbaidusubmit.php'),'<?php die; ?>'));
$action = isset($_POST['action']) ? addslashes(trim($_POST['action'])) : '';
if($action=='setbaidusubmit'){
	$url = isset($_POST['url']) ? addslashes(trim($_POST['url'])) : '';
	$linktoken = isset($_POST['linktoken']) ? addslashes(trim($_POST['linktoken'])) : '';
	$appid = isset($_POST['appid']) ? addslashes(trim($_POST['appid'])) : '';
	$resctoken = isset($_POST['resctoken']) ? addslashes(trim($_POST['resctoken'])) : '';
	if($url&&$linktoken){	
		if(get_magic_quotes_gpc()){
			$url=stripslashes($url);
			$linktoken=stripslashes($linktoken);
			$appid=stripslashes($appid);
			$resctoken=stripslashes($resctoken);
		}
		file_put_contents(dirname(__FILE__).'/../../plugins/'.$pluginsname.'/config/setbaidusubmit.php','<?php die; ?>'.serialize(array(
			'url'=>$url,
			'linktoken'=>$linktoken,
			'appid'=>$appid,
			'resctoken'=>$resctoken
		)));
	}
}
if(strpos($this->permalink,'?')){
	$pageurl=substr($this->permalink,0,strpos($this->permalink,'?'));
}else{
	$pageurl=$this->permalink;
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/css/amazeui.min.css"/>
<script src="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/js/amazeui.min.js" type="text/javascript"></script>
<script src="https://apps.bdimg.com/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<!-- content section -->
<section>
	<div class="am-g">
	  <div class="am-u-md-8 am-u-sm-centered">
		<form class="am-form" action="" method="post">
		  <fieldset class="am-form-set">
			<input type="text" id="url" name="url" value="<?php if(@$url!=''){echo $url;}else{echo @$setbaidusubmit['url'];} ?>" placeholder="<?php _e('站点链接'); ?>" required>
			<input type="text" id="linktoken" name="linktoken" value="<?php if(@$linktoken!=''){echo $linktoken;}else{echo @$setbaidusubmit['linktoken'];} ?>" placeholder="<?php _e('站点token'); ?>" required>
			<input type="text" id="appid" name="appid" value="<?php if(@$appid!=''){echo $appid;}else{echo @$setbaidusubmit['appid'];} ?>" placeholder="<?php _e('熊掌号appid'); ?>">
			<input type="text" id="resctoken" name="resctoken" type="text" value="<?php if(@$resctoken!=''){echo $resctoken;}else{echo @$setbaidusubmit['resctoken'];} ?>" placeholder="<?php _e('熊掌号token'); ?>">
		  </fieldset>
		  <input type="hidden" name="action" value="setbaidusubmit" />
		  <button id="setbaidusubmit" type="button" class="am-btn am-btn-primary am-btn-block"><?php _e('修改设置'); ?></button>
		</form>
	  </div>
	</div>
	
	<div class="am-scrollable-horizontal">
	  <table class="am-table am-table-bordered am-table-striped am-text-nowrap baidusubmittable">
		<thead>
		  <tr>
			<th>文章</th>
			<th>提交网址状态</th>
			<th>提交熊掌号状态</th>
			<th>操作网址</th>
			<th>操作熊掌号</th>
		  </tr>
		  </thead>
		  <tbody>
		<?php
		$queryTotal = $this->db->select()->from('table.contents')->join('table.multi_baidusubmit', 'table.contents.cid = table.multi_baidusubmit.bscid',Typecho_Db::LEFT_JOIN)->where('table.contents.status != ?', 'hidden');
		$rowsTotal = $this->db->fetchAll($queryTotal);
		$page_now = isset($_GET['page_now']) ? intval($_GET['page_now']) : 1;
		if($page_now<1){
			$page_now=1;
		}
		$page_rec=$this->parameter->pageSize;
		$totalrec=count($rowsTotal);
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
		$query = $this->db->select()->from('table.contents')->join('table.multi_baidusubmit', 'table.contents.cid = table.multi_baidusubmit.bscid',Typecho_Db::LEFT_JOIN)->where('table.contents.status != ?', 'hidden')->order('modified',Typecho_Db::SORT_DESC)->offset($i)->limit($page_rec);
		$rows = $this->db->fetchAll($query);
		if(count($rows)>0){
			foreach($rows as $row){
				$val = Typecho_Widget::widget('Widget_Abstract_Contents')->push($row);
				$permalink=str_replace('{cid}',$row['cid'],$val['permalink']);
			?>
			  <tr>
				<td><a href="<?=$permalink;?>"><?=$row['title'];?></a></td>
				<td>
					<?php
					if($row['linkstatus']==''){
						echo '未提交';
					}else if($row['linkstatus']==200){
						echo '<font color="green">成功</font>('.$row['instime'].')';
					}else if($row['linkstatus']!=200&&$row['linkstatus']!=''){
						echo '<font color="red">失败</font>('.$row['instime'].')<font color="red">'.$row['error'].'</font>';
					}
					?>
				</td>
				<td>
					<?php
					if($row['rescstatus']==''){
						echo '未提交';
					}else if($row['rescstatus']==200){
						echo '<font color="green">成功</font>('.$row['instime'].')';
					}else if($row['rescstatus']!=200&&$row['rescstatus']!=''){
						echo '<font color="red">失败</font>('.$row['instime'].')<font color="red">'.$row['error'].'</font>';
					}
					?>
				</td>
				<td>
					<?php
					if($row['linkstatus']==''){
						echo '<a href="javascript:;"><span class="baidusubmit" id="baidusubmit'.$row['cid'].'" data-url="'.$permalink.'" data-cid="'.$row['cid'].'" data-pluginsname="'.$pluginsname.'">提交网址</span></a>';
					}else{
						echo '<a href="javascript:;"><span class="baidusubmit" id="baidusubmit'.$row['cid'].'" data-url="'.$permalink.'" data-cid="'.$row['cid'].'" data-pluginsname="'.$pluginsname.'"><font color="red">再次提交网址</font></span></a>';
					}
					?>
				</td>
				<td class="mdui-table-col-numeric">
					<?php
					if($row['rescstatus']==''){
						echo '&nbsp;&nbsp;<a href="javascript:;"><span class="baiduziyuansubmit" id="baiduziyuansubmit'.$row['cid'].'" data-url="'.$permalink.'" data-cid="'.$row['cid'].'" data-pluginsname="'.$pluginsname.'">提交熊掌号</span></a>';
					}else{
						echo '&nbsp;&nbsp;<a href="javascript:;"><span class="baiduziyuansubmit" id="baiduziyuansubmit'.$row['cid'].'" data-url="'.$permalink.'" data-cid="'.$row['cid'].'" data-pluginsname="'.$pluginsname.'"><font color="red">再次提交熊掌号</font></span></a>';
					}
					?>
				</td>
			  </tr>
			<?php
			}
		}else{
		?>
		<tr><td class="tdcenter" colspan="5">暂无文章/页面记录</td></tr>
		<?php
		}
		?>
		</tbody>
	  </table>
	</div>
	<ul class="am-pagination blog-pagination">
	  <?php if($page_now!=1){?>
		<li class="am-pagination-prev"><a href="<?=$pageurl;?>?page_now=1">首页</a></li>
	  <?php }?>
	  <?php if($page_now>1){?>
		<li class="am-pagination-prev"><a href="<?=$pageurl;?>?page_now=<?=$before_page;?>">&laquo; 上一页</a></li>
	  <?php }?>
	  <?php if($page_now<$page){?>
		<li class="am-pagination-next"><a href="<?=$pageurl;?>?page_now=<?=$after_page;?>">下一页 &raquo;</a></li>
	  <?php }?>
	  <?php if($page_now!=$page){?>
		<li class="am-pagination-next"><a href="<?=$pageurl;?>?page_now=<?=$page;?>">尾页</a></li>
	  <?php }?>
	</ul>
</section>
<!-- end content section -->

<script>
$("#setbaidusubmit").click(function(){
	if($("#url").val()==''||$("#linktoken").val()==''){
		alert('至少要配置上站点链接和站点token');
		return; 
	}
	$('form').submit();
});
$(".baidusubmittable .baidusubmit").each(function(){
	var id=$(this).attr("id")
	$("#"+id).click( function () {
		$.post("<?php $this->options->siteUrl(); ?>usr/plugins/<?=$pluginsname;?>/ajax/baidusubmit.php",{action:'baidusubmit',url:$(this).attr('data-url'),cid:$(this).attr('data-cid'),pluginsname:$(this).attr('data-pluginsname')},function(data){
			if(data==-1){
				alert('提交失败');
			}else if(data!=''){
				alert(data);
			}
			window.location.href='';
		});
	});
});
$(".baidusubmittable .baiduziyuansubmit").each(function(){
	var id=$(this).attr("id")
	$("#"+id).click( function () {
		$.post("<?php $this->options->siteUrl(); ?>usr/plugins/<?=$pluginsname;?>/ajax/baidusubmit.php",{action:'baiduziyuansubmit',url:$(this).attr('data-url'),cid:$(this).attr('data-cid'),pluginsname:$(this).attr('data-pluginsname')},function(data){
			if(data==-1){
				alert('提交失败');
			}else if(data!=''){
				alert(data);
			}
			window.location.href='';
		});
	});
});
</script>