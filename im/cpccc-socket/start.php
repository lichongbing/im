<?php
use Workerman\Worker;
use Workerman\Lib\Timer;

// composer autoload
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/src/Woker.php';
require_once __DIR__ . '/config.php';

// 走wss
if ($ssl_local_pk && $ssl_local_cert) {
    echo "============== Websocket service with SSL ==============\r\n";
    if (!is_file($ssl_local_pk)) exit("file $ssl_local_pk not exist\n");
    if (!is_file($ssl_local_cert)) exit("file $ssl_local_cert not exist\n");
    $context = array(
        // 更多ssl选项请参考手册 http://php.net/manual/zh/context.ssl.php
        'ssl' => array(
            // 请使用绝对路径
            'local_cert'                 => $ssl_local_cert, // 也可以是crt文件
            'local_pk'                   => $ssl_local_pk,
            'verify_peer'                => false,
            // 'allow_self_signed' => true, //如果是自签名证书需要开启此选项
        )
    );
    class_alias('\Woker\CpccccSocket', '\Protocols\CpccccSocket');
    $pusher = new Woker\Woker("CpccccSocket://0.0.0.0:$websocket_port", $context);
    $pusher->transport = 'ssl';
// 走ws
} else {
    class_alias('\Woker\CpccccSocket', '\Protocols\CpccccSocket');
    $pusher = new Woker\Woker("CpccccSocket://0.0.0.0:$websocket_port");
}

$pusher->apiListen = "http://0.0.0.0:$api_port";
$pusher->appInfo = array(
    $app_key => array(
        'channel_hook' => !empty($domain) ? "$domain/online.php" : '',
        'app_secret'   => $app_secret,
    ),
);

$pusher->availableDomains = !empty($available_domains) ? $available_domains : [];

// 只能是1
$pusher->count = 1;

Worker::runAll();
