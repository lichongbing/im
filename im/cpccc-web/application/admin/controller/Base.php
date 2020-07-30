<?php

/**

 * Handler File Class

 *

 * @author liliang <liliang@wolive.cc>

 * @email liliang@wolive.cc

 * @date 2017/06/01

 */



namespace app\admin\controller;



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



        // 登录类不需要验证session

        if (get_class($this) === 'app\admin\controller\Login' || get_class($this) === 'app\admin\controller\Install') {

            return;

        }



        // 开启session

        session_start();

        $this->logincheck();

    }



    /**

     * 登录验证检查

     */

    protected function logincheck()

    {


//var_dump($_SESSION);die;

		$admin = cookie('admin');
		
		if (empty($_SESSION['admin'])) {
			
			$admin = $admin;
		}


        if (empty($admin)) {

            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {

                return $this->json(-1, '请登录');

            }

            header('Location: /admin/login/index');

            die;

        }

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

