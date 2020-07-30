<?php
/**
 * Handler File Class
 *
 * @author liliang <liliang@wolive.cc>
 * @email liliang@wolive.cc
 * @date 2017/06/01
 */

namespace app\api\model;

use think\Model;
use think\Db;

/**
 * 推送相关服务
 */
class Push extends Model
{
    protected $wokerInstance = null;

    /**
     * 推送服务构造函数
     *
     */
    public function __construct()
    {
        parent::__construct();
        $setting = Db::table('setting')->field('appkey, appsecret, api_address')->find();
        $url_info = parse_url($setting['api_address']);
        $option = array(
            'host' => $url_info['host'],
            'port' => isset($url_info['port']) ? $url_info['port'] : 2060,
        );
        $app_key    = $setting['appkey'];
        $app_secret = $setting['appsecret'];
        $app_id     = 1028;
        $this->wokerInstance = new \WokerAPI($app_key, $app_secret, $app_id, $option);
    }

    /**
     * 调用推送
     *
     * @param $channel
     * @param $event
     * @param $data
     *
     * @return void
     */
    public function emit($channel, $event, $data)
    {
        return $this->wokerInstance->emit($channel, $event, $data);
    }

}
