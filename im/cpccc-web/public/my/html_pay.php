<?php ob_start();require("class.php");?>
<?php if (1==2){?><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><?php }?>
<link rel="stylesheet" href="/cssjs/css/my_2019.css" />
<?php 
$t=$_GET['t'];
$te=$_GET['te'];
$toid=$_GET['toid'];
if ($te==1){$te_title="好友转账";}if ($te==2){$te_title="普通红包";}if ($te==3){$te_title="群组红包";}

if ($t=="show")
{
?>
<body>
    <div class="im_my_header"><i class="iconfont icon-fanhui1" onClick="layer.closeAll()"></i><?php print $te_title;?></div>
    
    <div id="msgbox" class="msg" style="display:none"></div>
    <input name="te" id="te" value="<?php print $te;?>" type="hidden">
    <input name="toid" id="toid" value="<?php print $toid;?>" type="hidden">
	<?php if ($te==2 or $te==3){?>    
        <div class="im_pay">
          <div class="im_pay_left">红包金额</div>
          <div class="im_pay_right"><input name="money" id="money"  placeholder="0.00" type="tel" onChange="Pay_Num(this,2);Pay_Show();" onKeyUp="Pay_Num(this,2);Pay_Show();" onBlur="Pay_Num(this,2);Pay_Show();" autocomplete="off"><span>元</span></div>
        </div>

		<?php if ($te==3){?>
        <div class="im_pay">
          <div class="im_pay_left">红包个数</div>
          <div class="im_pay_right"><input name="number" id="number" type="tel" onChange="Pay_Num(this,0);Pay_Show();" onKeyUp="Pay_Num(this,0);Pay_Show();" onBlur="Pay_Num(this,0);Pay_Show();" autocomplete="off" value="10"><span>个</span></div>
        </div>
		<?php }else{?>
        <input name="number" id="number" value="1" type="hidden">
        <?php }?>
        
        <div class="im_pay_big">
            <input name="remarks" id="remarks" class="" placeholder="恭喜发财，大吉大利" type="text" autocomplete="off">
        </div>
        
        <div class="money_box"><i>￥</i><span id="nowmoney">0.00</span></div>
        
        <div class="submit_box"><input name="moneysubmit" id="moneysubmit" class="submit_no" type="submit" value="塞钱进红包" onClick="Pay_SafePass(<?php print $te;?>,'Pay_Confirm()');" ></div>
	<?php }
	if ($te==1){?>
    	<?php
			$sql="select * from user where uid='$toid' LIMIT 1";
			$result=mysqli_query($conn,$sql);
			$row=mysqli_fetch_array($result);
			$user_avatar=$row['avatar'];
			$user_nickname=$row['nickname'];
		?>
    	<div class="useravatar"><img src="<?php print $user_avatar;?>"></div>
        <div class="user_nickname"><span><?php print $user_nickname;?></span></div>
        
        <div class="tabox">
        
        	<div class="ta_title">转账金额</div>
            <div class="ta_pay">
				<div class="ta_pay_line"><span>￥</span><input name="money" id="money" type="tel" onChange="Pay_No()" onKeyUp="Pay_Num(this,2);Pay_No();" onBlur="Pay_Num(this,2);Pay_No();" autofocus autocomplete="off"></div>
            </div>
			<div class="border-line"></div>
            
        	<div class="ta_title" style="color:#0f3373" onClick="javascript:$('#taremarksbox').toggle();">添加转账说明</div>
            <div id="taremarksbox" style="display:none">
                <div class="ta_pay_mini">
                    <input name="remarks" id="remarks" type="text" placeholder="最多10个汉字" autocomplete="off">
                </div>
                <div class="border-line"></div>
            </div>
            <input name="number" id="number" value="1" type="hidden">
            <div class="submit_box"><input name="moneysubmit" id="moneysubmit" class="tasubmit_no" type="submit" value="确认转账" onClick="Pay_SafePass(<?php print $te;?>,'Pay_Confirm()');"></div>
        </div>
       
    <?php }?>
</body>
<?php require("../cssjs/safepass/safepass.php");?>
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
