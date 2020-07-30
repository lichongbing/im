<?php

namespace app\h5\controller;

use think\captcha\Captcha;

class Login extends Base
{
    /**
     * 用户注册
     *
     * @return string
     */
    public function join()
    {
        if (!is_file(APP_PATH . '../config/database.php')) {
            return $this->json(-2, '服务端未安装');
        }

        if (empty($_POST['captcha'])) {
            return $this->json(1, '缺少参数');
        }
        // 默认头像
        $_POST['avatar'] = '/static/avatar.jpg';
        // 检查验证码
        $captcha = new Captcha();
        if (!$captcha->check($_POST['captcha'], 'join')) {
            return $this->json(1, '验证码不正确');
        }
        $user = new \app\api\controller\User();
        return $user->add();
    }

    /**
     * 登录验证
     */
    public function check()
    {
        if (!is_file(APP_PATH . '../config/database.php')) {
            return $this->json(-2, '服务端未安装');
        }

        if (empty($_POST['username']) || empty($_POST['password']) || empty($_POST['captcha'])) {
            return $this->json(1, '缺少参数');
        }
        $_GET['username'] = $_POST['username'];
        $_GET['password'] = $_POST['password'];

        // 检查验证码
        $captcha = new Captcha();
        if (!$captcha->check($_POST['captcha'], 'login')) {
            return $this->json(1, '验证码不正确');
        }

        // 读取用户信息
        $user = new \app\api\controller\User();
        $user_info = $user->get();
        if ($user_info['code'] == 0) {
            $_SESSION['userinfo'] = $user_info['data'];
            return $this->json(0, 'ok');
        } else {
            return $this->json($user_info['code'], $user_info['msg']);
        }
    }

    /**
     * 注册验证码
     */
    public function joincaptcha()
    {
        $captcha = new Captcha(['length' => 4, 'fontSize' => 50]);
        return $captcha->entry('join');
    }

    /**
     * 登录验证码
     */
    public function logincaptcha()
    {
        $captcha = new Captcha(['length' => 4, 'fontSize' => 50]);
        return $captcha->entry('login');
    }

    /**
     * 退出
     */
    public function logout()
    {
        if (!isset($_POST['logout_uid'])) {
            return $this->json(1, '缺少参数');
        }
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION['userinfo'])) {
            return $this->json(0, 'ok');
        }
        if ($_POST['logout_uid'] != $_SESSION['userinfo']['uid']) {
            return $this->json(2, '非法参数');
        }
        $_SESSION = null;
        session_destroy();
        return $this->json(0, 'ok');
    }

}
