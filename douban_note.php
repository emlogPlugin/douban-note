<?php
/*
Plugin Name: 同步到豆瓣日记
Version: 0.2
Plugin URL:http://www.justintseng.com
Description: 发表日志时可选择是否拷备一份至豆瓣日记。
Author: Justin Tseng
Author Email: admin@justintseng.com
Author URL: http://www.justintseng.com
*/
if(!defined('EMLOG_ROOT')) {exit('error!');}

function douban_note_hide()
{
?>
    <input type="checkbox" id="doubannote" value="1" name="douban_note" checked="checked" /><label for="doubannote">同步到豆瓣日记</label>
<?php
}
    addAction('adm_writelog_head','douban_note_hide');//挂载


function douban_note_publish()//发布
{	
  global $logData,$action,$blogid,$douban_note_hide;
  $douban_note_hide = isset($_POST['douban_note']) ? 'y' : 'n';
  
  if($action == 'add')
  {
	if($logData['password'] != '')
	{
  //    $logData['content'] ='此日志为加密日志，请<a href='.BLOG_URL.'?post='.$blogid.' target="_blank">点击此处</a>查看';
		$logData['hide'] ='y';
	}
	if($logData['hide'] != 'y' && $GLOBALS["douban_note_hide"] != 'n')
	{
		$title = $logData['title'];
		$content = str_replace(array('&nbsp;', ' '), array(),strip_tags(stripslashes($logData['content']))) . '
原文地址：' .Url::log($blogid);

		// 载入豆瓣Oauth类和API类
		require_once 'DoubanOauth.php';
		require_once 'DoubanAPI.php';
		require_once 'douban_note_config.php';
		require_once 'douban_note_token_conf.php';
		
		$douban_note_access_token = douban_note_access_token;
		$douban_note_refresh_token = douban_note_refresh_token;
		$douban_note_expires_in = douban_note_expires_in;
		$douban_note_user_id = douban_note_user_id;
		
		// 豆瓣应用public key
		$douban_note_clientId = douban_note_clientId;
		
		// 豆瓣应用secret key
		$douban_note_secret = douban_note_secret;
		
		// 用户授权后的回调链接
		$douban_note_callback = BLOG_URL . douban_note_callback2;
		
		// 设置应用需要的权限，Oauth类默认设置为douban_basic_common
		$douban_note_scope = douban_note_scope;
		
		// 生成一个豆瓣Oauth类实例
		$douban_note = new DoubanOauth($douban_note_clientId, $douban_note_secret, $douban_note_callback, $douban_note_scope);
		
		//获取更新的token
		$douban_note_time_now = time();
		$douban_note_time = douban_note_time;
		if($douban_note_time_now-$douban_note_time>=$douban_note_expires_in)
		{
			$douban_note->old_refresh_token = $douban_note_refresh_token;
			
			$douban_note_refresh_info = $douban_note->refresh();
			
			$douban_note_access_token = $douban_note_refresh_info['access_Token'];
			$douban_note_expires_in = $douban_note_refresh_info['expires_in'];
			$douban_note_refresh_token = $douban_note_refresh_info['refresh_Token'];
			$douban_note_user_id = $douban_note_refresh_info['douban_user_id'];
			
			//存储Token信息
			$douban_note_profile = '../content/plugins/douban_note/douban_note_token_conf.php';
			$douban_note_time = time();
			
			$douban_note_new_profile = "<?php\ndefine('douban_note_access_token','$douban_note_access_token');\ndefine('douban_note_expires_in','$douban_note_expires_in');\ndefine('douban_note_refresh_token','$douban_note_refresh_token');\ndefine('douban_note_user_id','$douban_note_user_id');\ndefine('douban_note_time','$douban_note_time');\n";
			
			$douban_note_fp = @fopen($douban_note_profile,'wb');
			fwrite($douban_note_fp,$douban_note_new_profile);
			fclose($douban_note_fp);
		}

		// $data为日记信息
		$douban_note_data = array(
				'title' => $title,
				'privacy' => 'public',
				'can_reply' => 'true',
				'content' => $content,
			  'pids' => '',
			  'layout_pid' => 'L',
			  'desc_pid' => '',
			  'image_pid' => '',
			);
		
		// 生成一个豆瓣API基类实例
		$douban_note_API = new DoubanAPI();
		
		// 选择修改评论API
		$douban_note_API->noteAdd($douban_note_access_token);
		
		// 使用豆瓣Oauth类向修改评论API发送请求，并获取返回结果
		$douban_note_result = $douban_note->send($douban_note_API, $douban_note_data);
		
		// 打印结果,返回的$result已经经过json_decode操作
		//var_dump($douban_note_result);
	  }
	}
}
addAction('save_log','douban_note_publish');//挂载

function douban_note_menu()
{
	echo '<div class="sidebarsubmenu" id="douban_note"><a href="./plugin.php?plugin=douban_note">同步到豆瓣日记</a></div>';
}
	addAction('adm_sidebar_ext', 'douban_note_menu');//挂载
