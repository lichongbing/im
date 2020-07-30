<?php

namespace app\h5\controller;

/**
 * 消息类
 *
 * @package app\api\controller
 */
class Message extends Base
{
    /**
     * 发送消息
     *
     * @return string
     */
    public function send()
    {
        $message = new \app\api\controller\Message();
        return $message->send();
    }

    /**
     * 撤回消息
     *
     * @return string
     */
    public function revoke()
    {
        $message = new \app\api\controller\Message();
        return $message->revoke();
    }

    /**
     * 获得与某个用户或群的消息
     *
     * @return string
     */
    public function get()
    {
        $_POST['id']   = $_GET['id'];
        $_POST['type'] = $_GET['type'];
        $message = new \app\api\controller\Message();
        return $message->get();
    }

    /**
     * 获得与某个用户或群的未读消息数
     *
     * @return string
     */
    public function unreadcount()
    {
        $message = new \app\api\controller\Message();
        return $message->unreadcount();
    }

    /**
     * 更新消息最后阅读时间
     *
     * @return array
     */
    public function updateLastReadTime()
    {
        $message = new \app\api\controller\Message();
        return $message->updateLastReadTime();
    }
	
	/*zedit新增支付功能*/
	public function payRp()
    {
        $message = new \app\api\controller\Message();
        return $message->payRp();
    }
	
	public function payTa()
    {
        $message = new \app\api\controller\Message();
        return $message->payTa();
    }
	/*zedit新增支付功能*/




}
