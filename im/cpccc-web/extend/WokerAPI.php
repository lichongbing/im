<?php

class WokerAPI
{
    public static $VERSION = '1.0.0';

    private $settings = array(
        'scheme'       => 'http',
        'port'         => 80,
        'timeout'      => 30,
        'debug'        => false,
        'curl_options' => array(),
    );

    private $logger = null;

    private $ch = null;

    /**
     * 构造函数.
     *
     * 初始化 Woker 实例
     *
     * @param string $auth_key
     * @param string $secret
     * @param int    $app_id
     * @param array $options [optional]
     *                         scheme - http 或者 https
     *                         host - 地址
     *                         port - 端口
     *                         timeout - 超时时间
     *                         encrypted - 设置后scheme默认是https并且port是443.
     */
    public function __construct($auth_key, $secret, $app_id, $options = array())
    {
        $this->checkCompatibility();

        if (is_bool($options) === true) {
            $options = array(
                'debug' => $options,
            );
        }

        if (
            isset($options['encrypted']) &&
            $options['encrypted'] === true &&
            !isset($options['scheme']) &&
            !isset($options['port'])
        ) {
            $options['scheme'] = 'https';
            $options['port'] = 443;
        }

        $this->settings['auth_key'] = $auth_key;
        $this->settings['secret'] = $secret;
        $this->settings['app_id'] = $app_id;
        $this->settings['base_path'] = '/apps/'.$this->settings['app_id'];

        foreach ($options as $key => $value) {
            if (isset($this->settings[$key])) {
                $this->settings[$key] = $value;
            }
        }

        if (isset($options['notification_host'])) {
            $this->settings['notification_host'] = $options['notification_host'];
        } else {
            $this->settings['notification_host'] = 'nativepush-cluster1.woker.cc';
        }

        if (isset($options['notification_scheme'])) {
            $this->settings['notification_scheme'] = $options['notification_scheme'];
        } else {
            $this->settings['notification_scheme'] = 'https';
        }

        if (!array_key_exists('host', $this->settings)) {
            if (array_key_exists('host', $options)) {
                $this->settings['host'] = $options['host'];
            } elseif (array_key_exists('cluster', $options)) {
                $this->settings['host'] = 'api-' . $options['cluster'] . '.woker.cc';
            } else {
                $this->settings['host'] = 'api.woker.cc';
            }
        }

        $this->settings['host'] =
            preg_replace('/http[s]?\:\/\//', '', $this->settings['host'], 1);
    }

    /**
     * 获取配置.
     *
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * 设置日志类实例.
     *
     * @return void
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * 打日志.
     *
     * @param string $msg
     *
     * @return void
     */
    private function log($msg)
    {
        if (is_null($this->logger) === false) {
            $this->logger->log('Woker: ' . $msg);
        }
    }

    /**
     * 检查环境是否满足
     *
     * @throws WokerException
     *
     * @return void
     */
    private function checkCompatibility()
    {
        if (!extension_loaded('curl')) {
            throw new WokerException('The Woker library requires the PHP cURL module. Please ensure it is installed');
        }

        if (!extension_loaded('json')) {
            throw new WokerException('The Woker library requires the PHP JSON module. Please ensure it is installed');
        }

        if (!in_array('sha256', hash_algos())) {
            throw new WokerException('SHA256 appears to be unsupported - make sure you have support for it, or upgrade your version of PHP.');
        }
    }

    /**
     * 检查channels数量及合法性.
     *
     * @param string[] $channels
     *
     * @throws WokerException
     *
     * @return void
     */
    private function validateChannels($channels)
    {
        if (count($channels) > 100) {
            throw new WokerException('An event can be triggered on a maximum of 100 channels in a single call.');
        }

        foreach ($channels as $channel) {
            $this->validateChannel($channel);
        }
    }

    /**
     * 检查频道名称是否合法
     *
     * @param $channel
     *
     * @throws WokerException
     *
     * @return void
     */
    private function validateChannel($channel)
    {
        if (!preg_match('/\A[-a-zA-Z0-9_=@,.;]+\z/', $channel)) {
            throw new WokerException('Invalid channel name ' . $channel);
        }
    }

    /**
     * 检查socket_id是否合法
     *
     * @param string $socket_id The socket ID to validate
     *
     * @throws WokerException if $socket_id is invalid
     */
    private function validateSocketId($socket_id)
    {
        if ($socket_id !== null && !preg_match('/\A\d+\.\d+\z/', $socket_id)) {
            throw new WokerException('Invalid socket ID ' . $socket_id);
        }
    }

    /**
     * 创建curl实例.
     *
     * @param string $domain
     * @param string $s_url
     * @param string $request_method
     * @param array $query_params
     *
     * @throws WokerException
     *
     * @return resource
     */
    private function createCurl($domain, $s_url, $request_method = 'GET', $query_params = array())
    {
        // Create the signed signature...
        $signed_query = self::buildAuthQueryString(
            $this->settings['auth_key'],
            $this->settings['secret'],
            $request_method,
            $s_url,
            $query_params
        );

        $full_url = $domain.$s_url.'?'.$signed_query;

        $this->log('createCurl( ' . $full_url . ' )');

        // Create or reuse existing curl handle
        if (null === $this->ch) {
            $this->ch = curl_init();
        }

        if ($this->ch === false) {
            throw new WokerException('Could not initialise cURL!');
        }

        $ch = $this->ch;

        // curl handle is not reusable unless reset
        if (function_exists('curl_reset')) {
            curl_reset($ch);
        }

        // Set cURL opts and execute request
        curl_setopt($ch, CURLOPT_URL, $full_url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Expect:',
            'X-Woker-Library: woker-http-php ' . self::$VERSION,
        ));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->settings['timeout']);
        if ($request_method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
        } elseif ($request_method === 'GET') {
            curl_setopt($ch, CURLOPT_POST, 0);
        } // Otherwise let the user configure it

        // Set custom curl options
        if (!empty($this->settings['curl_options'])) {
            foreach ($this->settings['curl_options'] as $option => $value) {
                curl_setopt($ch, $option, $value);
            }
        }

        return $ch;
    }

    /**
     * 获取curl结果.
     */
    private function execCurl($ch)
    {
        $response = array();

        $response['body'] = curl_exec($ch);
        $response['status'] = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response['body'] === false || $response['status'] < 200 || 400 <= $response['status']) {
            $this->log('execCurl error: ' . curl_error($ch));
        }

        $this->log('execCurl response: ' . print_r($response, true));

        return $response;
    }


    private function notificationDomain()
    {
        return $this->settings['notification_scheme'].'://'.$this->settings['notification_host'];
    }


    private function ddnDomain()
    {
        return $this->settings['scheme'] . '://' . $this->settings['host'] . ':' . $this->settings['port'];
    }

    /**
     * 创建 HMAC'd 验证字符串.
     *
     * @param string $auth_key
     * @param string $auth_secret
     * @param string $request_method
     * @param string $request_path
     * @param array  $query_params
     * @param string $auth_version   [optional]
     * @param string $auth_timestamp [optional]
     *
     * @return string
     */
    public static function buildAuthQueryString($auth_key, $auth_secret, $request_method, $request_path,
                                                $query_params = array(), $auth_version = '1.0', $auth_timestamp = null)
    {
        $params = array();
        $params['auth_key'] = $auth_key;
        $params['auth_timestamp'] = (is_null($auth_timestamp) ? time() : $auth_timestamp);
        $params['auth_version'] = $auth_version;

        $params = array_merge($params, $query_params);
        ksort($params);

        $string_to_sign = "$request_method\n" . $request_path . "\n" . self::arrayImplode('=', '&', $params);

        $auth_signature = hash_hmac('sha256', $string_to_sign, $auth_secret, false);

        $params['auth_signature'] = $auth_signature;
        ksort($params);

        $auth_query_string = self::arrayImplode('=', '&', $params);

        return $auth_query_string;
    }

    /**
     * arrayImplode
     *
     * @param string $glue
     * @param string $separator
     * @param array $array
     *
     * @return string The imploded array
     */
    public static function arrayImplode($glue, $separator, $array)
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

    /**
     * 触发一个事件
     *
     * @param array|string $channels
     * @param string $event
     * @param mixed $data Event data
     * @param string $socket_id [optional]
     * @param bool $debug [optional]
     * @param bool $already_encoded [optional]
     *
     * @return bool|array
     */
    public function emit($channels, $event, $data, $socket_id = null, $debug = false, $already_encoded = false)
    {
        if (is_string($channels) === true) {
            $this->log('->trigger received string channel "'.$channels.'". Converting to array.');
            $channels = array($channels);
        }

        $this->validateChannels($channels);
        $this->validateSocketId($socket_id);

        $query_params = array();

        $s_url = $this->settings['base_path'].'/events';

        $data_encoded = $already_encoded ? $data : json_encode($data);

        if (!$data_encoded) {
            $this->Log('Failed to perform json_encode on the the provided data: '.print_r($data, true));
        }

        $post_params = array();
        $post_params['name'] = $event;
        $post_params['data'] = $data_encoded;
        $post_params['channels'] = $channels;

        if ($socket_id !== null) {
            $post_params['socket_id'] = $socket_id;
        }

        $post_value = json_encode($post_params);

        $query_params['body_md5'] = md5($post_value);

        $ch = $this->createCurl($this->ddnDomain(), $s_url, 'POST', $query_params);

        $this->log('trigger POST: '.$post_value);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);

        $response = $this->execCurl($ch);
        return $response;
    }

    /**
     * 触发多个事件（暂时未实现）
     *
     * @param array $batch
     * @param bool  $debug           [optional]
     * @param bool  $already_encoded [optional]
     *
     * @return bool|string
     */
    public function emitBatch($batch = array(), $debug = false, $already_encoded = false)
    {
        // 暂时未实现
        return true;

        $query_params = array();

        $s_url = $this->settings['base_path'].'/batch_events';

        if (!$already_encoded) {
            foreach ($batch as $key => $event) {
                if (!is_string($event['data'])) {
                    $batch[$key]['data'] = json_encode($event['data']);
                }
            }
        }

        $post_params = array();
        $post_params['batch'] = $batch;

        $post_value = json_encode($post_params);

        $query_params['body_md5'] = md5($post_value);

        $ch = $this->createCurl($this->ddnDomain(), $s_url, 'POST', $query_params);

        $this->log('trigger POST: '.$post_value);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);

        $response = $this->execCurl($ch);

        if ($response['status'] === 200 && $debug === false) {
            return true;
        } elseif ($debug === true || $this->settings['debug'] === true) {
            return $response;
        } else {
            return false;
        }
    }

    /**
     *    获取某个频道信息
     *
     * @param string $channel
     * @param array $params 例如 $params = array( 'info' => 'connection_count' )
     *
     *	@return object
     */
    public function getChannelInfo($channel, $params = array())
    {
        $this->validateChannel($channel);

        $response = $this->get('/channels/'.$channel, $params);

        if ($response['status'] === 200) {
            $response = json_decode($response['body']);
        } else {
            $response = false;
        }

        return $response;
    }

    /**
     * 获取所有频道(暂时未实现)
     *
     * @param array $params 例如 $params = array( 'info' => 'connection_count' )
     *
     * @return array
     */
    public function getChannels($params = array())
    {
        // 暂时未实现
        return array();

        $response = $this->get('/channels', $params);

        if ($response['status'] === 200) {
            $response = json_decode($response['body']);
            $response->channels = get_object_vars($response->channels);
        } else {
            $response = false;
        }

        return $response;
    }

    /**
     * GET 方法请求某个url.
     *
     * @param string $path
     * @param array $params
     *
     * @return bool
     */
    public function get($path, $params = array())
    {
        $s_url = $this->settings['base_path'].$path;

        $ch = $this->createCurl($this->ddnDomain(), $s_url, 'GET', $params);

        $response = $this->execCurl($ch);

        if ($response['status'] === 200) {
            $response['result'] = json_decode($response['body'], true);
        } else {
            $response = false;
        }

        return $response;
    }

    /**
     * 创建一个socket签名.
     *
     * @param string $socket_id
     * @param string $custom_data
     *
     * @return string
     */
    public function auth($channel, $socket_id, $custom_data = null)
    {
        $this->validateChannel($channel);
        $this->validateSocketId($socket_id);

        if ($custom_data) {
            $signature = hash_hmac('sha256', $socket_id.':'.$channel.':'.$custom_data, $this->settings['secret'], false);
        } else {
            $signature = hash_hmac('sha256', $socket_id.':'.$channel, $this->settings['secret'], false);
        }

        $signature = array('auth' => $this->settings['auth_key'].':'.$signature);
        if ($custom_data) {
            $signature['channel_data'] = $custom_data;
        }

        return json_encode($signature);
    }

    /**
     * 创建一个presence频道签名.
     *
     * @param string $channel
     * @param string $socket_id
     * @param string $user_id
     * @param mixed  $user_info
     *
     * @return string
     */
    public function presenceAuth($channel, $socket_id, $user_id, $user_info = null)
    {
        $user_data = array('user_id' => $user_id);
        if ($user_info) {
            $user_data['user_info'] = $user_info;
        }

        return $this->auth($channel, $socket_id, json_encode($user_data));
    }

    /**
     * 发送一个通知.
     *
     * @param array $interests
     * @param array $data
     * @param bool  $debug
     *
     * @throws WokerException if validation fails.
     *
     * @return bool|string
     */
    public function notify($interests, $data = array(), $debug = false)
    {
        $query_params = array();

        if (is_string($interests)) {
            $this->log('->notify received string interests "'.$interests.'". Converting to array.');
            $interests = array($interests);
        }

        if (count($interests) === 0) {
            throw new WokerException('$interests array must not be empty');
        }

        $data['interests'] = $interests;

        $post_value = json_encode($data);

        $query_params['body_md5'] = md5($post_value);

        $notification_path = '/server_api/v1'.$this->settings['base_path'].'/notifications';
        $ch = $this->createCurl($this->notificationDomain(), $notification_path, 'POST', $query_params);

        $this->log('trigger POST (Native notifications): '.$post_value);

        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_value);

        $response = $this->execCurl($ch);

        if ($response['status'] === 202 && $debug === false) {
            return true;
        } elseif ($debug === true || $this->settings['debug'] === true) {
            return $response;
        } else {
            return false;
        }
    }
}

class WokerException extends Exception
{
}

class WokerInstance
{
    private static $instance = null;
    private static $app_id = '';
    private static $secret = '';
    private static $api_key = '';

    public static function getWoker()
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new Woker(
            self::$api_key,
            self::$secret,
            self::$app_id
        );

        return self::$instance;
    }
}
