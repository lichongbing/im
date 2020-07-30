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

$cpcccim_setting = $db->query('select appkey,appsecret,api_address from setting limit 1');
if (!$cpcccim_setting) {
    header("Status: 200 OK");
    die;
}
$cpcccim_setting = $cpcccim_setting[0];

$app_key = $cpcccim_setting['appkey'];
$app_secret = $cpcccim_setting['appsecret'];
$api_address_info = explode(':', $cpcccim_setting['api_address']);
$api_address = $api_address_info[0];
$api_port = !empty($api_address_info[1]) ? $api_address_info[1] : 2060;
$pusher = new WokerAPI(
    $app_key,
    $app_secret,
    1024,
    [],
    $api_address,
    $api_port
);

$webhook_signature = $_SERVER['HTTP_X_WOKER_SIGNATURE'];
$body = file_get_contents('php://input');
$expected_signature = hash_hmac('sha256', $body, $app_secret, false);
if ($webhook_signature !== $expected_signature) {
    header("Status: 401 Not authenticated");
    die;
}

$uid_online_array = $uid_offline_array = [];
$payload = json_decode($body, true);
foreach ($payload['events'] as $event) {
    // 通知在线
    if ($event["name"] == "channel_added") {
        if (strpos($event['channel'], 'user-') === 0) {
            $uid = str_replace('user-', '', $event['channel']);
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $uid)) {
                $uid_online_array[$uid] = $uid;
            } else {
                error_log("online.php uid:$uid 非法");
            }
        }
    }
    // 通知离线
    else if ($event['name'] == 'channel_removed') {
        // 用户 离线
        if (strpos($event['channel'], 'user-') === 0) {
            $uid = str_replace('user-', '', $event['channel']);
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $uid)) {
                $uid_offline_array[$uid] = $uid;
            } else {
                error_log("online.php uid:$uid 非法");
            }
        }
    }
}

if ($uid_online_array) {
    $db->execute("update `user` set state='online' where uid in ('" . implode("','", $uid_online_array) . "')");
}
if ($uid_offline_array) {
    $time = time();
    $db->execute("update `user` set state='offline', `logout_timestamp`=$time where uid in ('" . implode("','", $uid_offline_array) . "')");
}

header("Status: 200 OK");
