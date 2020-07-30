<?php

namespace app\admin\controller;

/**
 * 后台主页
 * @package app\api\controller
 */
class Index extends Base
{
    public function index()
    {
        header('Location: /admin/user/index');
        exit;
        $this->assign('section', 'home');
        return $this->fetch();
    }
}
