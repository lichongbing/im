<?php

namespace app\admin\controller;

use think\captcha\Captcha;
use think\Db;

class Account extends Base
{
    /**
     *  修改密码
     */
    public function edit()
    {
        // 检查是否登录
        $post = $this->request->post();
        if (isset($post['password']) && isset($post['old_password'])) {
            $username = $_SESSION['admin']['username'];
            $old_password = md5($username."-cpcccim-".$post['old_password']);
            $admin_info = Db::table('admin')->where(['username'=> $username, 'password' => $old_password])->find();
            if (!$admin_info) {
                return $this->json(1, '用户不存在或者密码错误');
            }

            $password = $post['password'];
            if (strlen($password) < 6) {
                return $this->json(6, '密码至少6个字符');
            }
            $new_password = md5($username."-cpcccim-".$password);

            Db::table('admin')->where(['username'=> $username, 'password'=> $old_password])->update(['password' => $new_password]);

            return $this->json(0);
        }
        return $this->fetch();
    }

}
