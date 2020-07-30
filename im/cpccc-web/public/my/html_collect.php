<?php ob_start();require("class.php");?>
<?php if (1==2){?><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><?php }?>
<style>
.rpbox_red
{
	color:#fcddae;
	width:100%;
	background:#f25745;
}

.rpbox_bottom_red
{
	width:100%;
	height:1rem;
	background:#f25745;
	border-radius: 0px 0px 100% 100%;
}

.rpbox_blue
{
	color:#FFF;
	width:100%;
	background:#00aaef;
}

.rpbox_bottom_blue
{
	width:100%;
	height:1rem;
	background:#00aaef;
	border-radius: 0px 0px 100% 100%;
}


.rpbox_green
{
	color:#FFF;
	width:100%;
	background:#01a548;
}


.rpbox_bottom_green
{
	width:100%;
	height:1rem;
	background:#01a548;
	border-radius: 0px 0px 100% 100%;
}

.rpbox_from
{
	padding-top:0.5rem;
	width:100%;
	text-align:center;
	hieght:0.45rem;
	display:block;
}


.rpbox_from img
{
	width:0.45rem;
	hieght:0.45rem;
	border-radius:0.06rem;

}

.rpbox_from span
{
	font-size:0.35rem;
	font-weight:800;
	margin-bottom:-0.5rem;
	padding-left:0.1rem;
	vertical-align:middle;
}

.rpbox_title
{
	font-size:0.28rem;
	padding-top:0.2rem;
}

.rpbox_money
{
	font-size:1.2rem;
	padding-top:0.6rem;
	font-weight:800;
	line-height:0.5rem;
}

.rpbox_money span
{
	
	font-size:0.6rem;
	padding-top:0.3rem;
}

.rpbox_mini
{
	font-size:0.22rem;
	padding-top:0.2rem;
}

.rplist
{
	width:100%;
}

.rpinfo
{
	width:100%;
	color:#666;
	padding:0.2rem;
	padding-top:0.5rem;
	font-size:0.3rem;
}

.bn_line
{
	position:relative;
}

.bn_line::before{
    content: '';
    position: absolute;
    top: 0;
    left: 0;  
	border-bottom: 1px solid #d6d6d6;
    width: 200%;
    height: 200%;
    transform-origin: 0 0;
    transform: scale(0.5,0.5);
    box-sizing: border-box;
}

.rplist table
{
	width:100%;	
}

.rplist table img
{
	width:1rem;
	height:1rem;
	padding:0.2rem;
	border-radius:0.3rem;
}

.rplist table span
{
	font-size:0.3rem;
	color:#151515;
	padding-right:0.2rem;
}

.rplist table dd
{
	font-size:0.25rem;
	color:#7c7c7c;
}

</style>

<?php 
$p_uid=$_GET['p_uid'];
$p_tid=$_GET['p_tid'];
$p_te=$_GET['p_te'];
$p_money=$_GET['p_money'];
$p_number=$_GET['p_number'];
$p_remarks=$_GET['p_remarks'];
$p_nickname=$_GET['p_nickname'];
$p_amount_id=$_GET['p_amount_id'];


if ($p_te==1){$te_title="好友转账";$bc="blue";}if ($p_te==2){$te_title="普通红包";$bc="red";}if ($p_te==3){$te_title="群组红包";$bc="red";}


	
//获得登录信息
$session_name=session_name();
$m_phpsessid=$_COOKIE[$session_name];
$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
$result=mysqli_query($conn,$sql);
$row=mysqli_fetch_array($result);
$dl_uid=$row['uid'];
	
	
	
//获得红包的相关信息并
$sql="select * from amount where `id`='$p_amount_id' order by id asc LIMIT 1";
$result=mysqli_query($conn,$sql);
$row=mysqli_fetch_array($result);
$kx_id=$row['id'];
$kx_type=$row['type'];
$kx_sub_type=$row['sub_type'];
if ($kx_sub_type==1){$sub_name="转账";}if ($kx_sub_type==2){$sub_name="红包";}if ($kx_sub_type==3){$sub_name="群红包";}

$kx_uid=$row['uid'];
$kx_to=$row['to'];
$kx_money=$row['money'];
$kx_amount_money=$kx_money;
$kx_gp_money=$row['gp_money'];

if ($kx_gp_money>0){$kx_total_money=$kx_gp_money;}else{$kx_total_money=$kx_money;};

$kx_remarks=$row['remarks'];
$kx_timestamp=$row['timestamp'];
$kx_state=$row['state'];



$sql="select * from user where uid='$kx_uid' order by uid desc LIMIT 1";
$result=mysqli_query($conn,$sql);
$row=mysqli_fetch_array($result);
$from_nickname=$row['nickname'];
$from_avatar=$row['avatar'];
/*获得红包的相关信息并显示*/


//如果是群组红包的话，金额等显示自己的
if ($p_te==3)
{
$sql="select * from amount where `gp_amount_id`='$p_amount_id' and uid='$dl_uid' order by id asc LIMIT 1";
$result=mysqli_query($conn,$sql);
$row=mysqli_fetch_array($result);
$kx_amount_money=$row['money'];
	if (!$kx_amount_money)
	{
		$sql="select * from amount where `gp_amount_id`='$p_amount_id' order by id asc LIMIT 1";
		$result=mysqli_query($conn,$sql);
		$row=mysqli_fetch_array($result);
		$kx_amount_money=$row['money'];	
		$kx_qhbzjs="yes";
	}
}
//如果是群组红包的话，金额等显示自己的

?>
<body>

<div class="im_my_header_<?php print $bc;?>"><i class="iconfont icon-fanhui1" onClick="layer.closeAll()"></i><?php print $te_title;?><span style="display:none">红包记录</span></div>
    


<div class="rpbox_<?php print $bc;?>">
	<div class="rpbox_from"><img src="<?php print $from_avatar;?>" style="vertical-align:middle"><span><?php print $from_nickname."的".$sub_name;?></span></div>
    <?php if ($kx_uid!=$dl_uid or $kx_sub_type==3){?>
        <div class="rpbox_title" align="center"><?php if ($p_te==1){print "收到一笔转账";}else{print $p_remarks;}?></div>
        <div class="rpbox_money" align="center"><?php print "<span>￥</span>".$kx_amount_money;?></div>
        <div class="rpbox_mini" align="center"><?php if ($kx_qhbzjs!="yes"){print '已存入零钱，可发红包、转账、提现等';}else{print "没抢到红包，红包被抢光啦！";}?></div>
    <?php }else{?>
        <div class="rpbox_title" align="center"><?php if ($p_te==1){print "发起一笔转账";}else{print $p_remarks;}?></div>
        <div class="rpbox_mini" align="center"><?php print $success;?></div>
    <?php }?>
</div>
<div class="rpbox_bottom_<?php print $bc;?>"></div>




<div class="rplist">
<div class="rpinfo"><?php print $p_number."个".$te_title."共".$kx_total_money;?>元</div>
<div class="bn_line"></div>
<?php
$sql="select * from amount where gp_amount_id='$kx_id' order by id asc LIMIT 100";
$result=mysqli_query($conn,$sql);
while ($row=mysqli_fetch_array($result))
{
	$row_uid=$row['uid'];
	$row_money=$row['money'];
	
	$sql_user="select * from user where uid='$row_uid' order by uid desc LIMIT 1";
	$result_user=mysqli_query($conn,$sql_user);
	$row_user=mysqli_fetch_array($result_user);
	$list_nickname=$row_user['nickname'];
	$list_avatar=$row_user['avatar'];
	$list_time=$row_user['timestamp'];
?>
<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="10" rowspan="2"><img src="<?php print $list_avatar;?>"></td><td valign="bottom"><span><?php print $list_nickname;?></span></td>
  <td align="right" valign="bottom"><span><?php print $row_money;?></span></td>
</tr>
  <tr>
    <td colspan="2" valign="top" class="bn_line"><dd><?php print date( "Y-m-d H:i:s",$list_time);?></dd></td>
  </tr>
</table>
<?php
}
?>
</div>   
    
    

</body>



<?php 
$temp=ob_get_contents();
ob_end_clean();

$temp = str_replace(array("\/r\/n", "\/r", "\/n"), "", $temp); 

$temp = str_replace(PHP_EOL, '', $temp); 
$temp = addslashes($temp);
?>
<?php
print '{"code":"0","html": "'.$temp.'"}';
?>
