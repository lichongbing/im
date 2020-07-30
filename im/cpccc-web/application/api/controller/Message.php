<?php

namespace app\api\controller;

use app\api\model\Push;
use think\Db;

/**
 * 消息类
 * @package app\api\controller
 */
class Message extends Base
{
    /**
     * 发送消息
     *
     * @return string
     */
    public function send()
    {
        $post = $this->_post();
        // 检查必要字段是否为空
        $required = ['to', 'type', 'content'];
        foreach ($required as $key) {
            if (!isset($post[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
		

        if ($post['content'] === '') {
            return $this->json(2, '发送内容不能为空');
        }

        // 判断类型
        $type = $post['type'];
        if ($type !== 'friend' && $type !== 'group') {
            return $this->json(2, "参数非法");
        }

        $from        = $post['login_uid'];
        $from_name   = '';
        $from_avatar = '';
        $to          = $post['to'];
        $to_name     = '';
        $to_avatar   = '';
        $content     = $post['content'];
        $timestamp   = time();

        // 读取系统配置
        $setting = Db::table('setting')->find();

        // 过滤脏字
        if ($setting['dirty_words']) {
            $dirty_words = explode("\n", $setting['dirty_words']);
            $find = $replace = [];
            foreach ($dirty_words as $key => $value) {
                $value = trim($value);
                if ($value) {
                    $find[] = $value;
                    $replace[] = str_pad('', function_exists('mb_strlen') ? mb_strlen($value) : 2, '*');
                }
            }
            if ($dirty_words) {
                $content = str_replace($dirty_words, $replace, $content);
            }
        }

        // 找出用户信息
        if ($type == 'friend') {
            // 判断系统配置是否开启了允许私聊
            if ($setting && $setting['private_chat'] !== 'on') {
                return $this->json(83, '系统设置了禁止私聊');
            }

            // 如果系统配置不允许陌生人之间聊天，则判断是否是好友
            if ($setting && $setting['stranger_chat'] !== 'on') {
                if (!Db::table('friend')->where(['uid'=>$from, 'friend_uid' => $to])->column('uid')) {
                    return $this->json(84, '不是好友，无法发起会话');
                }
            }

            $user_list = Db::table('user')->where('uid', 'in', [$from, $to])->column('uid,nickname,avatar,account_state', 'uid');
            if (count($user_list) < 2) {
                return $this->json(101, '用户不存在');
            }
			


				
            // 判断发言用户是否被禁用
            if ($user_list[$from]['account_state'] == 'disabled') {
                return $this->json(85, '该账户已经被禁用');
            }

            foreach ($user_list as $item) {
                if ($item['uid'] == $from) {
                    $from_avatar = $item['avatar'];
                    $remark = Db::table('friend')->where([
                        'uid'        => $to,
                        'friend_uid' => $from
                    ])->value('remark');
                    $from_name = $remark ? $remark : $item['nickname'];
                } else {
                    $to_name   = $item['nickname'];
                    $to_avatar = $item['avatar'];
                }
            }
			
			$sub_type="message";
			$sub_type=$this->Z_LMC($sub_type,$content);/*zedit（识别消息类型）*/
			
            // 插入到数据库
            Db::table('message')->insert([
                'from'       => $from,
                'to'         => $to,
                'content'    => $content,
                'type'       => $type,
                'timestamp'  => $timestamp,
				'sub_type'   => $sub_type
            ]);
            $mid = Db::table('message')->getLastInsID();

            Db::table('friend')->where([
                'uid'        => $from,
                'friend_uid' => $to
            ])->update([
                'last_mid'     => $mid,
                'unread_count' => 0,
                'state'        => 'chatting'
            ]);

            /*Db::table('friend')->where([
                'uid'        => $to,
                'friend_uid' => $from
            ])->update([
                'last_mid'     => $mid,
                'unread_count' => 0,
                'state'        => 'chatting'
            ]);*/

            Db::table('friend')->execute('update friend set `state`="chatting", `last_mid`=:mid, `unread_count`=`unread_count`+1 where `uid` = :uid and `friend_uid`=:friend_uid', [
                'uid'         => $to,
                'friend_uid'  => $from,
                'mid'         => $mid
            ]);
        } else {
            // 判断系统配置是否开启了允许群聊
            if ($setting && $setting['group_chat'] !== 'on') {
                return $this->json(83, '系统设置了禁止群聊');
            }

            $user_info = Db::table('user')->field('uid,nickname,avatar,account_state')->where('uid', 'in', $from)->find();
            if (!$user_info) {
                return $this->json(101, "用户不存在");
            }

            // 判断发言用户是否被禁用
            if ($user_info['account_state'] == 'disabled') {
                return $this->json(3, '该账户已经被禁用');
            }

            // 判断是否是群成员
            $member_info = Db::table('group_member')->where(['gid' => $to, 'uid' => $from])->find();
            if(!$member_info) {
                return $this->json(2, '不是群组成员无法发言');
            }
            // 判断是否在禁言中
            if ($member_info['forbidden'] > time()) {
                return $this->json(4, '已被禁言，无法发送消息');
            }


            $from_name   = $user_info['nickname'];
            $from_avatar = $user_info['avatar'];
            $group_info  = Db::table('groups')->where('gid', $to)->find();
            if (!$group_info) {
                return $this->json(701, "群组不存在");
            }


            if ($group_info['state'] == 'disabled') {
                return $this->json(85, '该群组已经被禁用');
            }
			
			
			/*zedit（增加判断群是否在全群禁言，禁言时只能群主能发言）*/
             if ($group_info['state'] == 'forbidden' and $group_info['uid']!=$from) {
                return $this->json(200, '全群禁言，无法发送消息');
            }


            $to_name   = $group_info['groupname'];
            $to_avatar = $group_info['avatar'];
			
			$sub_type="message";
			$sub_type=$this->Z_LMC($sub_type,$content);
			
            // 插入到数据库
            Db::table('message')->insert([
                'from'       => $from,
                'to'         => $to,
                'content'    => $content,
                'type'       => $type,
                'timestamp'  => $timestamp,
                'sub_type'   => $sub_type
            ]);
            $mid = Db::table('message')->getLastInsID();

            // 更新所群的所有成员群状态为chatting
            Db::table('group_member')->where(['gid' => $to, 'state' => 'hidden'])->update(['state' => 'chatting']);
        }

        // 执行推送
		$content=$this->Z_PMC($sub_type,$content);/*zedit（消息内容处理）*/
		
        $push    = new Push();
        $channel1 = $type == 'friend' ? "user-$to" : "group-$to";
        $event   = 'message';
        $data    = [
            'from'        => $from,
            'from_name'   => $from_name,
            'from_avatar' => $from_avatar,
            'to'          => $to,
            'to_name'     => $to_name,
            'to_avatar'   => $to_avatar,
            'content'     => $content,
            'timestamp'   => $timestamp,
            'type'        => $type,
            'sub_type'    => $sub_type,
            'mid'         => $mid,
        ];

        if (!empty($post['uniqueId'])) {
            $data['uniqueId'] = $post['uniqueId'];
        }
		
		//p($channel1);
		//p($event);
		//p($data);die;
		
        // 给群推送或者对方推送
        $result = $push->emit($channel1, $event, $data);
        if ($result['status'] != 200) {
            return $this->json(500, '服务器错误，推送失败：'.$result['body']);
        }

        // 如果是单聊，给自己也推送一次，方便多端同步聊天数据
        if ($type == 'friend') {
            $push->emit("user-$from", $event, $data);
        }

        // 给管理后台推送，用来监控所有人的消息
        $push->emit('cpcccim_all_user_message_8shf72skf', $event, $data);

        return $this->json(0, 'ok', ['mid' => $mid]);
    }

    /**
     * 消息撤回接口
     *
     * @return array
     */
    public function revoke()
    {
        $post = $this->_post();
        // 检查必要字段是否为空
        $required = ['type', 'id', 'mid'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, '缺少参数');
            }
        }

        // 判断类型
        $type = $post['type'];
        if ($type !== 'friend' && $type !== 'group') {
            return $this->json(2, "参数非法");
        }

        $id        = $post['id'];
        $mid       = $post['mid'];
        $type      = $post['type'];
        $login_uid = $post['login_uid'];

        // 读取系统配置
        $setting = Db::table('setting')->find();

        // 获得消息
        $message = Db::table('message')->where(['mid'=> $mid])->find();
        if (!$message) {
            return $this->json(404, '消息不存在');
        }
		/*zedit（限时撤回功能）*/
		else
		{
			$n_date=date('Y-m-d H:i:s', strtotime('-5 minute'));
			$n_timestamp=strtotime($n_date);
			$m_timestamp=$message['timestamp'];
			
			if ($n_timestamp>$m_timestamp)
			{
				 return $this->json(505, '只能撤回5分钟内的消息');
			}
			//$ad_json = addslashes($timestamp."=".json_encode($message));Db::table('ls')->where(['id'=>1])->update(['text' => $ad_json]);
		}
		/*zedit（限时撤回功能）*/
		
        // 找出用户信息
        if ($type == 'friend') {

            $user_list = Db::table('user')->where('uid', 'in', [$id, $login_uid])->column('uid,nickname,avatar,account_state', 'uid');
            if (count($user_list) < 2) {
                return $this->json(101, '用户不存在');
            }

            // 判断发起撤回者账户是否被禁用
            if ($user_list[$login_uid]['account_state'] == 'disabled') {
                return $this->json(85, '该账户已经被禁用');
            }

            // 判断是否是自己发的消息
            if ($message['from'] != $login_uid) {
                return $this->json(2, '非法请求');
            }

        } else {
            $user_info = Db::table('user')->field('uid,nickname,avatar,account_state')->where('uid', 'in', $login_uid)->find();
            if (!$user_info) {
                return $this->json(101, "用户不存在");
            }

            // 判断发言用户是否被禁用
            if ($user_info['account_state'] == 'disabled') {
                return $this->json(3, '该账户已经被禁用');
            }

            // 判断是否是群成员
            $member_info = Db::table('group_member')->where(['gid' => $id, 'uid' => $login_uid])->find();
            if(!$member_info) {
                return $this->json(2, '不是群组成员，无法撤回');
            }
            // 判断是否在禁言中
            if ($member_info['forbidden'] > time()) {
                return $this->json(4, '已被禁言，无法撤回');
            }

            $group_info  = Db::table('groups')->where('gid', $id)->find();
            if (!$group_info) {
                return $this->json(701, "群组不存在");
            }

            if ($group_info['state'] == 'disabled') {
                return $this->json(85, '该群组已经被禁用');
            }

            // 判断是否是自己发的消息
            if ($message['from'] != $login_uid) {
                // 如果是群主也可以撤回
                if ($group_info['uid'] != $login_uid) {
                    return $this->json(2, '非法请求');
                }
            }
        }

        Db::table('message')->where(['mid'=> $mid, 'to' => $id])->update([
            'sub_type'    => 'notice',
            'content'     => '此消息已撤回',
        ]);

        // 执行推送
        $push    = new Push();
        $channel1 = $type == 'friend' ? "user-$id" : "group-$id";
        $event   = 'revoke';
        $data    = [
            'type' => $type,
            'id'   => $id,
            'mid'  => $mid,
            'uid'  => $login_uid
        ];

        // 给群推送或者对方推送
        $result = $push->emit($channel1, $event, $data);
        if ($result['status'] != 200) {
            return $this->json(500, '服务器错误，推送失败：'.$result['body']);
        }

        // 如果是单聊，给自己也推送一次，方便多端同步聊天数据
        if ($type == 'friend') {
            $push->emit("user-$login_uid", $event, $data);
        }

        return $this->json(0, 'ok');
    }

    /**
     * 获得与某个用户或群的消息
     *
     * @return string
     */
    public function get()
    {
        $get = $this->_get();
        $required = ['type', 'id'];
        foreach ($required as $key) {
            if (empty($get[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
	
        $type         = $get['type'];
        $limit        = isset($get['limit']) ? intval($get['limit']) : 20;
        $limit        = $limit > 100 ? 100 : $limit;
        $mid          = isset($get['mid']) ? intval($get['mid']) : 2147483648;
        $message_list = [];
        $login_uid    = $get['login_uid'];
        switch ($type) {
            case 'friend':
                $friend_uid = $get['id'];
                $user_info = Db::table('user')->field('uid,nickname,avatar')->where('uid', 'in', [$login_uid, $friend_uid])->select();
                if (count($user_info) < 2) {
                    return $this->json(0, 'ok', $message_list);
                }
                $user_info_map = [];
                foreach ($user_info as $item) {
                    $user_info_map[$item['uid']] = $item;
                }
                $message_list = (array)Db::table('message')->query("select * from message where ((`from`=? and `to`=?) or (`from`=? and `to`=?)) and mid < ? and `type`='friend' order by mid desc limit ?", [$login_uid, $friend_uid, $friend_uid, $login_uid, $mid, $limit]);
                foreach ($message_list as $key => $value) {
					
					$value['content']=$this->Z_PMC($value['sub_type'],$value['content']);/*zedit（消息内容处理）*/		
					
                    $message_list[$key] = [
                        'mid'       => $value['mid'],
                        'from'      => $value['from'],
                        'avatar'    => $user_info_map[$value['from']]['avatar'],
                        'timestamp' => $value['timestamp'],
                        'content'   => $value['content'],
                        'sub_type'  => $value['sub_type']
                    ];
                }
                $message_list = array_reverse($message_list);

                Db::table('friend')->where([
                    'uid'            => $login_uid,
                    'friend_uid'     => $friend_uid
                ])->update([
                    'last_read_time' => time(),
                    'unread_count'   => 0
                ]);
                break;
            case 'group':
                $gid = $get['id'];
                $message_list_tmp = Db::table('message')->query("select * from message where `to`=? and `type`='group' and mid<? order by mid desc limit ?", [$gid, $mid, $limit]);
                if ($message_list_tmp) {
                    $uid_array = [];
                    foreach ($message_list_tmp as $item) {
                        $tmp_uid = $item['from'];
                        $uid_array[$tmp_uid] = $tmp_uid;
                    }
                    $uid_info = Db::table('user')->field('uid,avatar,nickname')->where('uid', 'in', $uid_array)->select();
                    $uid_info_map = [];
                    foreach ($uid_info as $item) {
                        $uid_info_map[$item['uid']] = $item;
                    }

                    // 获取备注
                    if ($login_uid) {
                        $remark_array = Db::table('friend')->where('friend_uid', 'in', $uid_array)->where('uid', $login_uid)->column('friend_uid,remark', 'friend_uid');
                    }

                    foreach ($message_list_tmp as $key=>$item) {
                        $tmp_uid = $item['from'];
                        // 数据不一致，删除
                        if (!isset($uid_info_map[$tmp_uid])) {
                            continue;
                        }
						
						$item['content']=$this->Z_PMC($item['sub_type'],$item['content']);/*zedit（消息内容处理）*/	
						
                        $message_list[] = [
                            'mid'       => $item['mid'],
                            'from'      => $item['from'],
                            'avatar'    => $uid_info_map[$tmp_uid]['avatar'],
                            'timestamp' => $item['timestamp'],
                            'content'   => $item['content'],
                            'sub_type'  => $item['sub_type'],
                            'name'      => !empty($remark_array[$tmp_uid]) ? $remark_array[$tmp_uid] : $uid_info_map[$tmp_uid]['nickname']
                        ];
                    }
                    $message_list = array_reverse($message_list);
                    if ($login_uid) {
                        Db::table('group_member')->where([
                            'uid' => $login_uid,
                            'gid' => $gid
                        ])->update(['last_read_time' => time()]);
                    }
                }
                break;
            default:
                $this->json(2, "参数非法");
        }

        return $this->json(0, 'ok', $message_list);
    }

    /**
     * 获得与某个用户或群的未读消息数字
     *
     * @return string
     */
    public function unreadcount()
    {
        $get = $this->_get();
        $required = ['type', 'id'];
        foreach ($required as $key) {
            if (empty($get[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
        $type         = $get['type'];
        $unread_count = 0;
        $login_uid     = $get['login_uid'];
        switch ($type) {
            case 'friend':
                $friend_uid = $get['id'];
                $last_read_time = Db::table('friend')->where(['uid' => $login_uid, 'friend_uid' => $friend_uid])->value('last_read_time');
                // 不是好友返回1
                if (null === $last_read_time) {
                    return $this->json(0, 'ok', 1);
                }
                $unread_count = (int)Db::table('message')->field('from')->where([
                    'from'      => $friend_uid,
                    'to'        => $login_uid,
                    'type'      => 'friend',
                    'sub_type'  => 'message',
                    'timestamp' => ['>', (int)$last_read_time],
                ])->count();
                break;
            case 'group':
                $gid = $get['id'];
                $last_read_time = Db::table('group_member')->where(['uid' => $login_uid, 'gid' => $gid])->value('last_read_time');
                // 不是群成员返回1
                if (null === $last_read_time) {
                    return $this->json(0, 'ok', 1);
                }
                $unread_count = (int)Db::table('message')->field('from')->where([
                    'to'        => $gid,
                    'type'      => 'group',
                    'sub_type'  => 'message',
                    'timestamp' => ['>', (int)$last_read_time],
                ])->count();
                break;
            default:
                $this->json(2, "参数非法");
        }

        return $this->json(0, 'ok', $unread_count);
    }

    /**
     * 更新与某个人或者好友最后阅读消息的时间，用来确定哪些消息未读
     */
    public function updateLastReadTime()
    {
        $post = $this->_post();
        $required = ['id', 'type'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, "缺少参数");
            }
        }
        $type       = $post['type'];
        $login_uid  = $post['login_uid'];

        switch ($type) {
            case 'friend':
                $friend_uid = $post['id'];
                if ($login_uid == $friend_uid) {
                    return $this->json(2, '参数非法');
                }
                Db::table('friend')->where([
                    'uid'            => $login_uid,
                    'friend_uid'     => $friend_uid
                ])->update([
                    'last_read_time' => time(),
                    'unread_count'   => 0
                ]);
                break;
            case 'group':
                $gid = $post['id'];
                Db::table('group_member')->where([
                    'uid'            => $login_uid,
                    'gid'            => $gid
                ])->update(['last_read_time' => time()]);
                break;
            default :
                return $this->json(2, '参数非法');
        }

        return $this->json(0, 'ok');
    }
	
}
