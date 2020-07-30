<?php

namespace app\h5\controller;

/**
 * 上传类
 *
 * @package app\api\controller
 */
class Upload extends Base
{
    /**
     * 上传头像
     *
     * @return string
     */
    public function avatar()
    {
        $upload = new \app\api\controller\Upload();
        return $upload->avatar();
    }

    /*zedit上传群头像*/
    public function groupavatar()
    {
        $upload = new \app\api\controller\Upload();
        return $upload->groupavatar();
    }
	 /*zedit上传群头像*/
	 
    /**
     * 上传图片
     *
     * @return string
     */
    public function img()
    {
        $upload = new \app\api\controller\Upload();
        return $upload->img();
    }

    /**
     * 上传文件
     *
     * @return string
     */
    public function file()
    {
        $upload = new \app\api\controller\Upload();
        return $upload->file();
    }
}
