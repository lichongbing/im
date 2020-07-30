<?php

namespace app\api\controller;

use think\Db;


class User extends Base
{
    /**
     * 添加用户
     *
     * @return string
     */
    public function add()
    {
        $post = $this->_post();
        // 检查必要字段是否为空
        $required = ['username', 'nickname', 'avatar', 'password'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }

        // 判断用户是否存在
        $username = $post['username'];
        if (Db::table('user')->where('username', $username)->column('uid')) {
            return $this->json(111, "用户名已经存在");
        }

        // 判断用户名是否合法
        if (!preg_match('/^[A-Za-z0-9]{1,60}$/', $username)) {
            return $this->json(112, "用户名只能包含字母和数字，长度小于60");
        }

        // 个性签名可以为空
        $sign = isset($post['sign']) ? ($post['sign']) : '';
        // 插入到数据库，其中密码是经过处理过的md5值
        Db::table('user')->insert([
            'username'         => $username,
            'nickname'         => $post['nickname'],
            'sign'             => $sign,
            'avatar'           => $post['avatar'],
            'password'         => $this->md5($username, $post['password']),
            'logout_timestamp' => 0,
            'state'            => isset($post['state']) && $post['state'] == 'online' ? 'online' : 'offline',
            'timestamp'        => time()
        ]);
        $uid = Db::table('user')->getLastInsID();
        return $this->json(0, 'ok', ['uid' => $uid]);
    }

    /**
     * 获得某个用户信息
     */
    public function get()
    {
        $get = $this->_get();
        $where = array();
        if (isset($get['uid'])) {
            $where['uid'] = $get['uid'];
        }
        if (isset($get['username'])) {
            $where['username'] = $get['username'];
        }
        if (isset($get['password']) && !isset($get['username'])) {
            return $this->json(1, '缺少参数');
        }
        if (isset($get['password'])) {
            $where['password'] = $this->md5($where['username'], $get['password']);
        }

        if (empty($where)) {
            return $this->json(1, '缺少参数');
        }

        $user_info = Db::table('user')->field('uid,username,nickname,sign,avatar,state,logout_timestamp,account_state')->where($where)->find();
        if (!$user_info) {
            return $this->json(101, isset($where['password']) ? '用户不存在或者密码错误' : '用户不存在');
        }
        // 有login_uid会加上是否是好友的判断
        if (isset($get['login_uid']) && isset($get['uid'])) {
            $login_uid = $get['login_uid'];
            $uid       = $get['uid'];



            // 判断账户是否被禁用
            if ($this->userDisabled($login_uid)) {
                return $this->json(85, '该账户被禁用');
            }

            // 查询是否是好友
            $friend_info = Db::table('friend')->where([
                'uid'        => $login_uid,
                'friend_uid' => $uid,
            ])->find();
            $user_info['is_friend'] = $friend_info ? true : false;
            $user_info['remark'] = $friend_info ? $friend_info['remark'] : '';
        }
        return $this->json(0, 'ok', $user_info);
    }

    /**
     * 更新用户信息
     */
    public function update() {
        $post = $this->_post();
        $login_uid = $post['login_uid'];

        $map = ['nickname', 'avatar', 'sign', 'account_state'];
        $data = [];
        foreach ($map as $key) {
            if (isset($post[$key])) {
                $data[$key] = ($post[$key]);
            }
        }
        if (empty($data)) {
            return $this->json(1, '缺少参数');
        }
        Db::table('user')->where('uid', $login_uid)->update($data);
        return $this->json(0, 'ok');
    }

    /**
     * 获得某些用户信息
     */
    public function multiGet()
    {
        $get = $this->_get();
        $uid_array = array();
        if (empty($get['uid'])) {
            return $this->json(1, '缺少参数');
        }
        if (!is_array($get['uid'])) {
            return $this->json(2, '参数非法');
        }
        foreach ($get['uid'] as $uid) {
            $uid_array[$uid] = $uid;
        }
        
        $user_list = Db::table('user')->field('uid,username,nickname,sign,avatar,state,logout_timestamp')->where('uid', 'in', $uid_array)->select();
        return $this->json(0, 'ok', $user_list);
    }


}
