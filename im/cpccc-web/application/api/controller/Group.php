<?php

namespace app\api\controller;

use app\api\model\Push;
use think\Db;


class Group extends Base
{
    /**
     * 创建群
     *
     * @return string
     */
    public function create()
    {
        // 读取系统配置
        $setting = Db::table('setting')->find();

        if ($setting['create_group'] != 'on') {
            return $this->json(702, '系统禁用了创建群组');
        }

        $post      = $this->_post();
        $members   = isset($post['members']) ? array_merge([$post['login_uid']], $post['members']) : [$post['login_uid']];
        $avatar    = isset($post['avatar']) ? $post['avatar'] : '/avatar.php?uid='.implode(',', array_slice($members, 0, 9));
        $login_uid = $post['login_uid'];

        // 判断账户是否被禁用
        if ($this->userDisabled($login_uid)) {
            return $this->json(85, '该账户被禁用');
        }

        // 判断用户是否存在
        if (!Db::table('user')->field('uid')->where('uid', $login_uid)->find()) {
            return $this->json(101, '用户不存在');
        }

        // 获得这些人的信息
        //$members_info_array = Db::table('user')->where('uid', 'in', $members)->column('uid,nickname', 'uid');//zedit(原始)
		//$ad_json = addslashes(json_encode($members_info_array));Db::table('ls')->where(['id'=>1])->update(['text' => $ad_json]);
		
		/*zedit*****************************(组群时成员名按顺序显示)*/
	    $members_info_array = [];
		for ($mi=0; $mi<count($members); $mi++)
		{
			$muid=$members[$mi];
			$members_info_array = $members_info_array+Db::table('user')->where('uid', 'in',$muid)->column('uid,nickname', 'uid');
		}
		//$ad_json = addslashes(json_encode($members_info_array));;Db::table('ls')->where(['id'=>1])->update(['text' => $ad_json]);
		/*zedit*****************************(组群时成员名按顺序显示)*/
		
        if (count($members) != count($members_info_array)) {
            return $this->json(2, '参数非法，用户不存在');
        }

        // 没有设置群组名则从成员名字中获取
        if (!isset($post['groupname'])) {
            $names = array_slice($members_info_array, 0, 3);
            $groupname = isset($post['groupname']) ? $post['groupname'] : '群聊('. implode(',', $names) .')';
        } else {
            $groupname = $post['groupname'];
        }

        Db::table('groups')->insert([
            'groupname' => $groupname,
            'avatar'    => $avatar,
            'uid'       => $login_uid,
            'state'     => 'normal',
            'timestamp' => time()
        ]);
        $gid = Db::table('groups')->getLastInsID();

        $members = [];
        $members[$login_uid] = ['gid' => $gid, 'uid' => $login_uid, 'state' => 'chatting'];
        if (isset($post['members']) && is_array($post['members'])) {
            foreach ($post['members'] as $tmp_uid) {
                $tmp_uid = ($tmp_uid);
                $members[$tmp_uid] = ['gid' => $gid, 'uid' => $tmp_uid, 'state' => 'chatting'];
            }
        }

        $timestamp = time();

        // 插入群成员
        Db::table('group_member')->insertAll($members);

        // 执行推送
        $push  = new Push();
        $event = 'addGroup';
        $data  = [
            'gid'       => $gid,
            'groupname' => $groupname,
            'avatar'    => $avatar,
            'uid'       => $login_uid
        ];
        // 给所有的成员推送一个添加群组监听的消息
        foreach ($members as $tmp_uid => $item) {
            $push->emit("user-$tmp_uid", $event, $data);
        }

        if (count($members) > 1) {
            $content = "[{$members_info_array[$login_uid]}]({POPBASEURI}user/detail/$login_uid) 邀请 ";
            foreach ($members as $tmp_uid => $item) {
                if ($tmp_uid != $login_uid) {
                    $content .= "[{$members_info_array[$tmp_uid]}]({POPBASEURI}user/detail/$tmp_uid) ";
                }
            }
            $content .= "加入了群聊";
            Db::table('message')->insert([
                'from' => $login_uid,
                'to' => $gid,
                'content' => $content,
                'type' => 'group',
                'sub_type' => 'notice',
                'timestamp' => $timestamp
            ]);
            $mid = Db::table('message')->getLastInsID();

            $event = 'message';
            $data = [
                'from' => $login_uid,
                'from_name' => $members_info_array[$login_uid],
                'from_avatar' => '',
                'to' => $gid,
                'to_name' => $groupname,
                'to_avatar' => $avatar,
                'content' => $content,
                'timestamp' => $timestamp,
                'type' => 'group',
                'sub_type' => 'notice',
                'mid' => $mid,
            ];
            // 给所有的成员推送一个群组邀请消息
            foreach ($members as $tmp_uid => $item) {
                $push->emit("user-$tmp_uid", $event, $data);
            }
        }

        return $this->json(0, 'ok', ['gid' => $gid, 'groupname' => $groupname, 'avatar' => $avatar]);
    }

    /**
     * 解散群组
     */
    public function delete()
    {
        $post = $this->_post();
        $required = ['gid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
        $login_uid = $post['login_uid'];
        $gid       = $post['gid'];
        // 判断是否是群主
        $group_info = Db::table('groups')->where([
            'gid' => $gid
        ])->find();
        if (!$group_info) {
            return $this->json(0, '记录不存在');
        }
        if ($group_info['uid'] != $login_uid) {
            return $this->json(2, '非法请求');
        }
		
			Db::table('group_member')->where('gid', $gid)->delete();
			Db::table('groups')->where('gid', $gid)->delete();
			// 通知群组的人删除群组
			$push = new Push();
			$push->emit('group-'.$gid, 'removeGroup',['gid'=>$gid]);
		
        return $this->json(0);
    }

    /**
     * 添加群成员
     *
     * @return array
     */
    public function memberadd()
    {
        $post = $this->_post();
        $required = ['gid', 'members'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, '缺少参数');
            }
        }

        if (!is_array($post['members'])) {
            return $this->json(2, '参数非法');
        }

        $gid        = $post['gid'];
        $login_uid  = $post['login_uid'];

        // 判断账户是否被禁用
        if ($this->userDisabled($login_uid)) {
            return $this->json(85, '该账户被禁用');
        }

        $group_info = Db::table('groups')->where([
            'gid' => $gid
        ])->find();
        if (!$group_info || $group_info['state'] === 'disabled') {
            return $this->json(3, '群不存在或者已经解散');
        }

        // 查询是否是群成员
        if (!Db::table('group_member')->where(['gid' => $gid, 'uid' => $login_uid])->column('uid')) {
            return $this->json(2, '参数非法，非本群人员不得拉人进群');
        }

        $members = $post['members'];
        // 如果已经是群成员则忽略
        $old_members = Db::table('group_member')->where('gid' , $gid)->where('uid', 'in', $members)->column('id', 'uid');
        $members_array = [];
        $timestamp = time();
        foreach ($members as $tmp_uid) {
            $tmp_uid = ($tmp_uid);
            if (isset($old_members[$tmp_uid])) {
                continue;
            }
            $members_array[$tmp_uid] = ['gid' => $gid, 'uid' => $tmp_uid, 'state' => 'hidden', 'last_read_time' => $timestamp];
        }

        if (!$members_array) {
            return $this->json(0);
        }

        // 获得这些人的信息
        $members_info_array = Db::table('user')->where('uid', 'in', array_merge(array_keys($members_array), [$login_uid]))->column('uid,nickname', 'uid');
        if (count($members_array) + 1 != count($members_info_array)) {
            return $this->json(2, '参数非法，用户不存在');
        }

        $content = "[{$members_info_array[$login_uid]}]({POPBASEURI}user/detail/$login_uid) 邀请 ";
        foreach (array_keys($members_array) as $tmp_uid) {
            $content .= "[{$members_info_array[$tmp_uid]}]({POPBASEURI}user/detail/$tmp_uid) ";
        }
        $content .= "加入了群聊";

        Db::table('message')->insert([
            'from'      => $login_uid,
            'to'        => $gid,
            'content'   => $content,
            'type'      => 'group',
            'sub_type'  => 'notice',
            'timestamp' => $timestamp
        ]);
        $mid = Db::table('message')->getLastInsID();

        // 插入成员
        Db::table('group_member')->insertAll($members_array);

        // 执行推送
        $push  = new Push();

        if (!isset($_POST['not_change_avatar'])) {
            // 更新群头像
            $slice_members = Db::table('group_member')->where('gid', $gid)->limit(9)->order('id asc')->column('uid');
            $avatar = '/avatar.php?uid=' . implode(',', $slice_members);
            if ($avatar != $group_info['avatar']) {
                // 给所有的成员发送推送一条更新群头像的消息
                $push->emit("group-$gid", 'updateGroup', ['gid' => $gid, 'avatar' => $avatar]);
                Db::table('groups')->where('gid', $gid)->update(['avatar' => $avatar]);
            }
        } else {
            $avatar = $group_info['avatar'];
        }

        $event = 'addGroup';
        $data  = [
            'gid'       => $gid,
            'groupname' => $group_info['groupname'],
            'avatar'    => $avatar,
            'uid'       => $login_uid,
            'content'   => $content
        ];
        // 给所有新成员推送一个添加群组监听的消息
        foreach ($members_array as $tmp_uid => $item) {
            $push->emit("user-$tmp_uid", $event, $data);
        }

        $event = 'message';
        $data  = [
            'from'        => $login_uid,
            'from_name'   => $members_info_array[$login_uid],
            'from_avatar' => '',
            'to'          => $gid,
            'to_name'     => $group_info['groupname'],
            'to_avatar'   => $avatar,
            'content'     => $content,
            'timestamp'   => $timestamp,
            'type'        => 'group',
            'sub_type'    => 'notice',
            'mid'         => $mid,
        ];
        $push->emit("group-$gid", $event, $data);

        return $this->json(0);
    }

    /**
     * 删除群成员
     *
     * @return array
     */
    public function memberdel()
    {
        $post = $this->_post();
        if (empty($post['gid']) || empty($post['members'])) {
            return $this->json(1, '缺少参数');
        }

        if (!is_array($post['members'])) {
            return $this->json(2, '参数非法');
        }

        $gid       = $post['gid'];
        $login_uid = $post['login_uid'];

        // 判断账户是否被禁用
        if ($this->userDisabled($login_uid)) {
            return $this->json(85, '该账户被禁用');
        }

        $group_info = Db::table('groups')->where([
            'gid' => $gid,
            'uid' => $login_uid,
        ])->find();
        if (!$group_info || $group_info['state'] === 'disabled') {
            return $this->json(2, '参数非法，群不存在或者非群创建人');
        }

        $members = $post['members'];
        if (in_array($login_uid, $members)) {
            return $this->json(2, '参数非法，不能删除创建人自己');
        }

        // 判断用户是否在群组里
        $is_in_group = Db::table('group_member')->where('gid', $gid)->where('uid', 'in', $members)->find();
        if (!$is_in_group) {
            return $this->json(2, '该用户已经被移出群');
        }

        // 获得这些人的信息
        $members_info_array = Db::table('user')->where('uid', 'in', array_merge($members, [$login_uid]))->column('uid,nickname', 'uid');

        $content = "[{$members_info_array[$login_uid]}]({POPBASEURI}user/detail/$login_uid) 将 ";
        foreach ($members as $tmp_uid) {
            if (isset($members_info_array[$tmp_uid])) {
                $content .= "[{$members_info_array[$tmp_uid]}]({POPBASEURI}user/detail/$tmp_uid) ";
            }
        }
        $content .= "移出了群聊";

        $timestamp = time();
        Db::table('message')->insert([
            'from'      => $login_uid,
            'to'        => $gid,
            'content'   => $content,
            'type'      => 'group',
            'sub_type'  => 'notice',
            'timestamp' => $timestamp
        ]);
        $mid = Db::table('message')->getLastInsID();

        Db::table('group_member')->where('gid', $gid)->where('uid', 'in', $members)->delete();

        // 通知删除消息
        $push  = new Push();

        if (!isset($_POST['not_change_avatar'])) {
            // 更新群头像
            $slice_members = Db::table('group_member')->where('gid', $gid)->limit(9)->order('id asc')->column('uid');
            $avatar = '/avatar.php?uid=' . implode(',', $slice_members);
            if ($avatar != $group_info['avatar']) {
                Db::table('groups')->where('gid', $gid)->update(['avatar' => $avatar]);
                // 给所有健在的成员发送推送一条更新群头像的消息
                $push->emit("group-$gid", 'updateGroup', ['gid' => $gid, 'avatar' => $avatar]);
            }
        } else {
            $avatar = $group_info['avatar'];
        }

        $event = 'removeGroup';
        $data  = [
            'gid'       => $gid,
            'groupname' => $group_info['groupname'],
            'avatar'    => $avatar,
            'uid'       => $login_uid
        ];
        // 给所有删除的成员推送一个删除群组监听的消息
        foreach ($members as $tmp_uid) {
            $push->emit("user-$tmp_uid", $event, $data);
        }

        $event = 'message';
        $data  = [
            'from'        => $login_uid,
            'from_name'   => $members_info_array[$login_uid],
            'from_avatar' => '',
            'to'          => $gid,
            'to_name'     => $group_info['groupname'],
            'to_avatar'   => $avatar,
            'content'     => $content,
            'timestamp'   => $timestamp,
            'type'        => 'group',
            'sub_type'    => 'notice',
            'mid'         => $mid,
        ];
        $push->emit("group-$gid", $event, $data);

        return $this->json(0);
    }

    /**
     * 添加群组
     *
     * @return string
     */
    protected function _join($login_uid, $gid)
    {
        Db::table('group_member')->insert([
            'gid' => $gid,
            'uid' => $login_uid,
        ]);
        return $this->json(0);
    }

    /**
     * 群详情
     *
     * @return string
     */
    public function detail()
    {
        $get = $this->_get();
        // 检查必要字段是否为空
        $required = ['gid'];
        foreach ($required as $key) {
            if (empty($get[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
        $login_uid = $get['login_uid'];
        $gid       = $get['gid'];
        $simple    = isset($get['simple']) ? 1 : 0;

        // 判断群是否存在
        $group_info = Db::table('groups')->where('gid', $gid)->find();
        if (!$group_info) {
            return $this->json(701, '群不存在', $group_info ? $group_info['state'] : $group_info);
        }

        $is_owner = $group_info['uid'] == $login_uid;

        // 获取群的备注名
        $remark = Db::table('group_member')->where(['gid' => $gid, 'uid' => $login_uid])->value('remark');
        $group_info['remark'] = $remark ? $remark : '';

        if ($simple) {
            return $this->json(0, 'ok', $group_info);
        }

        // 如果是拥有者，获取14个成员，否则获取15个成员
        $limit = $is_owner ? 23 : 24;/*zedit（原14,15）*/
        $members = [];
        $member_uid_array = Db::table('group_member')->where('gid', $gid)->order('id')->limit($limit)->column('uid');
        if ($member_uid_array) {
			
		
		 
         //$members = Db::table('user')->where('uid', 'in' ,$member_uid_array)->column('uid,avatar,nickname','uid');/*zedit(原始)*/
		 
		 
		/*zedit（群资料中顺序显示成员列表）*/
		//$ad_json = addslashes(json_encode($members));Db::table('ls')->where(['id'=>1])->update(['text' => $ad_json]);
		
		for ($mi=0; $mi<count($member_uid_array); $mi++)
		{
			$muid=$member_uid_array[$mi];
			$members =$members+Db::table('user')->where('uid', 'in' ,$muid)->limit(1)->column($mi.',uid,avatar,nickname',$mi);
			//$members[$mi] =Db::table('user')->where('uid', 'in' ,$muid)->limit(1)->column($mi.',uid,avatar,nickname',$mi);
			
		}
		/*zedit（按群资料中顺序显示成员列表）*/
			
					
            if ($members) {
                // 获取备注
                $remarks = Db::table('friend')->where('uid', $login_uid)->where('friend_uid', 'in',$member_uid_array)->column('remark', 'friend_uid');
                foreach ($members as $tmp_uid => $item) {
                    $members[$tmp_uid]['remark'] = isset($remarks[$tmp_uid]) ? $remarks[$tmp_uid] : '';
                }
            }
        }	
        return $this->json(0, 'ok', ['info' => $group_info, 'members' => $members]);
    }

    /**
     * 获取群成员
     *
     * @return string
     */
    public function members()
    {
        $get = $this->_get();
        // 检查必要字段是否为空
        $required = ['gid'];
        foreach ($required as $key) {
            if (empty($get[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
        $login_uid = $get['login_uid'];
        $gid       = $get['gid'];
        $pinyin = !empty($get['pinyin']);
        $exclude = !empty($get['exclude']) ? $get['exclude'] : '';
        $exclude = is_string($exclude) ? explode(',', $exclude) : [0];
        $index   = !empty($get['index']) ? $get['index'] : 0;
        $limit   = !empty($get['limit']) ? (int)$get['limit'] : 32;

        $members = [];
		
		
        //$member_uid_array = Db::table('group_member')->where('gid', $gid)->where('id', '>', $index)->where('uid', 'not in', $exclude)->order('id')->limit($limit)->column('id,uid', 'id');/*zedit(原始)*/
        $member_uid_array = Db::table('group_member')->where('gid', $gid)->where('id', '>', $index)->where('uid', 'not in', $exclude)->order('id asc')->limit($limit)->column('uid');
        if ($member_uid_array) {
            //$members = Db::table('user')->where('uid', 'in' ,$member_uid_array)->column('uid,avatar,nickname', 'uid');/*zedit(原始)*/
			/*zedit（群成员中按顺序显示成员列表）*/
			for ($mi=0; $mi<count($member_uid_array); $mi++)
			{
				$muid=$member_uid_array[$mi];
				$members =$members+Db::table('user')->where('uid', 'in' ,$muid)->limit(1)->column($mi.',uid,avatar,nickname', $mi);
			}
			//$ad_json = addslashes(json_encode($members));Db::table('ls')->where(['id'=>1])->update(['text' => $ad_json]);
			/*zedit（群成员中按顺序显示成员列表）*/
			
			
			
            if ($members) {
                // 获取备注
                $remarks = Db::table('friend')->where('uid', $login_uid)->where('friend_uid', 'in',$member_uid_array)->column('remark', 'friend_uid');
                foreach ($members as $tmp_uid => $item) {
                    $members[$tmp_uid]['name'] = !empty($remarks[$tmp_uid]) ? $remarks[$tmp_uid] : $members[$tmp_uid]['nickname'];
                }
                foreach ($member_uid_array as $id => $uid) {
                    if (isset($members[$uid])) {
                        $members[$uid]['index'] = $id;
                    }
                }
            }
        }

        if ($pinyin) {
            $members = (new \HanziToPinyin)->groupByInitials($members, 'id');
        }

        return $this->json(0, 'ok', $members);
    }

    /**
     * 设置群组备注
     */
    public function remark()
    {
		
		$this->json(0, "参数非法，不允许修改群名称！");exit;/*zedit(非群主不能修改群名称及自己备注)*/
        $post = $this->_post();
        // 检查必要字段是否为空
        $required = ['gid', 'remark'];
        foreach ($required as $key) {
            if (!isset($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }
        $login_uid = $post['login_uid'];
        $gid       = $post['gid'];

        if (empty($post['gid'])) {
            return $this->json(1, "缺少参数");
        }

        // 判断账户是否被禁用
        if ($this->userDisabled($login_uid)) {
            return $this->json(85, '该账户被禁用');
        }

        // 判断是否是群成员
        if (!Db::table('group_member')->field('remark')->where(['uid'=>$login_uid, 'gid'=>$gid])->find()) {
            return $this->json(2, '参数非法，不是该群成员');
        }

        $remark = ($post['remark']);
        if (strlen($remark) > 255) {
            return $this->json(2, '长度不能大于255个字节');
        }

        Db::table('group_member')->where(['uid'=>$login_uid, 'gid'=>$gid])->update(['remark' => $remark]);
        return $this->json(0);
    }

    /**
     * 禁言群成员
     *
     * @return array
     */
    public function forbiddenuser()
    {
        $post = $this->_post();

        // 检查必要字段是否为空
        $required = ['gid', 'uid', 'time'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }

        $gid        = $post['gid'];
        $uid        = $post['uid'];
        $login_uid  = $post['login_uid'];

        // 获取用户信息
        $user_list = DB::table('user')->where('uid', 'in', [$uid, $login_uid])->column('uid, nickname, avatar', 'uid');
        if (count($user_list) < 2) {
            return $this->json(2, '参数非法，用户不存在');
        }

        // 判断账户是否被禁用
        if ($this->userDisabled($login_uid)) {
            return $this->json(85, '该账户被禁用');
        }

        $duration   = (int)$post['time'];
        $group_info = Db::table('groups')->where([
            'gid' => $gid,
            'uid' => $login_uid,
        ])->find();

        if (!$group_info || $group_info['state'] === 'disabled') {
            return $this->json(2, '参数非法，群不存在或者非群创建人');
        }

        Db::table('group_member')->where('gid', $gid)->where('uid', $uid)->update(['forbidden' => time()+$duration]);

        // 获得这些人的信息
        $nickname = Db::table('user')->where('uid', $uid)->value('nickname');

        if ($duration > 0) {
            $time = '';
            if ($day = floor($duration / 86400)) {
                $time .= "{$day}天";
            }
            if ($hour = floor(($duration % 86400) / 3600)) {
                $time .= "{$hour}小时";
            }
            if ($minute = ceil(($duration % 3600) / 60)) {
                $time .= "{$minute}分钟";
            }

            $content = "[{$nickname}]({POPBASEURI}user/detail/$uid) 被禁言 $time";
        } else {
            $content = "[{$nickname}]({POPBASEURI}user/detail/$uid) 被解除禁言";
        }

        Db::table('message')->insert([
            'from'      => $uid,
            'to'        => $gid,
            'content'   => $content,
            'type'      => 'group',
            'sub_type'  => 'notice',
            'timestamp' => time()
        ]);

        $mid = Db::table('message')->getLastInsID();

        // 给所有人推送一条禁言消息
        $push = new Push();
        $push->emit('group-'.$gid, 'message', [
            'from'        => $login_uid,
            'from_name'   => $user_list[$login_uid]['nickname'],
            'from_avatar' => $user_list[$login_uid]['avatar'],
            'to'          => $gid,
            'to_name'     => $group_info['groupname'],
            'to_avatar'   => $group_info['avatar'],
            'content'     => $content,
            'timestamp'   => time(),
            'type'        => 'group',
            'sub_type'    => 'notice',
            'mid'         => $mid,
        ]);

        return $this->json(0);
    }

    /**
     * 离开群组
     */
    public function leave()
    {
        $post = $this->_post();
        $required = ['gid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
        $login_uid = $post['login_uid'];
        $gid       = $post['gid'];

        // 判断账户是否被禁用
        if ($this->userDisabled($login_uid)) {
            return $this->json(85, '该账户被禁用');
        }

        Db::table('group_member')->where([
            'uid'  => $login_uid,
            'gid'  => $gid
        ])->delete();

        return $this->json(0, 'ok');
    }


    /**
     * 更新用户的群组信息
     */
    public function memberupdate()
    {
        $post = $this->_post();
        $required = ['gid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }
        // 允许更新的字段
        $available_fields = ['state','last_read_time'];
        $data = [];
        foreach ($available_fields as $field) {
            if (isset($post[$field])) {
                $data[$field] = $post[$field];
            }
        }
        if (empty($field)) {
            return $this->json(1, "缺少参数");
        }

        $login_uid = $post['login_uid'];
        $gid       = $post['gid'];

        Db::table('group_member')->where([
            'uid' => $login_uid,
            'gid' => $gid
        ])->update($data);

        return $this->json(0, 'ok');
    }

    /**
     * 更新群组信息
     */
    public function update()
    {
        $post = $this->_post();
        $required = ['gid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }
        // 允许更新的字段
        $available_fields = ['groupname', 'state', 'avatar'];
        $data = [];
        foreach ($available_fields as $field) {
            if (isset($post[$field])) {
                $data[$field] = $post[$field];
            }
        }
        if (empty($field)) {
            return $this->json(1, "缺少参数");
        }



        $login_uid = $post['login_uid'];
        $gid       = $post['gid'];

        // 判断是否是群主
        $group_info = Db::table('groups')->where(['gid' => $gid])->find();
        if (!$group_info) {
            return $this->json(0, '记录不存在');
        }
        if ($group_info['uid'] != $login_uid) {
            return $this->json(2, '非法请求');
        }


        Db::table('groups')->where([
            'uid' => $login_uid,
            'gid' => $gid
        ])->update($data);




        /*给所有人推送一条修改群名称消息*/
        // 获取用户信息
        $user_list = DB::table('user')->where('uid', 'in', $login_uid)->column('uid, nickname, avatar', 'uid');
        if (count($user_list) < 1) {
            return $this->json(2, '参数非法，用户不存在');
        }
		$groupname=$data['groupname'];
		$content="群主 [".$user_list[$login_uid]['nickname']."]({POPBASEURI}user/detail/".$login_uid.") 将群名称修改为 ".$groupname;
		
        Db::table('message')->insert([
            'from'      => $login_uid,
            'to'        => $gid,
            'content'   => $content,
            'type'      => 'group',
            'sub_type'  => 'notice',
            'timestamp' => time()
        ]);

        $mid = Db::table('message')->getLastInsID();
		
        $push = new Push();
        $push->emit('group-'.$gid, 'message', [
            'from'        => $login_uid,
            'from_name'   => $user_list[$login_uid]['nickname'],
            'from_avatar' => $user_list[$login_uid]['avatar'],
            'to'          => $gid,
            'to_name'     => $group_info['groupname'],
            'to_avatar'   => $group_info['avatar'],
            'content'     => $content,
            'timestamp'   => time(),
            'type'        => 'group',
            'sub_type'    => 'notice',
			 'mid'    => $mid
        ]);
        /*给所有人推送一条修改群名称消息*/




        return $this->json(0, 'ok');
    }
	
	
	/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@zedit（新增模块）*/
    /*全群开启禁言*/
    public function forbiddengroup()
    {
        $post = $this->_post();
        $required = ['gid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
        $login_uid = $post['login_uid'];
        $gid       = $post['gid'];

        // 判断是否是群主
        $group_info = Db::table('groups')->where([
            'gid' => $gid
        ])->find();
        if (!$group_info) {
            return $this->json(0, '记录不存在');
        }
        if ($group_info['uid'] != $login_uid) {
            return $this->json(2, '非法请求');
        }

        // 获取用户信息
        $user_list = DB::table('user')->where('uid', 'in', $login_uid)->column('uid, nickname, avatar', 'uid');
        if (count($user_list) < 1) {
            return $this->json(2, '参数非法，用户不存在');
        }

		Db::table('groups')->where('gid', $gid)->update(['state' => 'forbidden']);


        /*给所有人推送一条全禁言消息*/
		$content="群主 [".$user_list[$login_uid]['nickname']."]({POPBASEURI}user/detail/".$login_uid.") 设置全群禁言";
		
        Db::table('message')->insert([
            'from'      => $login_uid,
            'to'        => $gid,
            'content'   => $content,
            'type'      => 'group',
            'sub_type'  => 'notice',
            'timestamp' => time()
        ]);

        $mid = Db::table('message')->getLastInsID();
		
        $push = new Push();
        $push->emit('group-'.$gid, 'message', [
            'from'        => $login_uid,
            'from_name'   => $user_list[$login_uid]['nickname'],
            'from_avatar' => $user_list[$login_uid]['avatar'],
            'to'          => $gid,
            'to_name'     => $group_info['groupname'],
            'to_avatar'   => $group_info['avatar'],
            'content'     => $content,
            'timestamp'   => time(),
            'type'        => 'group',
            'sub_type'    => 'notice',
			 'mid'    => $mid
        ]);
 		/*给所有人推送一条全禁言消息*/

        return $this->json(0);
    }	
	 /*全群开启禁言*/
	
	
    /*全群解除禁言*/
    public function chattinggroup()
    {
        $post = $this->_post();
        $required = ['gid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
        $login_uid = $post['login_uid'];
        $gid       = $post['gid'];

        // 判断是否是群主
        $group_info = Db::table('groups')->where([
            'gid' => $gid
        ])->find();
        if (!$group_info) {
            return $this->json(0, '记录不存在');
        }
        if ($group_info['uid'] != $login_uid) {
            return $this->json(2, '非法请求');
        }

        // 获取用户信息
        $user_list = DB::table('user')->where('uid', 'in', $login_uid)->column('uid, nickname, avatar', 'uid');
        if (count($user_list) < 1) {
            return $this->json(2, '参数非法，用户不存在');
        }

		Db::table('groups')->where('gid', $gid)->update(['state' => 'normal']);


        /*给所有人推送一条全禁言消息*/
		$content="群主 [".$user_list[$login_uid]['nickname']."]({POPBASEURI}user/detail/".$login_uid.") 解除全群禁言";
		
        Db::table('message')->insert([
            'from'      => $login_uid,
            'to'        => $gid,
            'content'   => $content,
            'type'      => 'group',
            'sub_type'  => 'notice',
            'timestamp' => time()
        ]);

        $mid = Db::table('message')->getLastInsID();
		
        $push = new Push();
        $push->emit('group-'.$gid, 'message', [
            'from'        => $login_uid,
            'from_name'   => $user_list[$login_uid]['nickname'],
            'from_avatar' => $user_list[$login_uid]['avatar'],
            'to'          => $gid,
            'to_name'     => $group_info['groupname'],
            'to_avatar'   => $group_info['avatar'],
            'content'     => $content,
            'timestamp'   => time(),
            'type'        => 'group',
            'sub_type'    => 'notice',
			 'mid'    => $mid
        ]);
 		/*给所有人推送一条全禁言消息*/

        return $this->json(0);
    }	
    /*全群解除禁言*/
	 
	 
    /*更换群头像*/
    public function guaUpdate()
    {
        $post = $this->_post();
        $required = ['gid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
        $login_uid = $post['login_uid'];
        $gid       = $post['gid'];

        // 判断是否是群主
        $group_info = Db::table('groups')->where(['gid' => $gid])->find();
        if (!$group_info) {
            return $this->json(0, '记录不存在');
        }
        if ($group_info['uid'] != $login_uid) {
            return $this->json(2, '非法请求');
        }

        // 获取用户信息
        $user_list = DB::table('user')->where('uid', 'in', $login_uid)->column('uid, nickname, avatar', 'uid');
        if (count($user_list) < 1) {
            return $this->json(2, '参数非法，用户不存在');
        }

		$avatar=$post['avatar'];
		Db::table('groups')->where('gid', $gid)->update(['avatar' => $avatar]);


        /*给所有人推送一条全禁言消息*/
		$content="群主 [".$user_list[$login_uid]['nickname']."]({POPBASEURI}user/detail/".$login_uid.") 更换了群头像";
		
        Db::table('message')->insert([
            'from'      => $login_uid,
            'to'        => $gid,
            'content'   => $content,
            'type'      => 'group',
            'sub_type'  => 'notice',
            'timestamp' => time()
        ]);

        $mid = Db::table('message')->getLastInsID();
		
        $push = new Push();
        $push->emit('group-'.$gid, 'message', [
            'from'        => $login_uid,
            'from_name'   => $user_list[$login_uid]['nickname'],
            'from_avatar' => $user_list[$login_uid]['avatar'],
            'to'          => $gid,
            'to_name'     => $group_info['groupname'],
            'to_avatar'   => $group_info['avatar'],
            'content'     => $content,
            'timestamp'   => time(),
            'type'        => 'group',
            'sub_type'    => 'notice',
			 'mid'    => $mid
        ]);
 		/*给所有人推送一条全禁言消息*/

        return $this->json(0);
    }	
    /*更换群头像*/

	 
	 
	/*@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@zedit（新增模块）*/
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
}
