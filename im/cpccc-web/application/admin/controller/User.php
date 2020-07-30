<?php

namespace app\admin\controller;

use think\Db;

/**
 * 用户列表页
 */
class User extends Base
{
    /**
     * 用户列表页
     * @return mixed
     */
    public function index()
    {
        $get = $this->request->get();
        $step = 50;
        $page = isset($get['page']) ? intval($get['page']) : 1;
        $users = Db::table('user')->order('uid desc')->limit(($page-1)*$step, $step)->select();
        $count = Db::table('user')->count('uid');
        $this->assign('users', $users);
        $this->assign('count', $count);
        $this->assign('page', $page);
        $this->assign('step', $step);
        $this->assign('section', 'user');
        return $this->fetch();
    }

    /**
     * 更新用户信息
     */
    public function update()
    {
        $post = $this->request->post();
        if (empty($post['uid'])) {
            return $this->json(1, '缺少参数');
        }

        $uid = $post['uid'];
        $old_user_info = Db::table('user')->where('uid', $uid)->find();
        if (!$old_user_info) {
            return $this->json(3, '用户不存在');
        }
        $map = ['nickname', 'avatar', 'sign', 'sign', 'account_state'];
        $data = [];
        foreach ($map as $key) {
            if (isset($post[$key])) {
                $data[$key] = ($post[$key]);
            }
        }
        if (isset($post['password']) && $post['password']) {
            $data['password'] = md5($old_user_info['username'] . '-cpcccim-' . $post['password']);
        }
        if (empty($data)) {
            return $this->json(1, '缺少参数');
        }
        Db::table('user')->where('uid', $uid)->update($data);
        return $this->json(0, 'ok');
    }

    /**
     * 群聊天记录页面
     */
    public function chatlog()
    {
        $get = $this->request->get();
        if (!isset($get['uid'])) {
            return $this->json(1, '缺少参数');
        }
        $this->assign('uid', $get['uid']);
        $this->assign('limit', 10);
        return $this->fetch();
    }

    /**
     * 获得该用户的聊天日志
     */
    public function chatget()
    {
        $get = $this->request->get();
        if (empty($get['uid'])) {
            return $this->json(1, '缺少参数');
        }
        $uid = $get['uid'];
        $mid = isset($get['mid']) ? intval($get['mid']) : 2147483648;
        $limit = isset($get['limit']) ? intval($get['limit']) : 10;

        $message_list = (array)Db::table('message')->query("select * from message where sub_type!='notice' and `from`=? and mid<$mid order by mid desc limit $limit", [$uid]);

        $gid_list = $group_list = [];
        $uid_list[$uid] = $uid;

        foreach ($message_list as $item) {
            if ($item['type'] == 'friend') {
                $tmp_uid = $item['from'] == $uid ? $item['to'] : $item['from'];
                $uid_list[$tmp_uid] = $tmp_uid;
            } else {
                $gid_list[$item['to']] = $item['to'];
            }
        }

        $user_list = Db::table('user')->where('uid', 'in', $uid_list)->column('uid,nickname as name,avatar', 'uid');
        $group_list = Db::table('groups')->where('gid', 'in', $gid_list)->column('gid,groupname as name,avatar', 'gid');

        foreach ($message_list as $key=>$item) {
            if ($item['type'] == 'friend') {
                $to_uid = $item['to'];
                $from_uid = $item['from'];
                $message_list[$key]['name'] = isset($user_list[$from_uid]) ? $user_list[$from_uid]['name'] : '';
                $message_list[$key]['avatar'] = isset($user_list[$from_uid]) ? $user_list[$from_uid]['avatar'] : '';
                $message_list[$key]['to_name'] = isset($user_list[$to_uid]) ? $user_list[$to_uid]['name'] : '';
                $message_list[$key]['to_avatar'] = isset($user_list[$to_uid]) ? $user_list[$to_uid]['avatar'] : '';
            } else {
                $gid = $item['to'];
                $from_uid = $item['from'];
                $message_list[$key]['name'] = isset($user_list[$from_uid]) ? $user_list[$from_uid]['name'] : '';
                $message_list[$key]['avatar'] = isset($user_list[$from_uid]) ? $user_list[$from_uid]['avatar'] : '';
                $message_list[$key]['to_name'] = isset($group_list[$gid]) ? $group_list[$gid]['name'] : '';
                $message_list[$key]['to_avatar'] = isset($group_list[$gid]) ? $group_list[$gid]['avatar'] : '';
            }
        }

        $message_list = array_reverse($message_list);

        return $this->json(0, 'ok', $message_list);
    }

    /**
     * 编辑用户信息
     * @return mixed|void
     */
    public function edit()
    {
        $get = $this->request->get();
        if (empty($get['uid'])) {
            return $this->json(1, '缺少参数');
        }
        $uid = $get['uid'];
        $user_info = Db::table('user')->where('uid', $uid)->find();
        if (!$user_info) {
            return $this->json(2, '用户不存在');
        }
        $this->assign('user_info', $user_info);
        return $this->fetch();
    }

    /**
     * 上传头像
     *
     * @return string
     */
    public function avatar()
    {
        $post = $this->request->post();
        if (empty($post['uid'])) {
            return $this->json(1, '缺少参数');
        }
        $_POST['login_uid'] = $post['uid'];
        $upload = new \app\api\controller\Upload();
        return $upload->avatar();
    }
}
