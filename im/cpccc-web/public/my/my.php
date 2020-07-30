<?php if (1==2){?><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><?php }?>
<?php
require("class.php");

$t=$_GET['t'];

print "UID：".$_SESSION('login_uidaaa');


//*******************************追加SESSION

if ($t=="addsession")
{
	$login_uid=$_POST['login_uid'];
	$login_username=$_POST['login_username'];
	
	$_SESSION['m_uid']=$login_uid;
	$_SESSION['m_username']=$login_username;
	
}

if ($_SESSION['m_uid']!="")
{
	$m_uid=$_SESSION['m_uid'];
	$m_username=$_SESSION['m_username'];
}
//*******************************追加SESSION


//*******************************群禁言
if ($t=="forbiddengroup")
{
	$te=$_GET['te'];
	$gid=$_GET['gid'];
	$sql="update groups set state='$te' where gid='$gid' and uid='$m_uid' LIMIT 1";
	$result=mysqli_query($conn,$sql);
	
	if ($result){print '{"code":"0"}';}else{print '{"code":"1"}';}
	
	
	//暂用，查询当前群的群主ID*********************这块最后要在核对用户身份
	/*
	$sql="select * from groups where gid='$gid' LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$group_uid=$row['uid'];
	*/
	//暂用，查询当前群的群主ID
	
	
	$sql="select * from user where uid='$m_uid' and username='$m_username' LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$group_nickname=$row['nickname'];
	
	
	
	//向群里广播一条禁言消息
	$from=$m_uid;
	$to=$gid;
	if ($from>0 and $to>0)
	{
		
		if ($te=="forbidden"){$content="群主 [".$group_nickname."]({POPBASEURI}user/detail/".$m_uid.") 设置全群禁言";}else{$content="群主 [".$group_nickname."]({POPBASEURI}user/detail/".$m_uid.") 解除全群禁言";}
		$type="group";
		$sub_type="notice";
		$timestamp=strtotime(date("Y-m-d H:i:s"));
	
		$sql="INSERT INTO  message (`from` ,`to` ,`content` ,`type` ,`sub_type` ,`timestamp`)VALUES ('$from',  '$to',  '$content',  '$type',  '$sub_type',  '$timestamp')";
		$result=mysqli_query($conn,$sql);
		//向群里广播一条禁言消息
	}
	
	exit;
}
//*******************************群禁言

?>