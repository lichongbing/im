<?php

namespace app\h5\controller;

/**
 * 好友相关接口
 * @package app\api\controller
 */
class Friend extends Base
{

    /**
     * 加好友申请
     *
     * @return string
     */
    public function apply()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->apply();
    }

    /**
     * 获取好友申请历史
     *
     * @return string
     */
    public function applylist()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->applylist();
    }

    /**
     * 好友列表(通讯录)
     */
    public function getlist()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->getlist();
    }

    /**
     * 未处理好友申请数
     */
    public function applycount()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->applycount();
    }

    /**
     * 获取好友申请详情
     *
     * @return string
     */
    public function applydetail()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->applydetail();
    }

    /**
     * 设置好友备注
     *
     * @return string
     */
    public function remark()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->remark();
    }

    /**
     * 同意好友申请
     *
     * @return string
     */
    public function agree()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->agree();
    }

    /**
     * 拒绝好友申请
     *
     * @return string
     */
    public function refuse()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->refuse();
    }

    /**
     * 执行互相添加好友操作
     *
     * @return string
     */
    public function add()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->add();
    }

    /**
     * 解除好友好友关系
     *
     * @return string
     */
    public function delete()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->delete();
    }

    /**
     * 更新好友信息
     */
    public function update()
    {
        $friend = new \app\api\controller\Friend();
        return $friend->update();
    }

}
