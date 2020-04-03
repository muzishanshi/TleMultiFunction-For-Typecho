<?php
/**
 * 多功能-论坛
 *
 * @package custom
 */
?>
<?php
date_default_timezone_set('Asia/Shanghai');
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
if($tleMultiFunction['bbs']=='n'){
	die('未启用论坛功能');
}
?>
<!DOCTYPE html>
<html>
<head lang="en">
  <meta charset="UTF-8">
  <title><?php $this->archiveTitle(array('category'=>_t(' %s '),'search'=>_t(' %s '),'tag'=>_t(' %s '),'author'=>_t(' %s ')),'',' - ');?><?php $this->options->title();?></title>
  <meta name="description" itemprop="description" content="<?php $this->options->description(); ?>">
  <meta name="keywords" content="<?php $this->options->keywords(); ?>">
  <meta name="author" content="<?php $this->options->title(); ?>">
  <meta http-equiv="x-dns-prefetch-control" content="on">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="format-detection" content="telephone=no">
  <meta name="renderer" content="webkit">
  <meta http-equiv="Cache-Control" content="no-siteapp"/>
  <!--iOS -->
  <meta name="apple-mobile-web-app-title" content="Title">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
  <meta name="HandheldFriendly" content="True">
  <meta name="MobileOptimized" content="480">
  <meta property="og:url" content="<?php $this->permalink(); ?>" />
  <meta property="og:type" content="blog" />
  <meta property="og:release_date" content="<?php $this->date('Y-m-j'); ?>" />
  <meta property="og:title" content="<?php $this->options->title(); ?>" />
  <meta property="og:description" content="<?php $this->description() ?>" />
  <meta property="og:author" content="<?php $this->author(); ?>" />
  <meta property="article:published_time" content="<?php $this->date('Y-m-j'); ?>" />
  <meta property="article:modified_time" content="<?php $this->date('Y-m-j'); ?>" />
  <link rel="alternate icon" href="https://www.tongleer.com/wp-content/themes/D8/img/favicon.png" type="image/png" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/css/amazeui.min.css"/>
  <style>
    @media only screen and (min-width: 1200px) {
      .blog-g-fixed {
        max-width: 1200px;
      }
    }
    @media only screen and (min-width: 641px) {
      .blog-sidebar {
        font-size: 1.4rem;
      }
    }
    .blog-main {
      padding: 20px 0;
    }
    .blog-title {
      margin: 10px 0 20px 0;
    }
    .blog-meta {
      font-size: 14px;
      margin: 10px 0 20px 0;
      color: #222;
    }
    .blog-meta a {
      color: #27ae60;
    }
    .blog-pagination a {
      font-size: 1.4rem;
    }
    .blog-team li {
      padding: 4px;
    }
    .blog-team img {
      margin-bottom: 0;
    }
    .blog-content img,
    .blog-team img {
      max-width: 100%;
      height: auto;
    }
    .blog-footer {
      padding: 10px 0;
      text-align: center;
    }
  </style>
</head>
<body>
<?php
if(strpos($this->permalink,'?')){
	$url=substr($this->permalink,0,strpos($this->permalink,'?'));
}else{
	$url=$this->permalink;
}
$action = isset($_POST['action']) ? addslashes($_POST['action']) : '';
if($action=='login'){
	$name = isset($_POST['name']) ? addslashes($_POST['name']) : '';
	$pass = isset($_POST['pass']) ? addslashes($_POST['pass']) : '';
	if($name&&$pass){
		if(!$this->user->login($name,$pass)){
			/** 防止穷举,休眠3秒 */
            sleep(3);
            $this->response->goBack('?referer=' . urlencode($this->request->referer));
		}
        $this->pluginHandle()->loginSucceed($this->user, $this->request->name,
        $this->request->password, 1 == $this->request->remember);
        /** 跳转验证后地址 */
        if (NULL != $this->request->referer) {
            $this->response->redirect($this->request->referer);
        } else {
			$this->response->redirect($url);
        }
	}
}else if($action=='logout'){
	$this->user->logout();
	$this->pluginHandle()->logout();
	$this->response->goBack(NULL, $this->options->index);
	@session_destroy();
	exit;
}else if($action=='publish'){
	$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
	$mid = isset($_POST['mid']) ? intval($_POST['mid']) : 0;
	$title = isset($_POST['title']) ? addslashes(trim($_POST['title'])) : '';
	$text = isset($_POST['text']) ? addslashes(trim($_POST['text'])) : '';
	$text=$text=='<p>&nbsp;</p>'?'':$text;
	$ip=getIP();
	if($title!=''&&$text!=''){
		if(@$_COOKIE['publish:'.$ip]!='a'){
			$insertData=array(
				'title' => $title,
				'created' => $this->options->time,
				'modified' => $this->options->time,
				'text' => $text,
				'authorId' => Typecho_Cookie::get('__typecho_uid'),
				'type' => 'post',
				'status' => 'hidden',
				'allowComment' => 1,
				'allowPing' => 1,
				'allowFeed' => 1
			);
			$insert = $this->db->insert('table.contents')->rows($insertData);
			$insertId = $this->db->query($insert);
			$insertData=array(
				'cid' => $insertId,
				'mid' => $mid
			);
			$insert = $this->db->insert('table.relationships')->rows($insertData);
			$this->db->query($insert);
			setcookie('publish:'.$ip,time(),time()+60);
			if($id!=0){
				$id='?id='.$insertId;
			}else{
				$id='';
			}
			echo "<script>window.location.href='".$url.$id."';</script>";
		}else{
			echo "<script>alert('操作太频繁了~');</script>";
		}
	}
}else if($action=='reply'){
	$cid = isset($_GET['id']) ? addslashes(trim($_GET['id'])) : '';
	$author = isset($_POST['author']) ? addslashes(trim($_POST['author'])) : '';
	$mail = isset($_POST['mail']) ? addslashes(trim($_POST['mail'])) : '';
	$replyurl = isset($_POST['replyurl']) ? addslashes(trim($_POST['replyurl'])) : '';
	$replyfloor = isset($_POST['replyfloor']) ? addslashes(trim($_POST['replyfloor'])) : '';
	$text = isset($_POST['text']) ? addslashes(trim($_POST['text'])) : '';
	$text=$text=='<p>&nbsp;</p>'?'':$text;
	$ip=getIP();
	$checkForm=false;
	if($this->user->hasLogin()){
		if($text==''){
			$checkForm=true;
		}
	}else{
		if($author==''||$mail==''||$text==''){
			$checkForm=true;
		}
	}
	if(!$checkForm){
		if(@$_COOKIE['reply:'.$ip]==''){
			$queryContents= $this->db->select('*,table.contents.created as tcreated')->from('table.contents')->join('table.users', 'table.contents.authorId = table.users.uid',Typecho_Db::INNER_JOIN)->where('cid = ?', $cid);
			$rowContents = $this->db->fetchRow($queryContents);
			$ownerId=$rowContents['authorId'];	
			if($this->user->hasLogin()){
				$queryUser= $this->db->select()->from('table.users')->where('uid = ?', Typecho_Cookie::get('__typecho_uid'));
				$rowUser = $this->db->fetchRow($queryUser);
				$author=$rowUser['screenName'];
				$mail=$rowUser['mail'];
				$replyurl=$rowUser['url'];
			}
			$insertData=array(
				'cid' => $cid,
				'created' => $this->options->time,
				'author' => $author,
				'authorId' => Typecho_Cookie::get('__typecho_uid'),
				'ownerId' => $ownerId,
				'mail' => $mail,
				'url' => $replyurl,
				'ip' => $ip,
				'agent' => $_SERVER['HTTP_USER_AGENT'],
				'text' => $text,
				'type' => 'comment',
				'status' => 'approved',
				'parent' => $replyfloor
			);
			$insert = $this->db->insert('table.comments')->rows($insertData);
			$insertId = $this->db->query($insert);
			$update = $this->db->update('table.contents')->rows(array('commentsNum'=>$rowContents['commentsNum']+1))->where('cid=?',$cid);
			$updateRows= $this->db->query($update);
			setcookie('reply:'.$ip,time(),time()+60);
			echo "<script>window.location.href='';</script>";
		}else{
			echo "<script>alert('休息一会再回复吧~');</script>";
		}
	}
}
?>
<header class="am-topbar">
  <h1 class="am-topbar-brand">
    <a href="<?=$url;?>"><?php $this->options->title();?>论坛</a>
  </h1>

  <button class="am-topbar-btn am-topbar-toggle am-btn am-btn-sm am-btn-success am-show-sm-only"
          data-am-collapse="{target: '#doc-topbar-collapse'}"><span class="am-sr-only">导航切换</span> <span
      class="am-icon-bars"></span></button>

  <div class="am-collapse am-topbar-collapse" id="doc-topbar-collapse">
    <ul class="am-nav am-nav-pills am-topbar-nav">
      <li class="am-active"><a href="<?=$url;?>">首页</a></li>
	  <?php $this->widget('Widget_Metas_Category_List')->to($categorys); ?>
	  <?php while($categorys->next()): ?>
	  <?php if ($categorys->levels === 0): ?>
	  <?php $children = $categorys->getAllChildren($categorys->mid); ?>
	  <?php if (empty($children)) { ?>
		<li class="am-dropdown" data-am-dropdown>
			<a href="<?php echo $url.'?mid='.$categorys->mid; ?>" title="<?php $categorys->name(); ?>">
			  <?php $categorys->name(); ?>
			</a>
		</li>
	  <?php } else { ?>
			<li class="am-dropdown" data-am-dropdown>
			<a class="am-dropdown-toggle" data-am-dropdown-toggle href="javascript:;">
			  <?php $categorys->name(); ?> <span class="am-icon-caret-down"></span>
			</a>
			<ul class="am-dropdown-content">
				<li class="am-dropdown-header">板块</li>
				<?php foreach ($children as $mid) { ?>
					<?php $child = $categorys->getCategory($mid); ?>
					<li><a href="<?php echo $url.'?mid='.$mid; ?>" title="<?php echo $child['name']; ?>"><?php echo $child['name']; ?></a></li>
				<?php }?>
			</ul>
			</li>
	  <?php } ?>
	  <?php endif; ?>
      <?php endwhile; ?>
    </ul>
	
	<div class="am-topbar-right am-form-inline am-topbar-form">
	<?php if($this->user->hasLogin()): ?>
		<div class="am-dropdown" data-am-dropdown>
		  <button class="am-btn am-btn-success am-dropdown-toggle am-btn-sm" data-am-dropdown-toggle><?php echo $this->user->name();?> <span class="am-icon-caret-down"></span></button>
		  <ul class="am-dropdown-content">
			<li class="am-dropdown-header">菜单</li>
			<li><a href="<?php $this->options->adminUrl(); ?>">用户中心</a></li>
			<li><a id="logout" href="javascript:;">退出</a></li>
		  </ul>
		</div>
	<?php else: ?>
		<button
		  type="button"
		  class="am-btn am-btn-success"
		  id="login-prompt-toggle">
		  登录
		</button>
		<div class="am-modal am-modal-prompt" tabindex="-1" id="login-prompt">
		  <div class="am-modal-dialog">
			<form class="am-form" id="loginForm" method="post" action="<?=$url;?>">
			<div class="am-modal-hd">登录</div>
			<div class="am-modal-bd">
			  <a href="<?php $this->options->adminUrl(); ?>register.php">新用户注册</a>
			  <fieldset class="am-form-set">
			  <input type="text" name="name" class="am-modal-prompt-input" placeholder="用户名">
			  <input type="text" name="pass" class="am-modal-prompt-input" placeholder="密码">
			  <input type="hidden" name="action" class="am-modal-prompt-input" value="login">
			  </fieldset>
			</div>
			<div class="am-modal-footer">
			  <span class="am-modal-btn" data-am-modal-cancel>取消</span>
			  <span class="am-modal-btn" data-am-modal-confirm>登录</span>
			</div>
			</form>
		  </div>
		</div>
	<?php endif; ?>
	</div>
	
	<form class="am-topbar-form am-topbar-left am-form-inline am-topbar-right" role="search" method="post" action="<?=$url;?>">
      <div class="am-form-group">
        <input type="text" name="keyword" class="am-form-field am-input-sm" placeholder="搜索文章">
      </div>
      <button type="submit" class="am-btn am-btn-default am-btn-sm">搜索</button>
    </form>

  </div>
</header>

<div class="am-g am-g-fixed blog-g-fixed">
  <?php
  $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
  if($id==0){
  ?>
  <div class="am-u-md-8">
	<?php
	$keyword = isset($_POST['keyword']) ? addslashes($_POST['keyword']) : '';
	if($keyword!=''){
		$keyword=" and (title like '%".$keyword."%' or text like '%".$keyword."%' or ".$this->db->getPrefix()."relationships.cid in (select cid from ".$this->db->getPrefix()."comments where text like '%".$keyword."%'))";
	}
	$mid = isset($_GET['mid']) ? intval($_GET['mid']) : 0;
	if($mid==0){
		$queryTotal= "select * from ".$this->db->getPrefix()."contents left join ".$this->db->getPrefix()."relationships on ".$this->db->getPrefix()."contents.cid = ".$this->db->getPrefix()."relationships.cid where ".$this->db->getPrefix()."contents.type='post'".$keyword;
	}else{
		$queryTotal= "select * from ".$this->db->getPrefix()."contents left join ".$this->db->getPrefix()."relationships on ".$this->db->getPrefix()."contents.cid = ".$this->db->getPrefix()."relationships.cid where ".$this->db->getPrefix()."contents.type='post' and ".$this->db->getPrefix()."relationships.mid=".$mid.$keyword;
	}
	$page_now = isset($_GET['page_now']) ? intval($_GET['page_now']) : 1;
	if($page_now<1){
		$page_now=1;
	}
	$resultTotal = $this->db->fetchAll($queryTotal);
	$page_rec=$this->parameter->pageSize;
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
	if($mid==0){
		$query= "select * from ".$this->db->getPrefix()."contents left join ".$this->db->getPrefix()."relationships on ".$this->db->getPrefix()."contents.cid = ".$this->db->getPrefix()."relationships.cid left join ".$this->db->getPrefix()."users on ".$this->db->getPrefix()."contents.authorId = ".$this->db->getPrefix()."users.uid where ".$this->db->getPrefix()."contents.type='post'".$keyword." order by modified desc limit ".$i.",".$page_rec;
	}else{
		$query= "select * from ".$this->db->getPrefix()."contents left join ".$this->db->getPrefix()."relationships on ".$this->db->getPrefix()."contents.cid = ".$this->db->getPrefix()."relationships.cid left join ".$this->db->getPrefix()."users on ".$this->db->getPrefix()."contents.authorId = ".$this->db->getPrefix()."users.uid where ".$this->db->getPrefix()."contents.type='post' and ".$this->db->getPrefix()."relationships.mid=".$mid.$keyword." order by modified desc limit ".$i.",".$page_rec;
	}
	$result = $this->db->fetchAll($query);
	if(count($result)>0){
		foreach($result as $value){
			$queryMetas= $this->db->select('name','type')->from('table.metas')->where('mid = ?', $value['mid']);
			$rowMetas = $this->db->fetchRow($queryMetas);
			if($rowMetas["type"]!="category"){
				continue;
			}
		?>
		<article class="blog-main">
		  <h3 class="am-article-title blog-title">
			<small>
				<font color="#ffaa00">
					<?php
						echo $rowMetas['name'];
					?>
				</font>
			</small>
			<a href="<?php echo $url.'?id='.$value['cid'];?>"><?=$value['title'];?></a>
		  </h3>
		  <h4 class="am-article-meta blog-meta">
			由 <a href="javascript:;"><?php if($value['screenName']!=''){echo $value['screenName'];}else{echo $value['name'];}?></a> 发表于 <?=date('Y-m-d H:i:s',$value['modified']);?> 有<?=$value['commentsNum'];?>人评论
		  </h4>
		  <div class="am-g">
			<div class="am-u-sm-12">
			  <?php
				$content=$value['text'];
				if(strpos($content, '<!--markdown-->')===0){
					$content=substr($content,15);
				}
				$content=Markdown::convert($content);
				preg_match_all("/<(img|IMG).*?src=[\'|\"](.*?)[\'|\"].*?[\/]?>/", $content, $pregRule);
				if($pregRule[0]!=null){
					$content1='<img src="'.$pregRule[2][0].'" width="100%" alt="" />';
					$content2 = str_replace($pregRule[0][0], '', $content);
					$content2=Typecho_Common::subStr($content2, 0, 140, '...');
					$content=$content1.$content2;
				}
			  ?>
			  <p><?php echo $content;?></p>
			</div>
		  </div>
		</article>
		<hr class="am-article-divider blog-hr">
		<?php
		}
		?>
		
		<ul class="am-pagination blog-pagination">
		  <?php if($page_now!=1){?>
			<li class="am-pagination-prev"><a href="<?=$url;?>?page_now=1">首页</a></li>
		  <?php }?>
		  <?php if($page_now>1){?>
			<li class="am-pagination-prev"><a href="<?=$url;?>?page_now=<?=$before_page;?>">&laquo; 上一页</a></li>
		  <?php }?>
		  <?php if($page_now<$page){?>
			<li class="am-pagination-next"><a href="<?=$url;?>?page_now=<?=$after_page;?>">下一页 &raquo;</a></li>
		  <?php }?>
		  <?php if($page_now!=$page){?>
			<li class="am-pagination-next"><a href="<?=$url;?>?page_now=<?=$page;?>">尾页</a></li>
		  <?php }?>
		</ul>
		
		<?php
	}else{
	?>
		<article class="blog-main">暂无帖子</article>
	<?php
	}
	?>
	<?php if($this->user->hasLogin()): ?>
	<form class="am-form" method="post" action="<?=$url;?>">
	  <button type="button" class="am-btn am-btn-primary">发帖</button>
	  <fieldset class="am-form-set">
	    <input type="text" name="title" placeholder="标题 *" required="">
		<div class="am-form-group">
		  <label for=""></label>
		  <select name="mid">
			<?php
			$queryCate= "select * from ".$this->db->getPrefix()."metas where parent=0 AND type='category'";
			$resultCate = $this->db->fetchAll($queryCate);
			$metasnum=1;
			foreach($resultCate as $value){
			?>
			<option value="<?=$value['mid'];?>">板块<?=$metasnum;?>：<?=$value['name'];?></option>
			<?php
			$metasnum++;
			}
			?>
		  </select>
		</div>
		<textarea name="text" id="publisheditorbylistbody"></textarea>
	  </fieldset>
	  <button type="submit" class="am-btn am-btn-success am-btn-block">提交</button>
	  <input type="hidden" name="action" value="publish">
	</form>
	<?php endif; ?>
  </div>
  <?php
  }else{
	$query= $this->db->select('*,table.contents.created as tcreated')->from('table.contents')->join('table.users', 'table.contents.authorId = table.users.uid',Typecho_Db::INNER_JOIN)->where('cid = ?', $id);
	$row = $this->db->fetchRow($query);
	?>
	<div class="am-u-md-8">
		<header class="am-g my-head">
		  <div class="am-u-sm-12 am-article">
			<h1 class="am-article-title">
				<small>
					<font color="#ffaa00">
						<?php
							$queryRelationships= $this->db->select()->from('table.relationships')->where('cid = ?', $row['cid']);
							$rowRelationships = $this->db->fetchRow($queryRelationships);
							$queryMetas= $this->db->select()->from('table.metas')->where('mid = ?', $rowRelationships['mid']);
							$rowMetas = $this->db->fetchRow($queryMetas);
							echo $rowMetas['name'];
						?>
					</font>
				</small>
				<?=$row['title'];?>
			</h1>
			<p class="am-article-meta">
				<?php if($row['screenName']!=''){echo $row['screenName'];}else{echo $row['name'];}?>
				发表于 <?=date('Y-m-d H:i:s',$row['modified']);?>
			</p>
		  </div>
		</header>
		<article class="blog-main">
			<?php
				$content=$row['text'];
				
				$i=0;
				$match_1 = "/(\!\[).*?\]\[(\d)\]/";
				preg_match_all ($match_1,$row['text'],$matches_1,PREG_PATTERN_ORDER);
				if(count($matches_1)>0&&count($matches_1[0])>0){
					foreach($matches_1[0] as $val_1){
						$content=str_replace($val_1,"",$content);
						$img_prefix=substr($val_1,strlen($val_1)- 3,3);
						$img_prefix=str_replace("[","\[",$img_prefix);
						$img_prefix=str_replace("]","\]",$img_prefix);
						$match_2 = "/(".$img_prefix.":).*?((.gif)|(.jpg)|(.bmp)|(.png)|(.GIF)|(.JPG)|(.PNG)|(.BMP))/";
						preg_match_all ($match_2,$content,$matches_2,PREG_PATTERN_ORDER);
						if(count($matches_2)>0&&count($matches_2[0])>0){
							foreach($matches_2[0] as $val_2){
								$img=substr($val_2,4);
								$content=preg_replace($match_2,'<img src="'.$img.'" />',$content);
								break;
							}
						}else{
							$content=$row['text'];
							break;
						}
						$i++;
					}
				}else{
					$content=$row['text'];
				}
				if(strpos($content, '<!--markdown-->')===0){
					$content=substr($content,15);
				}
				$content=Markdown::convert($content);
				$content = str_replace("<img ", "<img width=\"100%\"", $content);
				
				//判断是否开启WeMedia付费阅读插件，并隐藏其付费内容
				if(isset($plugins['activated']['WeMedia'])){
					if (preg_match_all('/&lt;!--WeMedia start--&gt;([\s\S]*?)&lt;!--WeMedia end--&gt;/i', $content, $hide_content)){
						$content = str_replace($hide_content[0], "", $content);
					}
				}
				
				echo $content;
			?>
		</article>
		<hr/>
        <ul class="am-comments-list">
			<?php
			$queryTotal= $this->db->select('*,table.comments.created as ccreated,table.comments.text as ctext,table.comments.parent as cparent')->from('table.comments')->join('table.contents', 'table.comments.cid = table.contents.cid',Typecho_Db::INNER_JOIN)->where('table.comments.status = ?', 'approved')->where('table.comments.type = ?', 'comment')->where('table.comments.cid = ?', $id);
			$page_now = isset($_GET['page_now']) ? intval($_GET['page_now']) : 1;
			if($page_now<1){
				$page_now=1;
			}
			$resultTotal = $this->db->fetchAll($queryTotal);
			$page_rec=$this->parameter->pageSize;
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
			$queryComments= $this->db->select('*,table.comments.created as ccreated,table.comments.text as ctext,table.comments.parent as cparent')->from('table.comments')->join('table.contents', 'table.comments.cid = table.contents.cid',Typecho_Db::INNER_JOIN)->where('table.comments.status = ?', 'approved')->where('table.comments.type = ?', 'comment')->where('table.comments.cid = ?', $id)->order('ccreated',Typecho_Db::SORT_ASC)->offset($i)->limit($page_rec);
			$resultComments = $this->db->fetchAll($queryComments);
			$i=1;
			foreach($resultComments as $value){
				$host = 'https://secure.gravatar.com';
				$urltemp = '/avatar/';
				$size = '50';
				$rating = Helper::options()->commentsAvatarRating;
				$hash = md5(strtolower($value['mail']));
				$avatar = $host . $urltemp . $hash . '?s=' . $size . '&r=' . $rating . '&d=';
				?>
				<li class="am-comment">
					<a href="javascript:;">
					  <img src="<?php echo $avatar ?>" alt="" class="am-comment-avatar" width="48" height="48">
					</a>
					<div class="am-comment-main">
					  <header class="am-comment-hd">
						<div class="am-comment-meta">
						  <a href="#link-to-user" class="am-comment-author"><?php echo $value['author']; ?></a> 评论于 <time datetime="<?php echo date('Y-m-d H:i:s',$value['ccreated']); ?>" title="<?php echo date('Y-m-d H:i:s',$value['ccreated']); ?>"><?php echo date('Y-m-d H:i:s',$value['ccreated']); ?></time>
						  <div class="am-fr">
							<a href="javascript:;" class="replyfloor" id="replyfloor<?=$i;?>" data-coid="<?php echo $value['coid']; ?>" data-author="<?php echo $value['author']; ?>" data-ccreated="<?php echo date('Y-m-d H:i:s',$value['ccreated']); ?>" data-ctext="<?php echo htmlspecialchars(strip_tags($value['ctext'])); ?>">回复</a>
							<?php echo (($page_now-1)*$page_rec+$i);?>楼
						  </div>
						</div>
					  </header>
					  <div class="am-comment-bd">
						<?php
						if($value['cparent']!=0){
							$querySubComment= $this->db->select()->from('table.comments')->where('coid = ?', $value['cparent']);
							$rowSubComment = $this->db->fetchRow($querySubComment);
							?>
							<header class="am-comment-hd">
								<div class="am-comment-meta">
								  “<a href="#link-to-user" class="am-comment-author"><?php echo $rowSubComment['author']; ?></a> 评论于 <time datetime="<?php echo date('Y-m-d H:i:s',$rowSubComment['created']); ?>" title="<?php echo date('Y-m-d H:i:s',$rowSubComment['created']); ?>"><?php echo date('Y-m-d H:i:s',$rowSubComment['created']); ?></time><p><?php echo $rowSubComment['text']; ?>”</p>
								</div>
							</header>
							<?php
						}
						?>
						<p><?php echo $value['ctext']; ?></p>
					  </div>
					</div>
				</li>
				<?php
				$i++;
			}
			?>
        </ul>
		<ul class="am-pagination blog-pagination">
		  <?php if($page_now!=1){?>
			<li class="am-pagination-prev"><a href="<?=$url;?>?id=<?=$id;?>&page_now=1">首页</a></li>
		  <?php }?>
		  <?php if($page_now>1){?>
			<li class="am-pagination-prev"><a href="<?=$url;?>?id=<?=$id;?>&page_now=<?=$before_page;?>">&laquo; 上一页</a></li>
		  <?php }?>
		  <?php if($page_now<$page){?>
			<li class="am-pagination-next"><a href="<?=$url;?>?id=<?=$id;?>&page_now=<?=$after_page;?>">下一页 &raquo;</a></li>
		  <?php }?>
		  <?php if($page_now!=$page){?>
			<li class="am-pagination-next"><a href="<?=$url;?>?id=<?=$id;?>&page_now=<?=$page;?>">尾页</a></li>
		  <?php }?>
		</ul>
		<div class="am-g">
		  <div class="am-u-md-8 am-u-sm-centered">
			<?php
			$allowComment=true;
			if($row['allowComment']){
				$queryOption= $this->db->select()->from('table.options')->where('name = ?', 'commentsAutoClose');
				$rowOption = $this->db->fetchRow($queryOption);
				if($rowOption['value']){
					$queryOption= $this->db->select()->from('table.options')->where('name = ?', 'commentsPostTimeout');
					$rowOption = $this->db->fetchRow($queryOption);
					$tcreated=(time()-$row['tcreated'])/60/60/24;
					$commentsPostTimeout=$rowOption['value']/24/3600;
					if($tcreated>$commentsPostTimeout){
						$allowComment=false;
					}else{
						$allowComment=true;
					}
				}else{
					$allowComment=true;
				}
			}else{
				$allowComment=false;
			}
			if($allowComment){
			?>
			<form class="am-form" method="post" action="<?=$url.'?id='.$id;?>">
			  <button type="button" class="am-btn am-btn-primary" id="publishbtn" data-uid="<?=$this->user->hasLogin();?>">发帖</button>
			  <button type="button" class="am-btn am-btn-primary" id="replybtn">回帖</button>
			  <div class="am-fr">
				<a href="javascript:location.href='<?=$url;?>';"><button type="button" class="am-btn am-btn-default">返回</button></a>
			  </div>
			  <fieldset class="am-form-set">
				<?php if(!$this->user->hasLogin()): ?>
				<input type="text" name="author" placeholder="昵称 *" required="">
				<input type="email" name="mail" placeholder="邮箱 *" required="">
				<input type="text" name="replyurl" placeholder="网址">
				<?php endif; ?>
				<textarea name="text" id="replyeditorbybody"></textarea>
			  </fieldset>
			  <button type="submit" class="am-btn am-btn-success am-btn-block">回贴</button>
			  <input type="hidden" name="action" value="reply">
			</form>
			<?php
			}else{
			?>	
			<button type="button" class="am-btn am-btn-default">回帖关闭</button>
			<div class="am-fr">
				<a href="javascript:location.href='<?=$url;?>';"><button type="button" class="am-btn am-btn-default">返回</button></a>
			</div>
			<?php
			}
			?>
		  </div>
		</div>
		<div class="am-popup" id="publishdialog">
		  <div class="am-popup-inner">
			<div class="am-popup-hd">
			  <h4 class="am-popup-title">发帖</h4>
			  <span data-am-modal-close
					class="am-close">&times;</span>
			</div>
			<div class="am-popup-bd">
				<form class="am-form" method="post" action="<?=$url.'?id='.$id;?>">
				  <fieldset class="am-form-set">
					<input type="text" name="title" placeholder="标题 *" required="">
					<div class="am-form-group">
					  <label for=""></label>
					  <select name="mid">
						<?php
						$queryCate= "select * from ".$this->db->getPrefix()."metas where parent=0 AND type='category'";
						$resultCate = $this->db->fetchAll($queryCate);
						$metasnum=1;
						foreach($resultCate as $value){
						?>
						<option value="<?=$value['mid'];?>" <?php if($value['mid']==$rowRelationships['mid']){?>selected<?php }?>>板块<?=$metasnum;?>：<?=$value['name'];?></option>
						<?php
						$metasnum++;
						}
						?>
					  </select>
					</div>
					<textarea name="text" id="publisheditorbypagemain"></textarea>
				  </fieldset>
				  <button type="submit" class="am-btn am-btn-success am-btn-block">提交</button>
				  <input type="hidden" name="action" value="publish">
				</form>
			</div>
		  </div>
		</div>
		<div class="am-popup" id="replydialog">
		  <div class="am-popup-inner">
			<div class="am-popup-hd">
			  <h4 class="am-popup-title">回帖</h4>
			  <span data-am-modal-close
					class="am-close">&times;</span>
			</div>
			<div class="am-popup-bd">
				<header class="am-comment-hd" id="replyparent">
					<div class="am-comment-meta">
					  “<a href="#link-to-user" class="am-comment-author"></a> 评论于 <time datetime="" title=""></time><p></p>
					</div>
				</header>
				<form class="am-form" method="post" action="<?=$url.'?id='.$id;?>">
				  <fieldset class="am-form-set">
					<?php if(!$this->user->hasLogin()): ?>
					<input type="text" name="author" placeholder="昵称 *" required="">
					<input type="email" name="mail" placeholder="邮箱 *" required="">
					<input type="text" name="replyurl" placeholder="网址">
					<?php endif; ?>
					<textarea name="text" id="replyeditorbymain"></textarea>
				  </fieldset>
				  <button type="submit" class="am-btn am-btn-success am-btn-block">回贴</button>
				  <input type="hidden" name="action" value="reply">
				  <input type="hidden" id="replyfloor" name="replyfloor" value="">
				</form>
			</div>
		  </div>
		</div>
	</div>
	<?php
  }
  ?>

  <div class="am-u-md-4 blog-sidebar">
    <div class="am-panel-group">
      <!--
	  <section class="am-panel am-panel-default">
        <div class="am-panel-hd">关于我</div>
        <div class="am-panel-bd">
          <p></p>
          <a class="am-btn am-btn-success am-btn-sm" href="#">查看更多 →</a>
        </div>
      </section>
	  -->
	  <!--
      <section class="am-panel am-panel-default">
        <div class="am-panel-hd">文章目录</div>
        <ul class="am-list blog-list">
          
		  <li><a href="#">Google fonts 的字體（sans-serif 篇）</a></li>
		  
        </ul>
      </section>
	  -->
	  <!--
      <section class="am-panel am-panel-default">
        <div class="am-panel-hd">团队成员</div>
        <div class="am-panel-bd">
          <ul class="am-avg-sm-4 blog-team">
			
            <li><img class="am-thumbnail"
                     src="http://img4.duitang.com/uploads/blog/201406/15/20140615230220_F5LiM.thumb.224_0.jpeg" alt=""/>
            </li>
			
          </ul>
        </div>
      </section>
	  -->
    </div>
  </div>

</div>

<footer class="blog-footer">
  <p>
    <small>
		<a href="<?php $this->options ->siteUrl(); ?>"><?php $this->options->title();?></a><br/>
		© Copyright 2018 <a href="http://www.tongleer.com">同乐儿</a> - All Rights Reserved. 
	</small>
  </p>
</footer>
<script src="https://apps.bdimg.com/libs/jquery/1.7.1/jquery.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/js/amazeui.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/js/amazeui.ie8polyfill.min.js" type="text/javascript"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/amazeui/2.7.2/js/amazeui.widgets.helper.min.js" type="text/javascript"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/10.0.1/classic/ckeditor.js"></script>
<script>
$(function() {
  $('#login-prompt-toggle').on('click', function() {
    $('#login-prompt').modal({
      relatedTarget: this,
      onConfirm: function(e) {
		$('#loginForm').submit();
      },
      onCancel: function(e) {}
    });
  });
  $('#logout').click(function (){
	$.post("<?php $url; ?>",{action:'logout'},function(data){
		window.location.href="<?php $url; ?>";
	});
  });
  ClassicEditor
	.create( document.querySelector( '#publisheditorbylistbody' ),{
		toolbar: [ 'heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'blockQuote' ]
	} )
	.catch( error => {
		console.error( error );
  } );
  ClassicEditor
	.create( document.querySelector( '#publisheditorbypagemain' ),{
		toolbar: [ 'heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'blockQuote' ]
	} )
	.catch( error => {
		console.error( error );
  } );
  ClassicEditor
	.create( document.querySelector( '#replyeditorbybody' ),{
		toolbar: [ 'heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'blockQuote' ]
	} )
	.catch( error => {
		console.error( error );
  } );
  ClassicEditor
	.create( document.querySelector( '#replyeditorbymain' ),{
		toolbar: [ 'heading', '|', 'bold', 'italic', 'bulletedList', 'numberedList', 'blockQuote' ]
	} )
	.catch( error => {
		console.error( error );
  } );
  $('#replybtn').on('click', function() {
    $('#replydialog').modal();
	$('#replyparent').css('display','none');
  });
  $(".replyfloor").each(function(){
	var id=$(this).attr("id")
	$("#"+id).click( function () {
		$('#replydialog').modal();
		$('#replyfloor').val($(this).attr('data-coid'));
		$('#replyparent').css('display','block');
		$('#replyparent a').text($(this).attr('data-author'));
		$('#replyparent time').text($(this).attr('data-ccreated'));
		$('#replyparent p').text($(this).attr('data-ctext'));
	});
  });
  $('#publishbtn').on('click', function() {
	if($(this).attr('data-uid')==1){
		$('#publishdialog').modal();
	}else{
		alert('登陆后方可发帖');
	}
  });
});
</script>
</body>
</html>