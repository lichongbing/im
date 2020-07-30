<?php

namespace app\admin\controller;

use think\Db;

/**
 * 用户列表页
 */
class Message extends Base
{
    /**
     * 聊天监控页
     * @return mixed
     */
    public function index()
    {
        $setting = Db::table('setting')->find();
        $this->assign('setting', $setting);
        $this->assign('section', 'message');
        $this->assign('limit', 100);
        return $this->fetch();
    }



    /**
     * 获得聊天日志
     */
    public function chatget()
    {
        $get = $this->request->get();
        
        $limit = isset($get['limit']) ? $get['limit'] : 100;

        $message_list = (array)Db::table('message')->query("select * from message where sub_type!='notice' order by mid desc limit $limit");

        $uid_list = $gid_list = $group_list = $user_list= [];

        foreach ($message_list as $item) {
            if ($item['type'] == 'friend') {
                $uid_list[$item['from']] = $item['from'];
                $uid_list[$item['to']] = $item['to'];
            } else {
                $uid_list[$item['from']] = $item['from'];
                $gid_list[$item['to']] = $item['to'];
            }
        }

        if ($uid_list) {
            $user_list = Db::table('user')->where('uid', 'in', $uid_list)->column('uid,nickname as name,avatar', 'uid');
        }
        if ($gid_list) {
            $group_list = Db::table('groups')->where('gid', 'in', $gid_list)->column('gid,groupname as name,avatar', 'gid');
        }

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
}
