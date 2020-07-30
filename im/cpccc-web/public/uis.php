<?php require("my/class.php");?>


<!DOCTYPE html>
<html lang="zh-cn">
<head>
	<meta charset="UTF-8" />
	<title>正在跳转</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0, user-scalable=no">
	<meta name="apple-mobile-web-app-capable" content="yes">
	<meta name="apple-touch-fullscreen" content="yes">
	<meta name="generator" content="OTgxNzExMDEz">
	<meta http-equiv="Access-Control-Allow-Origin" content="*">
	<script src="cssjs/js/jquery/jquery-1.12.4.min.js"></script>    
</head>
<body>
<img src="https://im.hatudou.com/h5/login/logincaptcha" style="display:none">
<?php
/*核心配置*/
$InitPass="CpcccIM@Yc";

$Get_U_id=$_GET['u'];
$Get_U_nickname=$_GET['n'];
$Get_U_avatar=$_GET['a'];
$Get_U_sign=$_GET['s'];

$Get_TU_id=$_GET['tu'];
$Get_TU_nickname=$_GET['tn'];
$Get_TU_avatar=$_GET['ta'];
$Get_TU_sign=$_GET['ts'];

$go=$_GET['go'];
$back_url=$_GET['url'];

if (!$Get_U_id){print "Sorry,Po.001";exit;}
else
{
	$username="Y@".$Get_U_id;
	$u_sql="SELECT * FROM  `user` WHERE  `username` ='$username' LIMIT 1";
	$u_result=mysqli_query($conn,$u_sql);
	$u_row=mysqli_fetch_array($u_result);
	$u_id=$u_row['uid'];
	$u_username=$u_row['username'];
}

if (!$u_id)
{
	$username="Y@".$Get_U_id;
	$nickname=$Get_U_nickname;
	$avatar=$Get_U_avatar;
	$sign=$Get_U_sign;
	$password=md5($username."-cpcccim-".$InitPass);
	$state="offline";
	$timestamp=time();
	$account_state="normal";

	$sql="INSERT INTO user (username,nickname,avatar,sign,password,state,timestamp,account_state) VALUES ('$username','$nickname','$avatar','$sign','$password','$state','$timestamp','$account_state')";
	mysqli_query($conn,$sql);
	$In_id=mysqli_insert_id($conn)+0;
	$u_id=$In_id;
	$u_username=$username;
}

if (!$u_id){print "Sorry,Po.002";exit;}


if($go!="my")
{
	if (!$Get_TU_id){print "Sorry,Po.003";exit;}
	else
	{
		$username="Y@".$Get_TU_id;
		$tu_sql="SELECT * FROM  `user` WHERE  `username` ='$username' LIMIT 1";
		$tu_result=mysqli_query($conn,$tu_sql);
		$tu_row=mysqli_fetch_array($tu_result);
		$tu_id=$tu_row['uid'];
		$tu_username=$tu_row['username'];
	}
	
	if (!$tu_id)
	{
		$username="Y@".$Get_TU_id;
		$nickname=$Get_TU_nickname;
		$avatar=$Get_TU_avatar;
		$sign=$Get_TU_sign;
		$password=md5($username."-cpcccim-".$InitPass);
		$state="offline";
		$timestamp=time();
		$account_state="normal";
	
		$sql="INSERT INTO user (username,nickname,avatar,sign,password,state,timestamp,account_state) VALUES ('$username','$nickname','$avatar','$sign','$password','$state','$timestamp','$account_state')";
		mysqli_query($conn,$sql);
		$In_tid=mysqli_insert_id($conn)+0;
		$tu_id=$In_tid;
		$tu_username=$username;
	}
	if (!$tu_id){print "Sorry,Po.004";exit;}
	
	//**************建立朋友关系****************
	$last_read_time=time();
	$sql="INSERT INTO friend (uid,friend_uid,state,last_read_time) VALUES ('$u_id','$tu_id','chatting','$last_read_time')";//
	mysqli_query($conn,$sql);
	$sql="INSERT INTO friend (uid,friend_uid,state,last_read_time) VALUES ('$tu_id','$u_id','chatting','$last_read_time')";//
	mysqli_query($conn,$sql);
}


?>
<script>
var domain=window.location.host;
var username='<?php print $u_username;?>';
var password='<?php print $InitPass;?>';
var back_url='<?php print $back_url;?>';

function setCookie(name,value)
{
 var Days = 1;
 var exp = new Date();
 exp.setTime(exp.getTime() + Days*24*60*60*1000);
 document.cookie = name + "="+ escape (value) + ";expires=" + exp.toGMTString();
}
setCookie('url',back_url);



$.ajax(
{
	url : '//'+domain+'/h5/login/check',
	method : 'POST',
	dataType : 'json',
	timeout : '100000',//千制
	data : {'username':username,'password':password,'captcha':'6666'}, 
	success:function(ret)
	{
		
	}, 
	error:function(err){ulert(username+'|'+password+"]"+JSON.stringify(err));}
}); 


$.ajax(
{
	url : '//'+domain+'/h5/login/check',
	method : 'POST',
	dataType : 'json',
	timeout : '100000',//千制
	data : {'username':username,'password':password,'captcha':'6666'}, 
	success:function(ret)
	{
		setTimeout('window.location="/im/h5/"',999);
	}, 
	error:function(err){ulert(username+'|'+password+"]"+JSON.stringify(err));}
}); 
</script>

</body>
</html>