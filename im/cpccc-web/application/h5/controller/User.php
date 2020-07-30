<?php

namespace app\h5\controller;

class User extends Base
{

    /**
     * 获得某个用户信息
     */
    public function detail()
    {
        if (empty($_GET['uid'])) {
            return $this->json(1, '缺少参数');
        }
        $user = new \app\api\controller\User();
        return $user->get();
    }

    /**
     * 获得某个用户信息
     */
    public function getbyname()
    {
        if (empty($_GET['username'])) {
            return $this->json(2, ' 非法参数');
        }
        $user = new \app\api\controller\User();
        return $user->get();
    }

    /**
     * 获得某个用户信息
     */
    public function getbyuid()
    {
        if (empty($_GET['uid'])) {
            return $this->json(2, ' 非法参数');
        }
        $user = new \app\api\controller\User();
        return $user->get();
    }

    /**
     * 更新用户信息
     */
    public function update()
    {
        $user = new \app\api\controller\User();
        return $user->update();
    }
}
