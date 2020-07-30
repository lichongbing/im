<?php $sign="no";require("class.php");?>
<?php if (1==2){?><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><?php }?>
<?php
$t=$_GET['t'];


//-----------------------------------------------------------------------------------------------------------验证交易密码模块
if ($t=="safepassword")
{
	$session_name=session_name();
	$m_phpsessid=$_COOKIE[$session_name];
	$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$m_login_uid=$row['uid'];
	if (!$m_login_uid){print '{"code":"0","state":"0"}';exit;}
	
	$sql="select * from user where uid='$m_login_uid' LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$m_username=$row['username'];
	$m_safepassword=$row['safepassword'];
	if (!$m_safepassword){print '{"code":"0","state":"2"}';exit;}	
	
	//$sql="update ls set text='$p_safepassword' where id=1 LIMIT 1";mysqli_query($conn,$sql);	

	$safepassword=md5($m_username."-cpcccim-".$_POST['safepassword']);
	if ($safepassword==$m_safepassword){print '{"code":"0","state":"9"}';exit;}else{print '{"code":"0","state":"1"}';exit;}
}
//-----------------------------------------------------------------------------------------------------------验证交易密码模块



//-----------------------------------------------------------------------------------------------------------交易处理模块
if ($t=="payconfirm")
{
	//接收参数并进行处理
	$post_te=$_POST["te"];
	$post_to=$_POST["toid"];
	$post_money=$_POST["money"];
	$post_number=$_POST["number"];if ($post_number==""){$post_number=1;}
	$post_remarks=$_POST["remarks"];	
	//接收参数并进行处理
	
	
	/*发送用户信息*/
	$session_name=session_name();
	$m_phpsessid=$_COOKIE[$session_name];
	$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$m_login_uid=$row['uid'];
	if (!$m_login_uid){print '{"code":"0","state":"0"}';exit;}
	
	
	$sql="select * from user where uid='$m_login_uid'  order by uid desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$m_uid=$row['uid'];
	$m_username=$row['username'];
	$m_nickname=$row['nickname'];
	/*发送用户信息*/

	
	/*检测余额，并更新余额*/
	$sql="select * from amount where uid='$m_uid' order by id desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$m_balance=$row['balance']+0;
	if ($m_balance<$post_money){print '{"code":"0","state":"1"}';exit;}
	/*检测余额，并更新余额*/
	
	/*接收用户信息*/
	if ($post_te=="1" or $post_te=="2"){$message_type="friend";}if ($post_te=="3"){$message_type="group";}
	if ($message_type=="group")
	{
		$sql="select * from groups where gid='$post_to' LIMIT 1";
		$result=mysqli_query($conn,$sql);
		$row=mysqli_fetch_array($result);
		$t_uid=$row['gid'];
		$t_nickname=$row['groupname'];
	}
	else
	{
		$sql="select * from user where uid='$post_to' LIMIT 1";
		$result=mysqli_query($conn,$sql);
		$row=mysqli_fetch_array($result);
		$t_uid=$row['uid'];
		$t_nickname=$row['nickname'];
	}
	
	if (!$t_uid){print '{"code":"0","state":"2","html":"'.$sql.'"}';exit;}
	/*接收用户信息*/
	
	if ($post_remarks=="")
	{
		if ($post_te=="1"){$post_remarks="转账给".$t_nickname;$pay_content="￥".$post_money."";}//转账
		if ($post_te=="2"){$post_remarks="发红包给".$t_nickname;$pay_content="恭喜发财 大吉大利";}//普通红包
		if ($post_te=="3"){$post_remarks="发群红包给".$t_nickname;$pay_content="恭喜发财 大吉大利";}//群红包
	}
	else
	{
		$pay_content=$post_remarks;
	}


	$type=2;//减少
	$sub_type=$post_te;//减少
	$uid=$m_uid;
	$to=$t_uid;
	$remarks=$post_remarks;
	$timestamp=time();
	$money=$post_money;
	$balance=($m_balance-$money);
	$state=0;
	
	if ($post_number==1)
	{	
		$sql="INSERT INTO  amount (`type` ,`sub_type` ,`uid` ,`to` ,`money` ,`balance` , `remarks` ,`timestamp` ,`state` ,`last_timestamp`)VALUES ('$type`',  '$sub_type`',  '$uid`',  '$to',  '$money',  '$balance',  '$remarks',  '$timestamp',  '$state',  '$timestamp')";
		$result=mysqli_query($conn,$sql);
		$gp_gid=mysqli_insert_id($conn);
	}
	else
	{
		//******数量不为1的群发红包的情况，将一个红包拆分为多个红包
		$totalmoney =$post_money;//总金额
		$num = $post_number; //红包数量
		$redpacket_arr= make_redpacket($num,$totalmoney);
		
		$xi=0;
		$gp_gid=0;
		$gp_money=$money;
		$gp_number=$post_number;
		while ($xi<($post_number+0))
		{
			
			$money=$redpacket_arr[$xi];
			$sql="INSERT INTO  amount (`type` ,`sub_type` ,`uid` ,`to` ,`money` ,`balance` , `remarks` ,`timestamp` ,`state` ,`gp_gid` ,`gp_money` ,`gp_number`)VALUES ('$type`',  '$sub_type`',  '$uid`',  '$to',  '$money',  '$balance',  '$remarks',  '$timestamp',  '$state',  '$gp_gid',  '$gp_money',  '$gp_number')";
			$result=mysqli_query($conn,$sql);
			if ($xi=="0"){$gp_gid=mysqli_insert_id($conn);}
			
			$xi=$xi+1;
			
		}
		//******数量不为1的群发红包的情况，将一个红包拆分为多个红包
	}
	
	
	
	
	if ($result!=1){print '{"code":"0","state":"7"}';exit;}else
	{
		$to_content="{nb}{pay}[".$m_uid."{|}".$t_uid."{|}".$post_te."{|}".$post_money."{|}".$post_number."{|}".$pay_content."{|}".$t_nickname."{|}".$gp_gid."]";
		
		print '{"code":"0","state":"9","to_id":"'.$t_uid.'","to_type":"'.$message_type.'","to_content":"'.$to_content.'"}';
		exit;	
	}
	
	
}
//-----------------------------------------------------------------------------------------------------------交易处理模块






//-设置登录密码
if ($t=="settingsave")
{
	$session_name=session_name();
	$m_phpsessid=$_COOKIE[$session_name];
	$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$m_login_uid=$row['uid'];
	
	$sql="select * from user where uid='$m_login_uid' LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$m_username=$row['username'];
	
	if (!$m_login_uid){print '{"code":"0","state":"0"}';exit;}
	
	if (isset($_POST['password']))
	{
		$password=md5($m_username."-cpcccim-".$_POST['password']);
		$upsql="password='".$password."'";
	}
	
	
	if (isset($_POST['safepassword']))
	{
		$safepassword=md5($m_username."-cpcccim-".$_POST['safepassword']);
		
		if (isset($_POST['password']))
		{
			$upsql=$upsql.",safepassword='".$safepassword."'";
		}
		else
		{
			$upsql="safepassword='".$safepassword."'";
		}
	}
	
	
	$sql="update user set $upsql where uid=$m_login_uid LIMIT 1";
	mysqli_query($conn,$sql);	

	print '{"code":"0","state":"9"}';
}
//-设置登录密码

//-群红包分配
function randomFloat($min = 0, $max = 1) 
{
   return sprintf("%.2f",$min + mt_rand() / mt_getrandmax() * ($max - $min));
}

function make_redpacket($num = 1,$money = 1)
{
   $min = 0.01;//最小的红包金额
   if($money/$num < $min)
   {
       exit("红包的最小金额是0.01,你发的红包太小了");
   }
   $arr = [];//存所有的红包
   if($num == 1)
   {
       $arr[] = $money;
       return $arr;
   }
   $em = $money - $min * $num; //剩余金额 
   if($em == 0)
   {
       for($i=0;$i<$num;$i++)
       {
           $arr[] = $min;
       }
   }else
   {
       $tt = $num;//剩余没分配金额的红包的数量
       $total = 0;
       for($i=0;$i<$num;$i++)
       {
           //当是还剩最后一个红包时 把剩下的钱装进去
           if($tt == 1)
           {
               $h = $em;
           }else
           {
               if($i < ((int)$num/3))
               {
                   $h = randomFloat(0,$em/$tt*2);
               }else
               {
                   $h = randomFloat(0,$em/$tt);
               }
           }
           $arr[] = $h + $min;
           $em = $em - $h;
           $tt--;
       }
   }
   return $arr;
}
//-群红包分配








//-领取红包及接收转账模块
if ($t=="paycollect")
{
	$p_uid=$_POST['p_uid'];
	$p_tid=$_POST['p_tid'];
	$p_te=$_POST['p_te'];
	$p_money=$_POST['p_money'];
	$p_number=$_POST['p_number'];
	$p_remarks=$_POST['p_remarks'];
	$p_nickname=$_POST['p_nickname'];
	$p_amount_id=$_POST['p_amount_id'];
	
	if ($p_te==1){$te_title="转账";}if ($p_te==2){$te_title="红包";}if ($p_te==3){$te_title="群红包";}


	//验证汇款人信息，并记录汇款人ID
	$sql="select * from user where uid='$p_uid'  order by uid desc  LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$hk_uid=$row['uid'];
	$hk_nickname=$row['nickname'];
	if ($hk_uid!=$p_uid){print '{"code":"0","state":"1"}';exit;}


	//验证收款人信息，并记录收款人ID
	$sql="select * from user where uid='$p_tid' order by uid desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$sk_uid=$row['uid'];
	$sk_avatar=$row['avatar'];
		
	$session_name=session_name();
	$m_phpsessid=$_COOKIE[$session_name];
	$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$dl_uid=$row['uid'];
	
	
	//获得收款人昵称
	$sql="select * from user where uid='$dl_uid' order by uid desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$sk_nickname=$row['nickname'];
	
	
	
	if ($p_te=="1" or $p_te=="2")
	{
		if ($sk_uid!=$p_tid){print '{"code":"0","state":"2"}';exit;}
		
		if ($dl_uid==$sk_uid or $dl_uid==$hk_uid){}else{print '{"code":"0","state":"3"}';exit;}
		
		$sql="select * from amount where `id`='$p_amount_id' and `sub_type`='$p_te' and `money`='$p_money' and gp_gid='0' and  (`uid`='$hk_uid' or `to`='$sk_uid')  order by id desc LIMIT 1";
		$result=mysqli_query($conn,$sql);
		$row=mysqli_fetch_array($result);
		$kx_id=$row['id'];
		
		if ($row['gp_gid']>0){$kx_mid=$row['gp_gid'];}else{$kx_mid=$kx_id;}
		
		$kx_money=$row['money'];
		$kx_to=$row['to'];
		$kx_sub_type=$row['sub_type'];
		$kx_state=$row['state'];
		if ($kx_id!=$p_amount_id){print '{"code":"0","state":"4"}';exit;}
	}
	else
	{
		$sql="select * from amount where `id`='$p_amount_id' and `sub_type`='$p_te' and (`gp_money`='$p_money' or `money`='$p_money') and gp_gid='0' order by id asc LIMIT 1";
		$result=mysqli_query($conn,$sql);
		$row=mysqli_fetch_array($result);
		$kx_id=$row['id'];
		if ($row['gp_gid']>0){$kx_mid=$row['gp_gid'];}else{$kx_mid=$kx_id;}
		$kx_money=$row['money'];
		$kx_to=$row['to'];
		$kx_sub_type=$row['sub_type'];
		$kx_state=$row['state'];
		if ($kx_id!=$p_amount_id){print '{"code":"0","state":"5"}';exit;}
	}



	//获得收款人原有金额，并计算新余额
	$sql="select * from amount where uid='$dl_uid' order by id desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$my_balance=$row['balance']+0;



	//普通红包和转账的收支
	if ($kx_sub_type=="1" or $kx_sub_type=="2")
	{
			//print $kx_to."=".$dl_uid;
			if ($kx_to==$dl_uid and $kx_state==0)
			{	
					$type=1;//增加
					$sub_type=$p_te;
					$uid=$dl_uid;
					$to=0;
					$money=$kx_money;
					$balance=$my_balance+$kx_money;
					$remarks=$p_remarks;
					$timestamp=time();
					$state=9;
					
					//插入新记录
					$sql="INSERT INTO  amount (`type` ,`sub_type` ,`uid` ,`to` ,`money` ,`balance` , `remarks` ,`timestamp` ,`state`,`last_timestamp`,`gp_amount_id`)VALUES ('$type`',  '$sub_type`',  '$uid`',  '$to',  '$money',  '$balance',  '$remarks',  '$timestamp',  '$state',  '$timestamp',  '$kx_mid')";
					$result=mysqli_query($conn,$sql);
					$iid=mysqli_insert_id($conn);
					
					//将记录更新已收取
					$sql="update amount set state=9,last_timestamp='$timestamp' where id='$kx_id' and uid='$hk_uid' LIMIT 1";
					mysqli_query($conn,$sql);
					
					$message=$sk_nickname." 领取了 ".$hk_nickname." 的".$te_title;
					if ($iid>0){print '{"code":"0","state":"7","message":"'.$message.'"}';exit;}
			}
			else
			{
				print '{"code":"0","state":"6"}';exit;
			}
	}
	//普通红包和转账的收支
			
	

	//群多个红包的收支
	if ($kx_sub_type=="3")		
	{
			$sql="select * from amount where gp_uid='$dl_uid' and ( `id`='$kx_id' or `gp_gid`='$kx_id' ) order by id desc LIMIT 1";
			$result=mysqli_query($conn,$sql);
			$row=mysqli_fetch_array($result);
			$sz_id=$row['id'];
			//print '{"code":"0","state":"8","html":"'.$sz_id.'"}';exit;
			if ($sz_id>0){print '{"code":"0","state":"8"}';exit;}//你已收取过红包
			else
			{
					$sql="select * from amount where state='0' and (`id`='$kx_id' or `gp_gid`='$kx_id') order by id desc LIMIT 1";
					$result=mysqli_query($conn,$sql);
					$row=mysqli_fetch_array($result);
					$kx_id=$row['id'];
					if ($kx_id>0)
					{
							$kx_money=$row['money'];
							$kx_to=$row['to'];
							$kx_sub_type=$row['sub_type'];
	
							$kx_state=$row['state'];
											
							$type=1;//增加
							$sub_type=$p_te;
							$uid=$dl_uid;
							$to=0;
							$money=$kx_money;
							$balance=$my_balance+$kx_money;
							$remarks=$p_remarks;
							$timestamp=time();
							$state=9;
								
							//插入新记录
							$sql="INSERT INTO  amount (`type` ,`sub_type` ,`uid` ,`to` ,`money` ,`balance` , `remarks` ,`timestamp` ,`state`,`last_timestamp`,`gp_amount_id`)VALUES ('$type`',  '$sub_type`',  '$uid`',  '$to',  '$money',  '$balance',  '$remarks',  '$timestamp',  '$state',  '$timestamp',  '$kx_mid')";
							$result=mysqli_query($conn,$sql);
							$iid=mysqli_insert_id($conn);

							//将记录更新已收取							
							$sql="update amount set state=9,gp_uid='$dl_uid',last_timestamp='$timestamp' where id='$kx_id' and uid='$hk_uid' LIMIT 1";
							mysqli_query($conn,$sql);
							
							$message=$sk_nickname." 领取了 ".$hk_nickname." 的".$te_title;
							if ($iid>0){print '{"code":"0","state":"9","message":"'.$message.'"}';exit;}
					}
					else
					{
						print '{"code":"0","state":"10"}';exit;
					}
			}
					
	}
	//群多个红包的收支
}
//-领取红包及接收转账模块


//检查是否收取过红包
if ($t=="payoperation")
{
	$p_uid=$_POST['p_uid'];
	$p_tid=$_POST['p_tid'];
	$p_te=$_POST['p_te'];
	$p_money=$_POST['p_money'];
	$p_number=$_POST['p_number'];
	$p_remarks=$_POST['p_remarks'];
	$p_nickname=$_POST['p_nickname'];
	$p_amount_id=$_POST['p_amount_id'];





	//验证汇款人信息，并记录汇款人ID
	$sql="select * from user where uid='$p_uid' order by uid desc  LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$hk_uid=$row['uid'];
	$hk_nickname=$row['nickname'];
	if ($hk_uid!=$p_uid){print '{"code":"0","state":"1","my":"0"}';exit;}


	//验证收款人信息，并记录收款人ID
	$sql="select * from user where uid='$p_tid' order by uid desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$sk_uid=$row['uid'];
	$sk_avatar=$row['avatar'];
		
	$session_name=session_name();
	$m_phpsessid=$_COOKIE[$session_name];
	$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
	$result=mysqli_query($conn,$sql);
	$row=mysqli_fetch_array($result);
	$dl_uid=$row['uid'];

	//如果是发送人，则直接进入
	


	if ($p_te=="1" or $p_te=="2")
	{
		if ($dl_uid==$hk_uid){print '{"code":"0","state":"9"}';exit;}
		else
		{
			$sql="select * from amount where `id`='$p_amount_id' and `sub_type`='$p_te' and `money`='$p_money' and state='9' and  (`uid`='$hk_uid' or `to`='$sk_uid')  order by id desc LIMIT 1";
			$result=mysqli_query($conn,$sql);
			$row=mysqli_fetch_array($result);
			$sq_id=$row['id'];
		}
	}
	else
	{
		$sql="select * from amount where (`id`='$p_amount_id' or `gp_gid`='$p_amount_id') and `sub_type`='$p_te' and (`gp_money`='$p_money' or `money`='$p_money') and gp_uid='$dl_uid' order by id asc LIMIT 1";
		$result=mysqli_query($conn,$sql);
		$row=mysqli_fetch_array($result);
		$sq_id=$row['id'];
	}
	
	if ($sq_id>0){print '{"code":"0","state":"9"}';exit;}
	else{
		if ($p_uid==$dl_uid)
		{
			print '{"code":"0","state":"1","my":"1"}';exit;
		}else
		{
			print '{"code":"0","state":"1","my":"0"}';exit;
		}
	}

}
//检查是否收取过红包



?>