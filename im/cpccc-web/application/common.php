<?php

use think\Db;
use think\Request;
use think\Response;


//格式化打印函数
function p($array){
	dump($array,1,'<pre style=font-size:18px;color:#00ae19;>',0);
}