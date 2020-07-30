<?php ob_start();require("class.php");?>
<?php if (1==2){?><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><?php }?>

<style>
.walletbox
{
	margin:0.2rem;
	margin-top:0rem;
	background:#3bac6a;
	border-radius:0.15rem;
	height:3rem;
	color:#f8faf5;
}

.walletbox table
{
	padding-top:0.75rem;
}


.walletbox i
{
	font-size:0.7rem;
}

.walletbox div
{
	font-size:0.3rem;
	display:block;
	margin-top:0.18rem;
	height:0.3rem;
}

.walletbox dd
{
	font-size:0.25rem;
	display:block;
	color:#8ad3a5;
	padding-top:0.05rem;
}






.line
{
	position:relative;
}

.line::before{
    content: '';
    position: absolute;
    top: 0;
    left: 0;  
    border-bottom: 1px solid #d9d9d9;
    width: 200%;
    height: 200%;
    transform-origin: 0 0;
    transform: scale(0.5,0.5);
    box-sizing: border-box;
}

</style>
<?php
//获得登录信息
$session_name=session_name();
$m_phpsessid=$_COOKIE[$session_name];
$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
$result=mysqli_query($conn,$sql);
$row=mysqli_fetch_array($result);
$dl_uid=$row['uid'];

//获得收款人原有金额，并计算新余额
$sql="select * from amount where uid='$dl_uid' order by id desc LIMIT 1";
$result=mysqli_query($conn,$sql);
$row=mysqli_fetch_array($result);
$my_balance=$row['balance']+0;
?>

<?php $t=$_GET['t'];if ($t=="show"){?>
<body>
    <div id="selectheader" class="im_my_header"><i class="iconfont icon-fanhui1" onClick="layer.closeAll();"></i>我的钱包</div>
	
    <div class="walletbox">
   	<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td width="33%" align="center"><i class="iconfont icon-qianbao" onClick="m_msg('请自定义对接API')"></i></td>
   	  <td width="33%" align="center"><i onClick="m_msg('请自定义对接API')" class="iconfont icon-jinqian"></i></td>
   	  <td width="33%" align="center"><i onClick="m_msg('请自定义对接API')" class="iconfont icon-xinyongqiahuankuan"></i></td>
   	  </tr>
   	  <tr>
   	    <td align="center"><div onClick="m_msg('请自定义对接API')">钱包</div></td>
   	    <td align="center"><div onClick="m_msg('请自定义对接API')">充值</div></td>
   	    <td align="center"><div onClick="m_msg('请自定义对接API')">提现</div></td>
      </tr>
   	  <tr>
   	    <td align="center"><dd onClick="m_msg('请自定义对接API')">￥<?php print $my_balance;?></dd></td>
   	    <td align="center">&nbsp;</td>
   	    <td align="center">&nbsp;</td>
      </tr>
   	</table>  
    </div>
    
</body>       
<?php }?>






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
