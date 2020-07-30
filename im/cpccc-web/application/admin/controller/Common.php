<?php

namespace app\admin\controller;

use think\Db;
use app\api\model\Push;

/**
 * 通用设置页
 */
class Common extends Base
{
    /**
     * 通用设置页
     * @return mixed
     */
    public function index()
    {
        $setting = Db::table('setting')->find();

        $this->assign('setting', $setting);
        $this->assign('section', 'common');
        return $this->fetch();
    }

    /**
     * 通用设置页
     * @return mixed
     */
    public function system()
    {
        $setting = Db::table('setting')->find();

        $this->assign('setting', $setting);
        $this->assign('section', 'system');
        return $this->fetch();
    }

    /**
     * 保存通用设置
     */
    public function save()
    {
        $post = $this->request->post();
        Db::table('setting')->where('id','>', 0)->update($post);
        $setting = Db::table('setting')->find();
        if ($setting) {
            unset($setting['appsecret']);
            $push = new Push();
            $push->emit('global', 'settingChange', $setting);
        }
        return $this->json(0);
    }
}
