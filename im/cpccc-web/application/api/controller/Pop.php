<?php

namespace app\api\controller;

use think\Db;


class Pop extends Base
{
    /**
     * 获取用户基础数据
     *
     * @return string
     */
    public function get()
    {
        $get = $this->_get();
        $login_uid = $get['login_uid'];
        // 个人信息
        $user_info =  Db::table('user')->field('uid, username, sign, nickname, avatar')->where('uid', $login_uid)->find();
        if (empty($user_info)) {
            return $this->json(101, "用户不存在");
        }
		
        // 获取未读好友申请数
        $unread_friend_apply_count = Db::table('notice')->field('to')->where([
            'to'        => $login_uid,
            'operation' => 'not_operated',
        ])->count();

        $user_info['unread_friend_apply_count'] = (int)$unread_friend_apply_count;

        $friend_list = $friend_uid_list = $group_list = $group_gid_list = $chatting_uid_list = $chatting_gid_list = $all_uid_list = $all_user_info = $all_group_info = $last_friend_message_list = $last_group_message_list = $chatting_list = $last_friend_read_time_map = [];

        // 好友列表
        $friend_info_list = Db::table('friend')->where([
            'uid'      => $login_uid,
            'state'    => 'chatting'
        ])->column('friend_uid,remark,state,last_read_time,last_mid,unread_count', 'friend_uid');

        if ($friend_info_list) {
            foreach ($friend_info_list as $item) {
                $friend_uid = $item['friend_uid'];
                $friend_uid_list[] = $friend_uid;
                if ($item['state'] === 'chatting') {
                    $chatting_uid_list[$friend_uid]  = $friend_uid;
                    $last_friend_read_time_map[$friend_uid] = $item['last_read_time'];
                }
            }
        }
        $all_uid_list = array_merge($friend_uid_list, [$login_uid]);
        if ($friend_uid_list) {
            $friend_list = Db::table('user')->where('uid', 'in', $friend_uid_list)->column('uid, nickname, avatar, state', 'uid');
            // 给好友列表添加备注字段
            if ($friend_list) {
                foreach ($friend_list as $k => $v) {
                    $tmp_uid = $v['uid'];
                    $friend_list[$k]['name'] = !empty($friend_info_list[$tmp_uid]['remark']) ? $friend_info_list[$tmp_uid]['remark'] : $v['nickname'];
                    unset($friend_list[$k]['nickname']);
                }
            }
        }

        // 群组信息
        $group_info_list = Db::table('group_member')->where('uid', $login_uid)->column('gid,remark,state,last_read_time', 'gid');
        if ($group_info_list) {
            foreach ($group_info_list as $item) {
                $gid = $item['gid'];
                $group_gid_list[] = $gid;
                if ($item['state'] === 'chatting') {
                    $chatting_gid_list[] = $gid;
                }
                $last_group_read_time_map[$gid] = $item['last_read_time'];
            }
        }
        if ($group_gid_list) {
            $group_list = (array)Db::table('groups')->where('gid', 'in', $group_gid_list)->column('gid, groupname, avatar, uid', 'gid');
            foreach ($group_list as $gid => $item) {
                $group_list[$gid]['name'] = !empty($group_info_list[$gid]['remark']) ? $group_info_list[$gid]['remark'] : $item['groupname'];
                unset($group_list[$gid]['groupname']);
            }
        }

        // 会话中的好友最后一条消息，和未读消息数字
        if ($chatting_uid_list) {
            $mid_array = array();
            foreach ($chatting_uid_list as $tmp_uid => $item) {
                $mid_array[] = $friend_info_list[$tmp_uid]['last_mid'];
            }

            $last_friend_message_list_tmp = Db::table('message')->field('mid, from, to, content, timestamp, sub_type')->where('mid', 'in', $mid_array)->select();
			//zedit**************此处经常导到消息崩溃
            if (!empty($last_friend_message_list_tmp))  //原isset替换为!empty，原出现崩溃时用!isset既可复原
            {
                    foreach ($last_friend_message_list_tmp as $item) 
                    {
                        /*zedit（消息处理）*/
						if (!empty($item['sub_type']) && !empty($item['content']))
						{
                        	$item['content']=$this->Z_PMC($item['sub_type'],$item['content']);
						}
                        
                        $from = $item['from'];
                        $key = $from == $login_uid ? "{$from}-{$item['to']}" : "{$item['to']}-{$from}";
                        $last_friend_message_list[$key] = $item;
                        // 最后一条消息不是自己发的，并且消息时间戳大于上次阅读时间，则需要获取未读消息数
                        if ($from != $login_uid && isset($last_friend_read_time_map[$from]) && $item['timestamp'] > $last_friend_read_time_map[$from]) {
                            $unread_count = (int)$friend_info_list[$from == $login_uid ? $item['to'] : $from]['unread_count'];
                            $last_friend_message_list[$key]['unread_count'] = $unread_count > 10 ? '99+' : $unread_count;
                        } else {
                            $last_friend_message_list[$key]['unread_count'] = 0;
                        }
                    } 
            }
            //zedit**************此处经常导到消息崩溃
			
			
			
        }

        if ($chatting_gid_list) {
            // 会话中的请组里最后一条消息
            $where_in_str = "('" . implode("','", $chatting_gid_list) . "')";
            $last_group_message_info = Db::table('message')->query("select max(mid) as mid,`to` from message where type='group' and (`to` in  $where_in_str) group by `to`");

            if ($last_group_message_info) {
                $mid_array = array();
                foreach ($last_group_message_info as $item) {
                    $mid_array[] = $item['mid'];
                }
                $last_group_message_info = Db::table('message')->field('mid, from, to, content, sub_type,timestamp')->where('mid', 'in', $mid_array)->select();
                // 收集所有用户uid
                foreach ($last_group_message_info as $item) {
                    $gid                           = $item['to'];
                    $from                          = $item['from'];
                    $last_group_message_list[$gid] = $item;
                    $all_uid_list[]                = $item['from'];
                    if ($from != $login_uid && isset($last_group_read_time_map[$gid]) && $item['timestamp'] > $last_group_read_time_map[$gid]) {
                        $unread_count = (int)Db::table('message')->field('from')->where([
                            'to'        => $gid,
                            'type'      => 'group',
                            'sub_type'  => 'message',
                            'timestamp' => ['>', $last_group_read_time_map[$gid]],
                        ])->count();
                        $last_group_message_list[$gid]['unread_count'] = $unread_count > 10 ? '99+' : $unread_count;
                    } else {
                        $last_group_message_list[$gid]['unread_count'] = 0;
                    }
                }
            }
        }

        // 查询所有uid的用户数据
        if ($all_uid_list) {
            $all_user_info_tmp = Db::table('user')->field('uid, nickname, avatar, state')->where('uid', 'in', array_unique($all_uid_list))->select();
            if ($all_user_info_tmp) {
                foreach ($all_user_info_tmp as $item) {
                    $all_user_info[$item['uid']] = $item;
                }
            }
        }

        if ($chatting_gid_list) {
            $all_group_info_tmp = Db::table('groups')->where('gid', 'in', $chatting_gid_list)->select();
            if ($all_group_info_tmp) {
                foreach ($all_group_info_tmp as $item) {
                    $all_group_info[$item['gid']] = $item;
                }
            }
        }

        foreach ($chatting_uid_list as $uid) {
            if (!isset($all_user_info[$uid])) {
                continue;
            }
            $item = $all_user_info[$uid];
            $key = "$login_uid-$uid";
            $chatting_list['friend'.$uid] = [
                'type'   => 'friend',
                'id'     => $uid,
                'avatar' => $item['avatar'],
                'name'   => isset($friend_info_list[$uid]) && $friend_info_list[$uid]['remark'] ? $friend_info_list[$uid]['remark'] : $item['nickname'],
                'unread_count' => isset($last_friend_message_list[$key]) ? $last_friend_message_list[$key]['unread_count'] : 0,
                'items' => isset($last_friend_message_list[$key]) ? [$last_friend_message_list[$key] + ['avatar' => $all_user_info[$last_friend_message_list[$key]['from']]['avatar']]] : [],
            ];
        }

        foreach ($chatting_gid_list as $gid) {
            if (!isset($all_group_info[$gid])) {
                continue;
            }
            $item = $all_group_info[$gid];
            $items = [];
            if (!empty($last_group_message_list[$gid])) {
                $from = $last_group_message_list[$gid]['from'];
                $items = [
                    $last_group_message_list[$gid] +
                    [
                        'avatar' => $all_user_info[$from]['avatar'],
                        'name' => !empty($friend_info_list[$from]['remark']) ? $friend_info_list[$from]['remark'] : $all_user_info[$from]['nickname']
                    ]
                ];
            }
            $chatting_list['group'.$gid] = [
                'type' => 'group',
                'id'   => $gid,
                'avatar' => $item['avatar'],
                'name' => isset($group_info_list[$gid]['remark']) && $group_info_list[$gid]['remark'] ? $group_info_list[$gid]['remark'] : $item['groupname'],
                'unread_count' => isset($last_group_message_list[$gid]) ? (int)$last_group_message_list[$gid]['unread_count'] : 0,
                'items' => $items
            ];
			
			
			/*zedit（消息处理）*/
			if (!empty($chatting_list['group'.$gid]['items'][0]['content']) && !empty($chatting_list['group'.$gid]['items'][0]['sub_type'])) 
			{
				$chatting_list['group'.$gid]['items'][0]['content']=$this->Z_PMC($chatting_list['group'.$gid]['items'][0]['sub_type'],$chatting_list['group'.$gid]['items'][0]['content']);
			}
			
        }

        $friend_list = (new \HanziToPinyin)->groupByInitials($friend_list, 'name');

        $setting = Db::table('setting')->find();
        unset($setting['appsecret']);
        unset($setting['api_address']);
        unset($setting['dirty_words']);

        // chatting条数超过一定数量则关闭最久没用的对话，增强系统性能
        $chatting_limit = 30;
        $chatting_count = count($chatting_list);
		
        if ($chatting_count > $chatting_limit) {
            // 保留有未读消息的数据，按照最后发言时间排序
            $copy_chatting_list = $sort_array = [];
            $unread_items_count = 0;
            foreach ($chatting_list as $key => $chatting_item) {
                if ($chatting_item['unread_count'] > 0) {
                    $unread_items_count++;
                    continue;
                }
                $copy_chatting_list[$key] = !empty($chatting_item['items'][0]['timestamp']) ? $chatting_item['items'][0]['timestamp'] : 0;
            }
            if ($unread_items_count >= $chatting_limit) {
                $del_chatting_keys = $copy_chatting_list;
            } else {
                arsort($copy_chatting_list, SORT_NUMERIC);
                $del_chatting_keys = array_slice($copy_chatting_list, $chatting_limit-$unread_items_count);
            }
            $friend_chatting_uid = $group_chatting_gid = [];
            foreach ($del_chatting_keys as $key => $timestamp) {
                $type = $chatting_list[$key]['type'];
                $id = $chatting_list[$key]['id'];
                if ($type == 'friend') {
                    $friend_chatting_uid[$id] = $id;
                } else {
                    $group_chatting_gid[$id] = $id;
                }
                unset($chatting_list[$key]);
            }
            if ($friend_chatting_uid) {
                Db::table('friend')->where('uid', $login_uid)->where('friend_uid', 'in', $friend_chatting_uid)->update([
                    'state' => 'hidden'
                ]);
            }
            if ($group_chatting_gid) {
                Db::table('group_member')->where('uid', $login_uid)->where('gid', 'in', $group_chatting_gid)->update([
                    'state' => 'hidden'
                ]);
            }
        }

        return $this->json(0, 'ok', [
            'mine'     => $user_info,
            'friend'   => [],//$friend_list,
            'group'    => array_values($group_list),
            'chatting' => $chatting_list,
            'setting'  => $setting
        ]);
    }

    /**
     * 删除会话
     * @return array
     */
    public function delchat()
    {
        $post = $this->_post();
        // 检查必要字段是否为空
        $required = ['id', 'type'];
        foreach ($required as $key) {
            if (empty($post[$key])) {
                return $this->json(1, '缺少参数');
            }
        }
        $login_uid = $post['login_uid'];
        $id        = $post['id'];
        switch ($post['type']) {
            case 'friend':
                Db::table('friend')->where(['uid' => $login_uid, 'friend_uid' => $id])->update(['state' => 'hidden']);
                break;
            case 'group':
                Db::table('group_member')->where(['uid' => $login_uid, 'gid' => $id])->update(['state' => 'hidden']);
                break;
            default:
                return $this->json(2, '参数非法');
        }
        return $this->json(0);
    }

}
