<?php
/**
 * Handler File Class
 *
 * @author liliang <liliang@wolive.cc>
 * @email liliang@wolive.cc
 * @date 2017/06/01
 */

namespace app\h5\controller;

use think\Controller;

/**
 * 基础验证
 */
class Base extends Controller
{
    public function _initialize()
    {
        // 标记是内部调用api接口，让其返回数组，不返回json
        $_GET['internal_call'] = true;
        $_GET['timestamp'] = time();
        $_GET['cpccc_token'] = md5($_GET['timestamp'] . \think\Config::get('cpccc.api_secret'));

        // 登录相关的类不需要验证session
        if (get_class($this) === 'app\h5\controller\Login') {
            return;
        }

        // 检查系统是否执行了安装
        if (!is_file(APP_PATH . '../config/database.php')) {
            return $this->json(-2, '服务端未安装');
        }

        // 开启session
        session_start();
        if (empty($_SESSION['userinfo'])) {
            return $this->json(-1, '请登录');
        }

        // 默认增加 login_uid 字段，方便api调用
        $_GET['login_uid'] = $_POST['login_uid'] = $_SESSION['userinfo']['uid'];
    }

    protected function json($code, $msg = '', $data = [])
    {
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode([
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ]);
        die;
    }

    protected function jsonArray($data)
    {
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($data);
        die;
    }
}
