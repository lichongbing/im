<?php ob_start();require("class.php");?>
<?php if (1==2){?><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><?php }?>

<style>
.setblock
{
	font-size:0.35rem;
}

.setrow
{
	color:#333333;
	background-color:#FFF;
	padding:0.37rem 0.31rem 0.36rem 0.37rem;
}


.setrow i
{
	float:right;
	color:#989898;
	font-size:0.3rem;
}

.setline
{
	position:relative;
	margin:0rem 0.31rem 0rem 0.31rem;
}

.setline::before{
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

.inputtop
{
	margin-top:0rem;	
}

.inputline
{
	position:relative;
}

.inputline::before{
    content: '';
    position: absolute;
    top: 0;
    left: 0;  
    border-top: 1px solid #d6d6d6;
	border-bottom: 1px solid #d6d6d6;
    width: 200%;
    height: 200%;
    transform-origin: 0 0;
    transform: scale(0.5,0.5);
    box-sizing: border-box;
}

.input
{
	font-size:0.5rem;
	height:1rem;
	background:#FFF;
	width:calc(100% - 2px - 0.4rem);
	border:1px solid #FFF;
	outline:none;
	text-decoration:none;
	padding:0 0.2rem 0 0.2rem;
	color:#151515;
}

</style>


<?php $t=$_GET['t'];if ($t=="listshow"){?>
<body>
    <div id="selectheader" class="im_my_header"><i class="iconfont icon-fanhui1" onClick="layer.closeAll();"></i>安全设置</div>
	
    <div class="setblock">
    	<div class="setrow" onClick="Ns_safesetting_password()">登录密码<i class="iconfont icon-fanhui1-copy"></i></div>
        <div class="setline"></div>
        <div class="setrow" onClick="Ns_safesetting_safepassword()">交易密码<i class="iconfont icon-fanhui1-copy"></i></div>
    </div>
    
</body>       
<?php }?>


<?php if ($t=="password"){?>
<body>
    <div id="selectheader" class="im_my_header"><i class="iconfont icon-fanhui1" onClick="layer.closeAll();"></i><b>设置登录密码</b><span onClick="Ns_save('password')">确定</span></div>
	
    <div class="inputline inputtop"></div>
    <input id="password" name="password" class="input">
     <div class="inputline"></div>
</body>   
<?php }?>


<?php if ($t=="safepassword"){?>
<body>
    <div id="selectheader" class="im_my_header"><i class="iconfont icon-fanhui1" onClick="layer.closeAll();"></i><b>设置交易密码</b><span onClick="Ns_save('safepassword')">确定</span></div>
	
    <div class="inputline inputtop"></div>
    <input id="safepassword" name="safepassword" class="input">
     <div class="inputline"></div>
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
