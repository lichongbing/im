<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: zhf <zhf@cpccc.com>
// +----------------------------------------------------------------------
// | Law: 未经官方允许不得二次销售，否则按侵犯著作权诉讼
// +----------------------------------------------------------------------


// [ 应用入口文件 ]
ini_set('session.gc_maxlifetime', 432000);
ini_set('session.cookie_lifetime', 432000);
ini_set('session.gc_probability',1);
ini_set('session.gc_divisor',1000);
ini_set("session.cookie_httponly", 1);

// 定义应用目录
define('APP_PATH', __DIR__ . '/../application/');
// 配置文件目录
define('CONF_PATH', __DIR__.'/../config/');
// 加载框架引导文件
require __DIR__ . '/../thinkphp/start.php';
