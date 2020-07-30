<?php

namespace app\api\controller;

use think\Db;

/**
 * 推送服务
 * @package app\api\controller
 */
class Push extends Base
{
    /**
     * 触发推送
     *
     * @return string
     */
    public function emit()
    {
        $post = $this->_post();
        // 检查必要字段是否为空
        $required = ['channel', 'event'];
        foreach ($required as $key) {
            if (!isset($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }

        $channel = $post['channel'];
        $event   = $post['event'];
        $data    = isset($post['data']) ? json_decode($post['data']) : [];

        $push = new \app\api\model\Push();
        $push->emit($channel, $event, $data);

        return $this->json(0);
    }

    /**
     * 验证private订阅
     */
    public function auth()
    {

        header('Content-Type: application/json');
        $setting = Db::table('setting')->field('appkey, appsecret, api_address')->find();
        $url_info = parse_url($setting['api_address']);
        $option = array(
            'host' => $url_info['host'],
            'port' => isset($url_info['port']) ? $url_info['port'] : 2060,
        );
        $app_key    = $setting['appkey'];
        $app_secret = $setting['appsecret'];
        $app_id     = 1028;

        $woker = new \WokerAPI($app_key, $app_secret, $app_id, $option);
        echo $woker->auth($_POST['channel_name'], $_POST['socket_id']);
        die;
    }

}
