<?php

namespace app\api\controller;

use app\api\model\Push;
use think\Db;

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
        // 读取系统配置
        $setting = Db::table('setting')->find();

        if ($setting['add_friend'] != 'on') {
            return $this->json(202, '系统禁用了加好友');
        }

        $post = $this->_post();
        // 检查必要字段是否为空
        $required = ['friend_uid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }
        $login_uid  = $post['login_uid'];
        $friend_uid = $post['friend_uid'];
        
        if ($login_uid === $friend_uid) {
            return $this->json(2, '参数非法');
        }

        // 判断用户是否存在
        $uid_array = Db::table('user')->field('uid')->where('uid', 'in', [$login_uid, $friend_uid])->where('account_state', 'normal')->select();
        if (count($uid_array) != 2) {
            return $this->json(101, '用户不存在');
        }

        // 判断下是否已经是好友
        $items = Db::table('friend')->field('uid')->where([
            'uid'        => $login_uid,
            'friend_uid' => $friend_uid
        ])->select();
        if ($items) {
            return $this->json(0, '已经是好友');
        }

        $postscript = isset($post['postscript']) ? $post['postscript'] : '';

        // 避免出现多条记录，从数据库中删除原来的记录
        Db::table('notice')->where([
            'to'   => $friend_uid,
            'from' => $login_uid,
            'type' => 'add_friend',
        ])->delete();

        // 向系统消息表插入一条记录
        Db::table('notice')->insert([
            'from'      => $login_uid,
            'to'        => $friend_uid,
            'data'      => json_encode(['postscript' => $postscript]),
            'type'      => 'add_friend',
            'operation' => 'not_operated',
            'timestamp' => time()
        ]);

        // 执行推送
        $push  = new Push();
        $event = 'friendApply';
        $data  = [
            'uid' => $login_uid
        ];
        $push->emit("user-$friend_uid", $event, $data);


        return $this->json(0, 'ok');
    }

    /**
     * 同意好友申请
     *
     * @return string
     */
    public function agree()
    {
        return $this->_applyOperation('agree');
    }

    /**
     * 拒绝好友申请
     *
     * @return string
     */
    public function refuse()
    {
        return $this->_applyOperation('refuse');
    }

    /**
     * 好友申请处理
     *
     * @return string
     */
    protected function _applyOperation($operation)
    {
        $post = $this->_post();
        if (empty($post['nid'])) {
            return $this->json(1, '缺少参数');
        }
        $nid       = intval($post['nid']);
        $login_uid = $post['login_uid'];

        // 判断账户是否被禁用
        if ($this->userDisabled($login_uid)) {
            return $this->json(85, '该账户被禁用');
        }

        // 判断记录是否存在
        $notice_info = Db::table('notice')->where([
            'nid' => $nid,
            'to'  => $login_uid
        ])->find();
        if (empty($notice_info)) {
            return $this->json(3, '记录不存在');
        }
        if ($notice_info['operation'] === $operation) {
            return $this->json(0, '已经操作成功');
        }
        if ($notice_info['operation'] !== 'not_operated') {
            return $this->json(210, '非法操作');
        }
        $from = $notice_info['from'];
        $to   = $notice_info['to'];

        Db::table('notice')->where('nid', $nid)->update(['operation' => $operation]);

        if ($operation === 'agree') {
            $this->_add($from, $to);
            $message = new Message();
            $_POST['from'] = $login_uid;
            $_POST['to'] = $from;
            $_POST['content'] = '我通过了你的好友请求，我们来聊天吧';
            $_POST['type'] = 'friend';
            $ret = $message->send();
        }

        return $this->json(0, '', empty($ret) ? [] : $ret);
    }

    /**
     * 执行互相添加好友操作
     *
     * @return string
     */
    public function add()
    {
        $post = $this->_post();
        // 检查必要字段是否为空
        $required = ['friend_uid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }
        $uid1 = $post['login_uid'];
        $uid2 = $post['friend_uid'];
        return $this->_add($uid1, $uid2);
    }

    /**
     * 添加好友
     *
     * @return string
     */
    protected function _add($uid1, $uid2)
    {
        // 判断用户是否存在
        $uid_array = Db::table('user')->where('uid', 'in', [$uid1, $uid2])->select();
        if (count($uid_array) != 2) {
            return $this->json(101, '用户不存在');
        }

        $uid_map = [];
        foreach ($uid_array as $item) {
            $uid_map[$item['uid']] = [
                'uid'      => $item['uid'],
                'name'     => $item['nickname'],
                'avatar'   => $item['avatar']
            ];
        }

        $items = Db::table('friend')->query('select uid from friend where (uid=:uid and friend_uid=:friend_uid) or (uid=:uid2 and friend_uid=:friend_uid2)', [
            'uid'         => $uid1,
            'friend_uid'  => $uid2,
            'uid2'        => $uid2,
            'friend_uid2' => $uid1
        ]);
        if ($items) {
            if (count($items) == 2) {
                return $this->json(0, '已经是好友');
            } else {
                // 数据不一致
                Db::table('friend')->execute('delete from friend where (uid=:uid and friend_uid=:friend_uid) or (uid=:uid2 and friend_uid=:friend_uid2)', [
                    'uid'         => $uid1,
                    'friend_uid'  => $uid2,
                    'uid2'        => $uid2,
                    'friend_uid2' => $uid1
                ]);
            }
        }

        $timestamp = time();
        Db::table('friend')->insert([
            'uid'            => $uid1,
            'friend_uid'     => $uid2,
            'state'          => 'chatting',
            'last_read_time' => $timestamp
        ]);

        Db::table('friend')->insert([
            'uid'            => $uid2,
            'friend_uid'     => $uid1,
            'state'          => 'chatting',
            'last_read_time' => $timestamp
        ]);

        // 执行推送，通知客户端渲染新的好友
        $push  = new Push();
        $event = 'addFriend';
        $data  = $uid_map[$uid1];
        $push->emit("user-$uid2", $event, $data);
        $data  = $uid_map[$uid2];
        $push->emit("user-$uid1", $event, $data);

        return $this->json(0, 'ok');
    }

    /**
     * 设置好友备注
     */
    public function remark()
    {
        $post = $this->_post();
        // 检查必要字段是否为空
        $required = ['friend_uid', 'remark'];
        foreach ($required as $key) {
            if (!isset($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }

        if (empty($post['friend_uid'])) {
            return $this->json(1, "缺少参数");
        }

        $login_uid  = $post['login_uid'];
        $friend_uid = $post['friend_uid'];

        if ($login_uid === $friend_uid) {
            return $this->json(2, '参数非法');
        }

        // 判断账户是否被禁用
        if ($this->userDisabled($login_uid)) {
            return $this->json(85, '该账户被禁用');
        }

        // 判断用户是否存在
        $uid_array = Db::table('user')->field('uid')->where('uid', 'in', [$login_uid, $friend_uid])->select();
        if (count($uid_array) != 2) {
            return $this->json(101, '用户不存在');
        }

        $remark = ($post['remark']);
        if (strlen($remark) > 255) {
            return $this->json(2, '长度不能大于255个字节');
        }

        Db::table('friend')->where(['uid'=>$login_uid, 'friend_uid'=>$friend_uid])->update(['remark' => $remark]);
        return $this->json(0);
    }

    /**
     * 获取好友申请信息
     *
     * @return string
     */
    public function applylist()
    {
        $get = $this->_get();
        $login_uid = $get['login_uid'];
        $limit = isset($get['limit']) ? intval($get['limit']) : 500;
        $data = Db::table('notice')->query("select notice.*, user.nickname, user.avatar from notice inner join user on notice.from=user.uid where notice.to=? order by nid desc limit ?", [$login_uid, $limit]);
        return $this->json(0, 'ok', $data);
    }

    /**
     * 通讯录好友列表
     *
     * @return array
     */
    public function getlist()
    {
        $get               = $this->_get();
        $login_uid         = $get['login_uid'];
        $friend_uid_list   = $friend_list = [];
        $friend_info_list  = Db::table('friend')->field('friend_uid')->where('uid', $login_uid)->select();
        if ($friend_info_list) {
            foreach ($friend_info_list as $item) {
                $friend_uid_list[] = $item['friend_uid'];
            }
        }

        if ($friend_uid_list) {
            $friend_list = Db::table('user')->where('uid', 'in', $friend_uid_list)->column('uid, nickname, avatar, state', 'uid');
            $remarks = Db::table('friend')->where('uid', $login_uid)->where('friend_uid', 'in',$friend_uid_list)->column('remark', 'friend_uid');
            foreach ($friend_list as $tmp_uid => $item) {
                $friend_list[$tmp_uid]['name'] = !empty($remarks[$tmp_uid]) ? $remarks[$tmp_uid] : $friend_list[$tmp_uid]['nickname'];
                unset($friend_list[$tmp_uid]['nickname']);
            }
        }

        $friend_list = (new \HanziToPinyin)->groupByInitials($friend_list, 'name');

        return $this->json(0, 'ok', $friend_list);
    }

    /**
     * 获取未处理好友申请信息数字
     *
     * @return string
     */
    public function applycount()
    {
        $get = $this->_get();
        $login_uid = $get['login_uid'];
        $data = Db::table('notice')->field('to')->where([
            'to'        => $login_uid,
            'operation' => 'not_operated',
        ])->count();
        return $this->json(0, 'ok', $data);
    }

    /**
     * 获取好友申请详情
     *
     * @return string
     */
    public function applydetail()
    {
        $get = $this->_get();
        if (empty($get['nid'])) {
            return $this->json(1, "缺少参数");
        }
        $login_uid = $get['login_uid'];
        $nid       = $get['nid'];
        $data = Db::table('notice')->query("select notice.*, user.nickname, user.username, user.avatar from notice inner join user on notice.from=user.uid where nid=? and notice.to=? limit 1", [$nid, $login_uid]);
        $data = $data ? current($data) : [];
        return $this->json(0, 'ok', $data);
    }

    /**
     * 解除好友好友关系
     *
     * @return string
     */
    public function delete()
    {
        $post = $this->_post();
        $required = ['friend_uid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }
        $uid1 = $post['login_uid'];
        $uid2 = $post['friend_uid'];

        // 判断账户是否被禁用
        if ($this->userDisabled($uid1)) {
            return $this->json(85, '该账户被禁用');
        }

        Db::table('friend')->where([
            'uid'         => $uid1,
            'friend_uid'  => $uid2
        ])->delete();

        Db::table('friend')->where([
            'uid'         => $uid2,
            'friend_uid'  => $uid1
        ])->delete();

        // 执行推送，通知客户端渲染新的好友
        $push  = new Push();
        $event = 'removeFriend';
        $push->emit("user-$uid2", $event, ['uid' => $uid1]);

        return $this->json(0, 'ok');
    }


    /**
     * 更新好友信息
     */
    public function update()
    {
        $post = $this->_post();
        $required = ['friend_uid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }
        // 允许更新的字段
        $available_fields = ['state', 'last_read_time', 'unread_count'];
        $data = [];
        foreach ($available_fields as $field) {
            if (isset($post[$field])) {
                $data[$field] = $post[$field];
            }
        }
        if (empty($field)) {
            return $this->json(1, "缺少参数");
        }

        $login_uid  = $post['login_uid'];
        $friend_uid = $post['friend_uid'];

        if ($login_uid === $friend_uid) {
            return $this->json(2, '参数非法');
        }

        Db::table('friend')->where([
            'uid'            => $login_uid,
            'friend_uid'     => $friend_uid
        ])->update($data);

        return $this->json(0, 'ok');
    }

}