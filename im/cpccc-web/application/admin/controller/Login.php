<?php

namespace app\admin\controller;

use think\captcha\Captcha;
use think\Db;

class Login extends Base
{

    /**
     * 登录页
     */
    public function index()
    {
        return $this->fetch();
    }

    /**
     * @return \think\Response
     */
    public function captcha()
    {
        $captcha = new Captcha();
        return $captcha->entry('admin_login');
    }

    /**
     * 登录验证
     */
    public function check()
    {
        $post = $this->request->post();
        if (empty($post['username']) || empty($post['password'])) {
            return $this->json(2, '用户名和密码是必填项');
        }
		/*
        $captcha = new Captcha();
        if(!$captcha->check($post['captcha'], 'admin_login')) {
            return $this->json(3, '验证码不正确');
        }
		*/
		
        $username = $post['username'];
        $password = md5($username."-cpcccim-".$post['password']);
		
	
        $admin_info = Db::table('admin')->where(['username'=> $username, 'password' => $password])->find();
        if (!$admin_info) {
            return $this->json(1, '用户不存在或者密码错误='.$password);
        }
        $_SESSION['admin'] = ['username' => $username];
		
		cookie('admin',$username,7200*24);
		//var_dump($_SESSION);die;
		
        return $this->json(0);
    }

    /**
     * 退出
     */
    public function logout()
    {
        session_start();
        $_SESSION = null;
        session_destroy();
        header('Location: /admin/login/index');
    }
}
