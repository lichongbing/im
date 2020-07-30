<?php

namespace app\api\controller;

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
        $uid = $_POST['login_uid'];
        $ret = $this->base('/upload/avatars/'. ($uid%1000) .'/'.$uid, '');
        if ($ret['code'] !== 0) {
            return $this->jsonArray($ret);
        }

        $realpath = $ret['data']['realpath'];
        unset($ret['data']['realpath']);

        try {
            // 判断是否是图片
            if (!getimagesize($realpath)) {
                unlink($realpath);
                return $this->json(500, '上传的不是图片');
            }
            // 生成缩略图
            $image = \think\Image::open($realpath);
            $image->thumb(150, 150, \think\Image::THUMB_CENTER)->save($realpath);
        } catch (\Exception $e) {
            unlink($realpath);
            return $this->json(501, '处理图片发生错误');
        }

        return $this->jsonArray($ret);
    }


 	/*zedit上传群头像*/
    public function groupavatar()
    {
        $gid = $_POST['gid'];
        $ret = $this->base('/upload/avatars/'. ($gid%1000) .'/'.$gid, '');
        if ($ret['code'] !== 0) {
            return $this->jsonArray($ret);
        }

        $realpath = $ret['data']['realpath'];
        unset($ret['data']['realpath']);
		
        try {
            // 判断是否是图片
            if (!getimagesize($realpath)) {
                unlink($realpath);
                return $this->json(500, '上传的不是图片');
            }
            // 生成缩略图
            $image = \think\Image::open($realpath);
            $image->thumb(150, 150, \think\Image::THUMB_CENTER)->save($realpath);
        } catch (\Exception $e) {
            unlink($realpath);
            return $this->json(501, '处理图片发生错误');
        }

        return $this->jsonArray($ret);
    }
	/*zedit上传群头像*/






    /**
     * 上传图片
     *
     * @return string
     */
    public function img()
    {
        $ret = $this->base('/upload/images/' . date('Ym'), '');
        if ($ret['code'] !== 0) {
            return $this->jsonArray($ret);
        }

        $realpath = $ret['data']['realpath'];
        unset($ret['data']['realpath']);

        try {
            // 判断是否是图片
            if(!getimagesize($realpath)) {
                unlink($realpath);
                return $this->json(500, '上传的不是图片');
            }
            // 生成缩略图,减少图片尺寸
            $image = \think\Image::open($realpath);
            $image->thumb(800, 800)->save($realpath);
        } catch (\Exception $e) {
            unlink($realpath);
            return $this->json(501, '处理图片发生错误');
        }

        return $this->jsonArray($ret);
    }

    /**
     * 上传文件
     *
     * @return string
     */
    public function file()
    {
        $ret = $this->base('/upload/files/' . date('Ym'), '');
        if ($ret['code'] !== 0) {
            return $this->jsonArray($ret);
        }
        unset($ret['data']['realpath']);
        return $this->jsonArray($ret);
    }

    /**
     * 发送消息
     *
     * @return string
     */
    protected function base($relative_dir, $ext_name = '.file')
    {
        $file = $_FILES['file'];
        //判断文件是否为空或者出错
        if (!empty($file) && $file['error'] == 0) {
            $base_dir = __DIR__  . '/../../../public';
            $full_dir = $base_dir . $relative_dir;
            if (!is_dir($full_dir)) {
                mkdir($full_dir, 0777, true);
            }
            $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $ext_forbidden_map = ['php', 'php3', 'php5', 'css', 'js', 'html', 'htm', 'asp', 'jsp'];
            if (in_array($ext, $ext_forbidden_map)) {
                // @todo
            }
            // 默认强制将所有文件名字改为.file后缀
            $ext = $ext.$ext_name;
            $relative_path = $relative_dir . '/' . date('d') . bin2hex(pack('Nn',time(), rand(1, 65535))) . ".$ext";
            $full_path = $base_dir . $relative_path;
            $file_data = file_get_contents($_FILES['file']['tmp_name']);
            if (false !== strpos($file_data, '<?php')) {
                return [
                    'code' => 400,
                    'msg'  => '文件非法',
                ];
            }
            if(move_uploaded_file($_FILES['file']['tmp_name'], $full_path)){
                $url = '//'. $_SERVER['HTTP_HOST'];
                return [
                    'code' => 0,
                    'msg'  => 'ok',
                    'data' => [
                        'src'           => $url.$relative_path,
                        'name'          => $_FILES['file']['name'],
                        'realpath'      => $full_path,
                        'size'          => $this->formatSize($_FILES['file']['size'])
                    ]
                ];
            }
            return [
                'code' => 500,
                'msg'  => '报错文件失败，请检查服务器upload目录写权限',
            ];
        }
        $code = !empty($file['error']) ? $file['error'] : 0;
        $code_map = [
            UPLOAD_ERR_INI_SIZE => '上传文件不能超过'.ini_get('upload_max_filesize'),
            UPLOAD_ERR_FORM_SIZE => '上传文件大小超过表单限制',
            UPLOAD_ERR_PARTIAL => '文件只有部分被上传',
            UPLOAD_ERR_NO_FILE => '没有文件被上传',
            UPLOAD_ERR_NO_TMP_DIR => '找不到临时文件夹',
            UPLOAD_ERR_CANT_WRITE => '文件保存失败',
        ];
        $code_msg = empty($file) ? '没找到上传文件': (isset($code_map[$code]) ? $code_map[$code] : '未知错误');
        return [
            'code' => 404,
            'msg'  => "上传错误($code_msg)",
        ];
    }

    /**
     * 格式化文件大小
     *
     * @param $file_size
     * @return string
     */
    protected function formatSize($file_size) {
        $size = sprintf("%u", $file_size);
        if($size == 0) {
            return("0 Bytes");
        }
        $sizename = array(" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB");
        return round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizename[$i];
    }
}
