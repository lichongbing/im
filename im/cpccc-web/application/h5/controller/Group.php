<?php

namespace app\h5\controller;

class Group extends Base
{
    /**
     * 创建群
     *
     * @return string
     */
    public function create()
    {
        unset($_POST['avatar']);
        $group = new \app\api\controller\Group();
        return $group->create();
    }

    /**
     * 群详情
     *
     * @return string
     */
    public function detail()
    {
        $group = new \app\api\controller\Group();
        return $group->detail();
    }

    /**
     * 群成员
     *
     * @return string
     */
    public function members()
    {
        $group = new \app\api\controller\Group();
        return $group->members();
    }

    /**
     * 添加群成员
     *
     * @return string
     */
    public function memberadd()
    {
        $group = new \app\api\controller\Group();
        return $group->memberadd();
    }

    /**
     * 删除群成员
     *
     * @return string
     */
    public function memberdel()
    {
        $group = new \app\api\controller\Group();
        return $group->memberdel();
    }

    /**
     * 设置群备注
     *
     * @return string
     */
    public function remark()
    {
        $group = new \app\api\controller\Group();
        return $group->remark();
    }

    /**
     * 解散群组
     */
    public function delete()
    {
        $group = new \app\api\controller\Group();
        return $group->delete();
    }

    /**
     * 离开群组
     */
    public function leave()
    {
        $group = new \app\api\controller\Group();
        return $group->leave();
    }

    /**
     * 禁言某个用户
     */
    public function forbiddenuser()
    {
        $group = new \app\api\controller\Group();
        return $group->forbiddenuser();
    }

    /**
     * 更新用户的群组信息
     */
    public function memberupdate()
    {
        $group = new \app\api\controller\Group();
        return $group->memberupdate();
    }

    /**
     * 更新群组信息
     */
    public function update()
    {
        $group = new \app\api\controller\Group();
        return $group->update();
    }

    /**
     * 获取群头像
     */
    public function avatar()
    {
        header('Location: /avatar.php?uid='.$_GET['uid']);
        exit;
    }
	
	/*zedit*****************************************************************(新增模块)*/
	/*全群禁言开启*/
    public function forbiddengroup()
    {
        $group = new \app\api\controller\Group();
        return $group->forbiddengroup();
    }
	
	/*全群禁言解除*/
    public function chattinggroup()
    {
        $group = new \app\api\controller\Group();
        return $group->chattinggroup();
    }
	
	/*更换群头像*/
    public function guaUpdate()
    {
        $group = new \app\api\controller\Group();
        return $group->guaUpdate();
    }
	/*zedit*****************************************************************(新增模块)*/




}
