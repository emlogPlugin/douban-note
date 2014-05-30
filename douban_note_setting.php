<?php
if(!defined('EMLOG_ROOT')) {exit('error!');}
function plugin_setting_view()
{
	require_once(EMLOG_ROOT.'/content/plugins/douban_note/DoubanOauth.php');
	require_once(EMLOG_ROOT.'/content/plugins/douban_note/DoubanAPI.php');
	require_once(EMLOG_ROOT.'/content/plugins/douban_note/douban_note_config.php');
	require_once(EMLOG_ROOT.'/content/plugins/douban_note/douban_note_token_conf.php');
?>
<div class=containertitle><b>同步到豆瓣日记</b>
<?php if(isset($_GET['setting'])):?><span class="actived">插件设置完成</span><?php endif;?>
</div>
<div class=line></div>
<div class="des">同步到豆瓣日记插件基于豆瓣API，可以在发布日志时选择是否自动同步到你的豆瓣日记。<br />请先到<a href="http://developers.douban.com/apikey/">豆瓣开放平台</a>创建应用，并添加自己为测试用户，设置API权限为豆瓣公共和豆瓣社区，然后将API Key和Secret填入以下输入框保存，即可开始绑定帐号。
<br />注：加密日志将不被同步。</div>
<div>
<?php
$douban_note_access_token = douban_note_access_token;
$douban_note_time_now = time();
$douban_note_time = douban_note_time;
$douban_note_expires_in = douban_note_expires_in;
if(isset($douban_note_access_token) && $douban_note_time_now-$douban_note_time<=$douban_note_expires_in){
?>
    <p><img src="http://img3.douban.com/icon/ul<?php echo douban_note_user_id; ?>.jpg" width="80" height="80" alt="" />
    <br />已成功绑定！</p>
<?php
}
else{
	// 豆瓣应用public key
	$douban_note_clientId = douban_note_clientId;
	// 豆瓣应用secret key
	$douban_note_secret = douban_note_secret;
	// 用户授权后的回调链接
	$douban_note_callback = BLOG_URL . douban_note_callback;
	// 设置应用需要的权限，Oauth类默认设置为douban_basic_common
	$douban_note_scope = douban_note_scope;
	// 生成一个豆瓣Oauth类实例
	$douban_note = new DoubanOauth($douban_note_clientId, $douban_note_secret, $douban_note_callback, $douban_note_scope);
	
	$authorizationUrl = $douban_note->authorizationUrl();
?>
    <p><a href="<?php echo $authorizationUrl; ?>" >绑定豆瓣帐号</a></p> 
<?php
	if ( isset($_GET['code'])) {
		// 设置authorizationCode
		$douban_note->authorizationCode = $_GET['code'];
		
		// 通过authorizationCode获取accessToken
		$douban_note_access_info = $douban_note->access();
		$douban_note_access_token = $douban_note_access_info['access_Token'];
		$douban_note_expires_in = $douban_note_access_info['expires_in'];
		$douban_note_refresh_token = $douban_note_access_info['refresh_Token'];
		$douban_note_user_id = $douban_note_access_info['douban_user_id'];
		
		//存储Token信息
		$douban_note_profile = EMLOG_ROOT.'/content/plugins/douban_note/douban_note_token_conf.php';
		$douban_note_time = time();
		$douban_note_new_profile = "<?php\ndefine('douban_note_access_token','$douban_note_access_token');\ndefine('douban_note_expires_in','$douban_note_expires_in');\ndefine('douban_note_refresh_token','$douban_note_refresh_token');\ndefine('douban_note_user_id','$douban_note_user_id');\ndefine('douban_note_time','$douban_note_time');\n";
		$douban_note_fp = @fopen($douban_note_profile,'wb');
		if(!$douban_note_fp) {
			emMsg('操作失败，请确保插件目录(/content/plugins/douban_note/)可写');
		}
		fwrite($douban_note_fp,$douban_note_new_profile);
		fclose($douban_note_fp);
	}
}
?>
</div>
<div>
<form id="form1" name="form1" method="post" action="plugin.php?plugin=douban_note&action=setting">
<table width="540" border="0" cellspacing="0" cellpadding="0">
<tr>
<td width="90"><span style="width:300px;">API Key</span></td>
<td width="450"><input name="clientId" type="text" id="clientId" style="width:180px;" value="<?php echo douban_note_clientId;?>"/></td>
</tr>
<tr>
<td>Secret</td>
<td><input type="secret" name="secret" value="<?php echo douban_note_secret;?>" style="width:180px;"/></td>
</tr>
<tr>
<td height="30">&nbsp;</td>
<td><input name="Input" type="submit" value="提交" /> <input name="Input" type="reset" value="取消" /></td>
</tr>
</table>
</form>
<br/>
说明：请确认本插件目录下“douban_note_config.php”、“douban_note_token_conf”文件据有可读写权限。如有疑问，请访问<a href="http://www.justintseng.com/douban-note" target="_blank">我的博客</a>留言，将尽量解答。
</div>
<?php
}
function plugin_setting()
{
    include(EMLOG_ROOT.'/content/plugins/douban_note/douban_note_config.php');
	$douban_note_fso = fopen(EMLOG_ROOT.'/content/plugins/douban_note/douban_note_config.php','r');
	$douban_note_config = fread($douban_note_fso,filesize(EMLOG_ROOT.'/content/plugins/douban_note/douban_note_config.php'));
	fclose($douban_note_fso);

	$douban_note_clientId = htmlspecialchars($_POST['clientId'], ENT_QUOTES);
	$douban_note_secret = htmlspecialchars($_POST['secret'], ENT_QUOTES);
	$interval = is_numeric($_POST['interval'])&&$_POST['interval'] > 0 ? $_POST['interval'] : '0';
	$douban_note_patt = array("/define\('douban_note_clientId',(.*)\)/","/define\('douban_note_secret',(.*)\)/","/define\('INTERVAL',(.*)\)/");
	$douban_note_replace = array("define('douban_note_clientId','".$douban_note_clientId."')","define('douban_note_secret','".$douban_note_secret."')","define('INTERVAL','".$interval."')");
	$douban_note_new_config = preg_replace($douban_note_patt, $douban_note_replace, $douban_note_config);
	$douban_note_fso =@fopen(EMLOG_ROOT.'/content/plugins/douban_note/douban_note_config.php','w');
	if(!$douban_note_fso) emMsg('数据存取失败，请确认本插件目录下"douban_note_config.php"文件为可读写权限(777)！');
	fwrite($douban_note_fso,$douban_note_new_config);
	fclose($douban_note_fso);
}
?>