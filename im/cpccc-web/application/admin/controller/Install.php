<?php

namespace app\admin\controller;

use think\Db;

/**
 * 安装向导
 * @package app\api\controller
 */
class Install extends Base
{
    /**
     * 检查服务器环境
     * @return mixed
     */
    public function index()
    {
        // 已经安装
        $db_config_file = CONF_PATH . 'database.php';
        if (is_file($db_config_file)) {
            Header('Location: /admin/install/step3');
            exit;
        }
        Header('Location: /admin/install/step2');
        exit;
    }

    /**
     * 设置数据库
     * @return mixed|void
     */
    public function step2()
    {
        $db_config_file = CONF_PATH . 'database.php';
        if (is_file($db_config_file)) {
            Header('Location: /admin/install/step3');
            exit;
        }

        $post = $this->request->post();
        if (!empty($post)) {
            $dbhost = $post['dbhost'];
            $dbport = $post['dbport'];
            $dbname = $post['dbname'];
            $dbuser = $post['dbuser'];
            // 为了避免dbpassword被htmlspecialchars转义，这里用$_POST['dbpassword']获取
            $dbpassword = $_POST['dbpassword'];
            $overwrite = $post['overwrite'] == 'yes';

            // 判断数据库名是否合法
            if (!preg_match('/^[a-zA-Z0-9_\-]+$/', $dbname)) {
                return $this->json(6, "数据库名字非法");
            }

            try {
                $db = Db::connect("mysql://{$dbuser}:{$dbpassword}@{$dbhost}:{$dbport}/#utf8");
                $ret = $db->query("show databases like '$dbname'");
                if (!$ret) {
                    $db->execute("create database `$dbname`");
                }
                $db = Db::connect("mysql://{$dbuser}:{$dbpassword}@{$dbhost}:{$dbport}/{$dbname}#utf8");
                $tables = $db->query("show tables");
                if ($tables && !$overwrite) {
                    return $this->json(7, "数据库{$dbname}不为空，无法安装。<br>如果需要请选择覆盖数据库。<br>注意：数据库{$dbname}覆盖后将无法恢复。");
                }
                $sql_query = file_get_contents(APP_PATH.'../install/data.sql');
                $sql_query = $this->remove_remarks($sql_query);
                $sql_query = $this->remove_comments($sql_query);
                $sql_query = $this->split_sql_file($sql_query, ';');
                foreach ($sql_query as $sql) {
                    $db->execute($sql);
                }

                $ret = file_put_contents($db_config_file, "<?php
// +----------------------------------------------------------------------
// | Author: zhf <zhf@cpccc.com>
// +----------------------------------------------------------------------

return [
    // 数据库调试模式
    'debug'          => false,
    // 是否严格检查字段是否存在
    'fields_strict'  => false,
    // 是否自动写入时间戳字段
    'auto_timestamp' => false,
    // 是否需要进行SQL性能分析
    'sql_explain'    => false,

    // 数据库类型
    'type'           => 'mysql',
    // 服务器地址
    'hostname'       => '{$dbhost}',
    // 数据库名
    'database'       => '{$dbname}',
    // 用户名
    'username'       => '{$dbuser}',
    // 密码
    'password'       => '{$dbpassword}',
    // 端口
    'hostport'       => '',
    // 数据库表前缀
    'prefix'         => '',
    // 数据库编码默认采用utf8
    'charset'        => 'utf8',
    // 数据库连接参数
    'params'         => [],
];
");
                if (!$ret) {
                    return $this->json(2, '无法保存数据库配置，请检查' . CONF_PATH . '目录是否可写');
                }

                return $this->json(0, 'ok');
            } catch (\Exception $e) {
                switch ($e->getCode()) {
                    case 1045:
                        return $this->json(1045, '数据库用户名或者密码不正确');
                }
                return $this->json(500, $e.'');
            }
        }

        return $this->fetch();
    }

    /**
     * appkey等服务器设置
     * @return mixed
     */
    public function step3()
    {
        // 如果step2没做完跳转到最开始
        $db_config_file = CONF_PATH . 'database.php';
        if (!is_file($db_config_file)) {
            Header('Location: /admin/install');
            exit;
        }

        // 数据库setting表已经填写
        $setting = Db::table('setting')->find();
        if ($setting) {
            Header('Location: /admin/install/step4');
            exit;
        }

        $post = $this->request->post();
        if ($post) {
            $ws_address = $post['ws_address'];
            $api_address = $post['api_address'];
            $appkey = $post['appkey'];
            $appsecret = $post['appsecret'];
            Db::table('setting')->insert([
                'ws_address'   => $ws_address,
                'api_address'  => $api_address,
                'appkey'       => $appkey,
                'appsecret'    => $appsecret
            ]);
            $this->json(0);
        }

        // 如果wolive-pusher存在，则自动使用本机地址
        $pusher_config_file = APP_PATH . '../../cpccc-socket/config.php';
        if (is_file($pusher_config_file)) {
            include $pusher_config_file;
        }
        $tmp = explode(':', $_SERVER['HTTP_HOST']);
        $domain = $tmp[0];
        $websocket_port = !empty($websocket_port) ? $websocket_port : 6060;
        $api_port = !empty($api_port) ? $api_port : 2060;
        $appkey = !empty($app_key) ? $app_key : '请填写cpccc-socket/config.php中的appkey';
        $appsecret = !empty($app_secret) ? $app_secret : '请填写cpccc-socket/config.php中的appsecret';
        $ws_address = "ws://{$domain}:{$websocket_port}";
        $api_address = "http://127.0.0.1:{$api_port}";

        $this->assign('appkey', $appkey);
        $this->assign('appsecret', $appsecret);
        $this->assign('ws_address', $ws_address);
        $this->assign('api_address', $api_address);

        return $this->fetch();
    }

    /**
     * 管理员设置
     * @return mixed|void
     */
    public function step4()
    {
        // 如果step2没做完跳转到最开始
        $db_config_file = CONF_PATH . 'database.php';
        if (!is_file($db_config_file)) {
            Header('Location: /admin/install');
            exit;
        }

        // 如果step3没做完则跳转到step3
        $setting = Db::table('setting')->find();
        if (!$setting) {
            Header('Location: /admin/install/step3');
            exit;
        }

        // 判断数据库里是否已经添加了管理员
        $admin_info = Db::table('admin')->find();
        if ($admin_info) {
            Header('Location: /admin/install/complete');
            exit;
        }

        $post = $this->request->post();
        if ($post) {
            $admin_name = $post['admin_name'];
            $password1 = $post['password1'];
            $password2 = $post['password2'];
            if ($password1 != $password2) {
                return $this->json(401, '两次输入密码不一致');
            }
            if (strlen($password1) < 6) {
                return $this->json(401, '密码长度不能小于6个字符');
            }
            Db::table('admin')->insert([
                'username' => $admin_name,
                'password' => md5("$admin_name-cpcccim-$password1")
            ]);
            $this->json(0);
        }
        return $this->fetch();
    }

    /**
     * 完成安装
     * @return mixed|void
     */
    public function complete()
    {
        $this->assign('del_install_file', is_file(APP_PATH . '/admin/controller/Install.php'));
        return $this->fetch();
    }

    /**
     * 去除sql文件中的注释
     * @param $output
     * @return string
     */
    protected function remove_comments($output)
    {
        $lines = explode("\n", $output);
        $output = "";

        // try to keep mem. use down
        $linecount = count($lines);

        $in_comment = false;
        for($i = 0; $i < $linecount; $i++)
        {
            if( preg_match("/^\/\*/", preg_quote($lines[$i])) )
            {
                $in_comment = true;
            }
            if( !$in_comment )
            {
                $output .= $lines[$i] . "\n";
            }

            if( preg_match("/\*\/$/", preg_quote($lines[$i])) )
            {
                $in_comment = false;
            }
        }
        unset($lines);
        return $output;
    }

    protected function remove_remarks($sql)
    {
        $lines = explode("\n", $sql);

        // try to keep mem. use down
        $sql = "";

        $linecount = count($lines);
        $output = "";

        for ($i = 0; $i < $linecount; $i++)
        {
            if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0))
            {
                if (isset($lines[$i][0]) && $lines[$i][0] != "#")
                {
                    $output .= $lines[$i] . "\n";
                }
                else
                {
                    $output .= "\n";
                }
                // Trading a bit of speed for lower mem. use here.
                $lines[$i] = "";
            }
        }

        return $output;
    }

    function split_sql_file($sql, $delimiter)
    {
        // Split up our string into "possible" SQL statements.
        $tokens = explode($delimiter, $sql);

        // try to save mem.
        $sql = "";
        $output = array();

        // we don't actually care about the matches preg gives us.
        $matches = array();

        // this is faster than calling count($oktens) every time thru the loop.
        $token_count = count($tokens);
        for ($i = 0; $i < $token_count; $i++)
        {
            // Don't wanna add an empty string as the last thing in the array.
            if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0)))
            {
                // This is the total number of single quotes in the token.
                $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
                // Counts single quotes that are preceded by an odd number of backslashes,
                // which means they're escaped quotes.
                $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

                $unescaped_quotes = $total_quotes - $escaped_quotes;

                // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
                if (($unescaped_quotes % 2) == 0)
                {
                    // It's a complete sql statement.
                    $output[] = $tokens[$i];
                    // save memory.
                    $tokens[$i] = "";
                }
                else
                {
                    // incomplete sql statement. keep adding tokens until we have a complete one.
                    // $temp will hold what we have so far.
                    $temp = $tokens[$i] . $delimiter;
                    // save memory..
                    $tokens[$i] = "";

                    // Do we have a complete statement yet?
                    $complete_stmt = false;

                    for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
                    {
                        // This is the total number of single quotes in the token.
                        $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
                        // Counts single quotes that are preceded by an odd number of backslashes,
                        // which means they're escaped quotes.
                        $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

                        $unescaped_quotes = $total_quotes - $escaped_quotes;

                        if (($unescaped_quotes % 2) == 1)
                        {
                            // odd number of unescaped quotes. In combination with the previous incomplete
                            // statement(s), we now have a complete statement. (2 odds always make an even)
                            $output[] = $temp . $tokens[$j];

                            // save memory.
                            $tokens[$j] = "";
                            $temp = "";

                            // exit the loop.
                            $complete_stmt = true;
                            // make sure the outer loop continues at the right point.
                            $i = $j;
                        }
                        else
                        {
                            // even number of unescaped quotes. We still don't have a complete statement.
                            // (1 odd and 1 even always make an odd)
                            $temp .= $tokens[$j] . $delimiter;
                            // save memory.
                            $tokens[$j] = "";
                        }

                    } // for..
                } // else
            }
        }

        return $output;
    }
}
