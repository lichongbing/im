<?php
use think\Db;
if (!ini_get('date.timezone')) {
    ini_set('date.timezone','Asia/Shanghai');
}
define('APP_PATH', __DIR__ . '/../application/');
define('CONF_PATH', __DIR__.'/../config/');
require __DIR__ . '/../thinkphp/base.php';
$db_config = include __DIR__ . '/../config/database.php';
$db = Db::connect($db_config);

$get = $_GET;
$uid_array = explode(',', $get['uid']);
$uid_array = array_slice($uid_array, 0, 9);
$holdplace = [];
foreach ($uid_array as $key => $value) {
    $uid_array[$key] = (int)$value;
    $holdplace[] = '?';
}



//$avatar_array = $db->query('select avatar from user where uid in ('.implode(',', $holdplace).')', $uid_array);/*zedit(原始)*/
//print_r($avatar_array);print "<br />----------------<br />";


/*zedit(修改，按顺序显示头像)*/
$mi=0;
for ($mi=0; $mi<count($uid_array); $mi++)
{
	@$uid=$uid_array[$mi];	
	$avatar_array_a[$mi] = $db->query("select avatar from user where uid='$uid'");	
}
$avatar_array=array_reduce($avatar_array_a, 'array_merge', array());
//print_r($avatar_array);
/*zedit(修改，按顺序显示头像)*/









$pic_list = [];
foreach ($avatar_array as $avatar_url) {
    $avatar_url = $avatar_url['avatar'];
    $path = parse_url($avatar_url, PHP_URL_PATH);
    if (!$path || !is_file($real_path = __DIR__ . $path)) {
        $real_path = __DIR__ . '/cssjs/img/avatar.jpg';
    }
    $pic_list[] = $real_path;
}

$bg_w = 150; // 背景图片宽度
$bg_h = 150; // 背景图片高度

$background = imagecreatetruecolor($bg_w,$bg_h); // 背景图片
$color = imagecolorallocate($background, 220, 220, 220); // 为真彩色画布创建白色背景，再设置为透明
imagefill($background, 0, 0, $color);
imageColorTransparent($background, $color);

$pic_count = count($pic_list);
$lineArr = array(); // 需要换行的位置
$space_x = 3;
$space_y = 3;
$line_x = 0;
switch($pic_count) {
    case 1: // 正中间
        $start_x = intval($bg_w/4); // 开始位置X
        $start_y = intval($bg_h/4); // 开始位置Y
        $pic_w = intval($bg_w/2); // 宽度
        $pic_h = intval($bg_h/2); // 高度
        break;
    case 2: // 中间位置并排
        $start_x = 2;
        $start_y = intval($bg_h/4) + 3;
        $pic_w = intval($bg_w/2) - 5;
        $pic_h = intval($bg_h/2) - 5;
        $space_x = 5;
        break;
    case 3:
        $start_x = 40; // 开始位置X
        $start_y = 5; // 开始位置Y
        $pic_w = intval($bg_w/2) - 5; // 宽度
        $pic_h = intval($bg_h/2) - 5; // 高度
        $lineArr = array(2);
        $line_x = 4;
        break;
    case 4:
        $start_x = 4; // 开始位置X
        $start_y = 5; // 开始位置Y
        $pic_w = intval($bg_w/2) - 5; // 宽度
        $pic_h = intval($bg_h/2) - 5; // 高度
        $lineArr = array(3);
        $line_x = 4;
        break;
    case 5:
        $start_x = 30; // 开始位置X
        $start_y = 30; // 开始位置Y
        $pic_w = intval($bg_w/3) - 5; // 宽度
        $pic_h = intval($bg_h/3) - 5; // 高度
        $lineArr = array(3);
        $line_x = 5;
        break;
    case 6:
        $start_x = 5; // 开始位置X
        $start_y = 30; // 开始位置Y
        $pic_w = intval($bg_w/3) - 5; // 宽度
        $pic_h = intval($bg_h/3) - 5; // 高度
        $lineArr = array(4);
        $line_x = 5;
        break;
    case 7:
        $start_x = 53; // 开始位置X
        $start_y = 5; // 开始位置Y
        $pic_w = intval($bg_w/3) - 5; // 宽度
        $pic_h = intval($bg_h/3) - 5; // 高度
        $lineArr = array(2,5);
        $line_x = 5;
        break;
    case 8:
        $start_x = 30; // 开始位置X
        $start_y = 5; // 开始位置Y
        $pic_w = intval($bg_w/3) - 5; // 宽度
        $pic_h = intval($bg_h/3) - 5; // 高度
        $lineArr = array(3,6);
        $line_x = 5;
        break;
    case 9:
        $start_x = 5; // 开始位置X
        $start_y = 5; // 开始位置Y
        $pic_w = intval($bg_w/3) - 5; // 宽度
        $pic_h = intval($bg_h/3) - 5; // 高度
        $lineArr = array(4,7);
        $line_x = 5;
        break;
}
foreach( $pic_list as $k=>$pic_path ) {
    $kk = $k + 1;
    if ( in_array($kk, $lineArr) ) {
        $start_x = $line_x;
        $start_y = $start_y + $pic_h + $space_y;
    }
    $pathInfo = pathinfo($pic_path);
    switch( strtolower($pathInfo['extension']) ) {
        // 通过后缀判断类型不准，统一用imagecreatefromstring方法
        /*case 'jpg':
        case 'jpeg':
            $imagecreatefromjpeg = 'imagecreatefromjpeg';
            break;
        case 'png':
            $imagecreatefromjpeg = 'imagecreatefrompng';
            break;*/
        case 'gif':
        default:
            $imagecreatefromjpeg = 'imagecreatefromstring';
            $pic_path = file_get_contents($pic_path);
            break;
    }
    $resource = $imagecreatefromjpeg($pic_path);
    // $start_x,$start_y copy图片在背景中的位置
    // 0,0 被copy图片的位置
    // $pic_w,$pic_h copy后的高度和宽度
    imagecopyresized($background,$resource,$start_x,$start_y,0,0,$pic_w,$pic_h,imagesx($resource),imagesy($resource)); // 最后两个参数为原始图片宽度和高度，倒数两个参数为copy时的图片宽度和高度
    $start_x = $start_x + $pic_w + $space_x;
}



header('Content-type: image/jpeg');
// 头像30天过期
header("Cache-Control: public");
header("Pragma: cache");
$offset =86400;
header("Expires: ".gmdate("D, d M Y H:i:s", time() + $offset)." GMT");
imagejpeg($background);
die;
