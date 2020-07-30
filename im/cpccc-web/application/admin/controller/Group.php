<?php

namespace app\admin\controller;

use think\Db;
use app\api\model\Push;

/**
 * 群组列表页
 */
class Group extends Base
{
    /**
     * 群组列表页
     * @return mixed
     */
    public function index()
    {
        $get = $this->request->get();
        $step = 50;
        $page = isset($get['page']) ? intval($get['page']) : 1;
        $groups = (array)Db::table('groups')->order('gid desc')->limit(($page-1)*$step, $step)->select();
        $uid_list = [];
        foreach ($groups as $group) {
            $uid_list[$group['uid']] = $group['uid'];
        }
        if ($uid_list) {
            $user_list = Db::table('user')->where('uid', 'in', $uid_list)->column('uid,nickname,username', 'uid');
        }
        foreach ($groups as $key=>$group) {
            $tmp_uid = $group['uid'];
            $groups[$key]['nickname'] = isset($user_list[$tmp_uid]) ? $user_list[$tmp_uid]['nickname'] : '';
            $groups[$key]['username'] = isset($user_list[$tmp_uid]) ? $user_list[$tmp_uid]['username'] : '';
        }
        $count = Db::table('groups')->count('gid');
        $this->assign('groups', $groups);
        $this->assign('count', $count);
        $this->assign('page', $page);
        $this->assign('step', $step);
        $this->assign('section', 'group');
        return $this->fetch();
    }

    /**
     * 成员列表页
     * @return mixed
     */
    public function members()
    {
        $get = $this->request->get();
        $step = 10;
        if (!isset($get['gid'])) {
            return $this->json(1, '缺少参数');
        }
        $gid = $get['gid'];
        $page = isset($get['page']) ? intval($get['page']) : 1;
        $uid_list = (array)Db::table('group_member')->where('gid', $gid)->order('id')->limit(($page-1)*$step, $step)->column('uid');
        $members = [];
        if ($uid_list) {
            $members = Db::table('user')->where('uid', 'in', $uid_list)->column('uid,nickname,username,avatar', 'uid');
        }
        $count = Db::table('group_member')->where('gid', $gid)->count('gid');
        $this->assign('members', $members);
        $this->assign('count', $count);
        $this->assign('page', $page);
        $this->assign('step', $step);
        $this->assign('gid', $gid);
        return $this->fetch();
    }

    /**
     * 成员列表页
     * @return mixed
     */
    public function memberdelete()
    {
        $post = $this->request->post();
        if (empty($post['gid']) || empty($post['uid'])) {
            return $this->json(1, '缺少参数');
        }

        $gid       = $post['gid'];

        $group_info = Db::table('groups')->where([
            'gid' => $gid,
        ])->find();
        if (!$group_info) {
            return $this->json(2, '群不存在');
        }

        $members = [$post['uid']];

        Db::table('group_member')->where('gid', $gid)->where('uid', 'in', $members)->delete();

        $push  = new Push();

        // 更新群头像
        $slice_members = Db::table('group_member')->where('gid' , $gid)->limit(9)->order('id asc')->column('uid');
        $avatar = '/avatar.php?uid='.implode(',', $slice_members);
        if ($avatar != $group_info['avatar']) {
            Db::table('groups')->where('gid', $gid)->update(['avatar' => $avatar]);
            // 给所有健在的成员发送推送一条更新群头像的消息
            $push->emit("group-$gid", 'updateGroup', ['gid' => $gid, 'avatar' => $avatar]);
        }

        $event = 'removeGroup';
        $data  = [
            'gid'       => $gid,
            'groupname' => $group_info['groupname'],
            'avatar'    => $avatar,
            'uid'       => $group_info['uid']
        ];
        // 给所有删除的成员推送一个删除群组监听的消息
        foreach ($members as $tmp_uid) {
            $push->emit("user-$tmp_uid", $event, $data);
        }

        return $this->json(0);
    }

    /**
     * 更新群组信息
     */
    public function update()
    {
        $post = $this->request->post();
        if (empty($post['gid'])) {
            return $this->json(1, '缺少参数');
        }

        $gid = $post['gid'];
        $old_group_info = Db::table('groups')->where('gid', $gid)->find();
        if (!$old_group_info) {
            return $this->json(3, '用户不存在');
        }
        $map = ['groupname', 'state'];
        $data = [];
        foreach ($map as $key) {
            if (isset($post[$key])) {
                $data[$key] = ($post[$key]);
            }
        }
        if (empty($data)) {
            return $this->json(1, '缺少参数');
        }
        Db::table('groups')->where('gid', $gid)->update($data);
        return $this->json(0, 'ok');
    }

    /**
     * 彻底删除群组
     */
    public function delete()
    {
        $post = $this->request->post();
        if (empty($post['gid'])) {
            return $this->json(1, '缺少参数');
        }
        $gid = $post['gid'];
        Db::table('groups')->where('gid', $gid)->delete();
        Db::table('group_member')->where('gid', $gid)->delete();

        // 给所有群成员推送一条删除群组的消息
        $push  = new Push();
        $push->emit("group-$gid", 'removeGroup', ['gid' => $gid]);

        return $this->json(0, 'ok');
    }

    /**
     * 群聊天记录页面
     */
    public function chatlog()
    {
        $get = $this->request->get();
        if (!isset($get['gid'])) {
            return $this->json(1, '缺少参数');
        }
        $this->assign('gid', $get['gid']);
        $this->assign('limit', 10);
        return $this->fetch();
    }

    /**
     * 获得群的聊天记录
     */
    public function chatget()
    {
        $_GET['type'] = 'group';
        $_GET['login_uid'] = 1;
        $message = new \app\api\controller\Message();
        $message_list = $message->get();
        return $this->jsonArray($message_list);
    }

    /**
     * 编辑群组
     */
    public function edit()
    {
        $get = $this->request->get();
        if (empty($get['gid'])) {
            return $this->json(1, '缺少参数');
        }
        $gid = $get['gid'];
        $group_info = Db::table('groups')->where('gid', $gid)->find();
        if (!$group_info) {
            return $this->json(2, '用户不存在');
        }
        $this->assign('group_info', $group_info);
        return $this->fetch();
    }
}
