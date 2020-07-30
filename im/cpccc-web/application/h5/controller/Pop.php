<?php

namespace app\h5\controller;



class Pop extends Base
{

    /**
     * 获取用户基础数据
     *
     * @return string
     */
    public function get()
    {
        $pop = new \app\api\controller\Pop();
        return $pop->get();
    }

    /**
     * 删除会话
     *
     * @return string
     */
    public function delchat()
    {
        $pop = new \app\api\controller\Pop();
        return $pop->delchat();
    }

}
