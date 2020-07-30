<?php

namespace app\h5\controller;

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
    /*public function emit()
    {
        $push = new \app\api\controller\Push();
        return $push->emit();
    }*/

    /**
     * 验证private订阅
     */
    public function auth()
    {
        $push = new \app\api\controller\Push();
        $push->auth();
    }

}
