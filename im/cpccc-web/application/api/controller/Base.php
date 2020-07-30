<?php
/**
 * Handler File Class
 *
 * @author liliang <liliang@wolive.cc>
 * @email liliang@wolive.cc
 * @date 2017/06/01
 */

namespace app\api\controller;

use think\Controller;
use think\Db;

/**
 * 基础验证
 */
class Base extends Controller
{
    /**
     * api接口基础验证，验证api请求合法性
     * @return array
     */
    public function _initialize()
    {
		
		
		$db_config = include ROOT_PATH . '/config/database.php';
		
		//var_dump($db_config['username']);die;
		
		
		
		

		/*zedit（自发送消息）*/
		$conn=mysqli_connect('127.0.0.1',$db_config['username'],$db_config['password'],$db_config['database']);
		$ovm="yes";
		
		if (isset($_GET['sak'])){$_POST=$_GET;}
		
		
		if (isset($_POST['sak']))
		{
			if ($_POST['sak']=="99e36bdf9c656e89f03687825285fed1")
			{
				/*获取当前登录用户*/
				$m_phpsessid=$_COOKIE[session_name()];
				$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
				$result=mysqli_query($conn,$sql);
				$row=mysqli_fetch_array($result);
				$_POST['login_uid']=$row['uid'];
				/*获取当前登录用户*/
				$_POST['timestamp']=time();
				$_GET=$_POST;
				$ovm="no";
			}
		}
		/*zedit（自发送消息）*/
		
		
        if (empty($_GET['cpccc_token']) && empty($_GET['timestamp'])) {
            return $this->json(1, '缺少参数（代码PAACB-1）');
        }
        $timestamp = (int)$_GET['timestamp'];
        // 判断时间戳是否在5分钟以内
        $time_diff = time() - $timestamp;
        if ($time_diff > 300 || $timestamp < -300) {
            return $this->json(3, '非法请求');
        }
        // 这些类不检查login_uid字段
        $exclude = ['app\api\controller\User'];
        if (!in_array(get_class($this), $exclude)) {
            // 所有请求必须有login_uid
            if (!isset($_GET['login_uid']) && !isset($POST['login_uid'])) {
                return $this->json(1, '缺少参数（代码PAACB-2）');
            }
			/*****************************************************zedit（输出用户SESSIOIN）*/
			else
			{
					$conn=mysqli_connect('127.0.0.1',$db_config['username'],$db_config['password'],$db_config['database']);
					$m_ip=$_SERVER["REMOTE_ADDR"];
					$session_name=session_name();
					$m_phpsessid=$_COOKIE[$session_name];
					if ($_GET['login_uid']>0){$m_uid=$_GET['login_uid'];}else{$m_uid=$POST['login_uid'];}
					$m_dt=date("Y-m-d H:i:s");
					
					$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
					$result=mysqli_query($conn,$sql);
					$row=mysqli_fetch_array($result);
					$g_uid=$row['uid'];
					if ($row['id']=="" or $m_uid!=$g_uid)
					{
						$sql="INSERT INTO user_session (ip,phpsessid,uid,dt) VALUES ('$m_ip','$m_phpsessid','$m_uid','$m_dt')";
						mysqli_query($conn,$sql);	
					}
			}
			/*****************************************************zedit（输出用户SESSIOIN）*/
        }
		
		if ($ovm!="no")
		{
			if ($_GET['cpccc_token'] !== md5($timestamp . \think\Config::get('cpccc.api_secret'))) {
				return $this->json(3, '非法请求');
			}
		}
    }

    /**
     * 合成json数据
     *
     * @param $code
     * @param string $msg
     * @param array $data
     * @return array
     */
    protected function json($code, $msg = '', $data = [])
    {
        $return = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];		
        if (isset($_GET['internal_call'])) {
            return $return;
        }
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($return);
        die;
    }
	
	/*
    protected function m_json($code, $msg = '', $data = [])
    {
        $return = [
            'code' => $code,
            'msg'  => $msg,
            'data' => $data
        ];		
		
		
		
		$ad_json = addslashes(json_encode($data));Db::table('ls')->where(['id'=>1])->update(['text' => $ad_json]);
		
		
        if (isset($_GET['internal_call'])) {
            return $return;
        }
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($return);
        die;
    }
	*/
	
	
	
    /**
     * 合成json数据
     *
     * @param $data
     * @return array
     */
    protected function jsonArray($data)
    {
        header('Content-Type: application/json;charset=utf-8');
        echo json_encode($data);
        die;
    }

    /**
     * 用户密码md5
     *
     * @param $username
     * @param $password
     * @return string
     */
    protected function md5($username, $password)
    {
        return md5("$username-cpcccim-$password");
    }

    /**
     * 判断用户是否被禁用
     *
     * @param $uid
     * @return bool
     */
    protected function userDisabled($uid)
    {
        $account_state = Db::table('user')->where('uid', $uid)->value('account_state');
        return !$account_state || $account_state === 'disabled';
    }

    /**
     * 过滤$_GET
     * @return mixed
     */
    protected function _get()
    {
        array_walk_recursive($_GET, function (&$item){
            $item = htmlspecialchars($item);
        });
        return $_GET;
    }

    /**
     * 过滤$_POST
     * @return mixed
     */
    protected function _post()
    {
        array_walk_recursive($_POST, function (&$item){
            $item = htmlspecialchars($item);
        });
        return $_POST;
    }
	
	
	
    /*zedit（消息内容处理函数）*/
    protected function Z_PMC($sub_type,$content)
    {
		$ok_content=$content;
		
		//位置类型
		if ($sub_type=="position")
		{
			$data_content=str_replace(array("{nb}{position}",'[',']'),"",$content);
			$data=explode("{|}",$data_content);
			
			$name=$data[0];$name_str="'".$name."'";
			$address=$data[1];$address_str="'".$address."'";
			$location=$data[2];$location_str="'".$location."'";
			
			$ok_content='<lmap{@kg@}style="background:{@kg@}url(https://restapi.amap.com/v3/staticmap?location='.$location.'&zoom=18&size=680*380&markers=mid,,A:'.$location.'&key=ee95e52bf08006f63fd29bcfbcf21df0){@kg@}no-repeat{@kg@}-0rem{@kg@}1.1rem;background-size:100%;"{@kg@}onclick="Map_Show('.$name_str.','.$address_str.','.$location_str.')"><i>'.$name."</i>".'<dd>'.$address.'</dd></lmap>';
		}
		//位置类型
		
		//支付类型
		if ($sub_type=="pay")
		{
			$data_content=str_replace(array("{nb}{pay}",'[',']'),"",$content);
			$data=explode("{|}",$data_content);
			
			
			$p_uid=$data[0];$p_uid_str="'".$p_uid."'";
			$p_tid=$data[1];$p_tid_str="'".$p_tid."'";
			$p_te=$data[2];	$p_te_str="'".$p_te."'";
			$p_money=$data[3];$p_money_str="'".$p_money."'";
			$p_number=$data[4];$p_number_str="'".$p_number."'";
			$p_remarks=$data[5];$p_remarks_str="'".$p_remarks."'";
			$p_nickname=$data[6];$p_nickname_str="'".$p_nickname."'";
			$p_amount_id=$data[7];$p_amount_id_str="'".$p_amount_id."'";
			
			if ($p_te==1){$te_text="哈土豆转账，转账给".$p_nickname;$te_img="/cssjs/img/ta.png";}
			if ($p_te==2){$te_text="哈土豆红包";$te_img="/cssjs/img/rp.png";}
			if ($p_te==3){$te_text="哈土豆群红包";$te_img="/cssjs/img/rp.png";}
			
			
			$db_config = include ROOT_PATH . '/config/database.php';
			
			
			/*获取当前登录用户*/
			$conn=mysqli_connect('127.0.0.1',$db_config['username'],$db_config['password'],$db_config['database']);
			
			$sql="select * from amount where id='$p_amount_id' order by id desc LIMIT 1";
			$result=mysqli_query($conn,$sql);
			$row=mysqli_fetch_array($result);
			$amount_uid=$row['uid'];
			$amount_state=$row['state'];
			
			$m_phpsessid=$_COOKIE[session_name()];
			$sql="select * from user_session where phpsessid='$m_phpsessid' order by id desc LIMIT 1";
			$result=mysqli_query($conn,$sql);
			$row=mysqli_fetch_array($result);
			$dl_uid=$row['uid'];
					
					
			if ($amount_state==9)
			{
				if ($amount_uid==$dl_uid){$cdn="right";}else{$cdn="left";}
				$pybg="background:-webkit-linear-gradient(to{@kg@}".$cdn.",#fedfc2,#fc9f3f);background:-o-linear-gradient(to{@kg@}".$cdn.",#fedfc2,#fc9f3f);background:-moz-linear-gradient(to{@kg@}".$cdn.",#fedfc2,#fc9f3f);background:linear-gradient(to{@kg@}".$cdn.",#fedfc2,#fc9f3f);";
				$te_text=$te_text."，已领取";
				$te_img=str_replace(".png","_y.png",$te_img);
			}
			else
			{
				$pybg="";
			}
			
			
			$ok_content='<lpay{@kg@}onclick="Pay_Operation('.$p_uid_str.','.$p_tid_str.','.$p_te_str.','.$p_money_str.','.$p_number_str.','.$p_remarks_str.','.$p_nickname_str.','.$p_amount_id_str.')"{@kg@}style="'.$pybg.'"><i><img{@kg@}src="'.$te_img.'"></i><dd>'.$p_remarks.'</dd></lpay><lpayfoot><span>'.$te_text.'</span></lpayfoot>';
		}
		//支付类型
		
		//公告类型
		if ($sub_type=="newnotice")
		{
			$data_content=str_replace(array("{nb}{newnotice}"),"",$content);
			$ok_content=$data_content;
		}
		//公告类型
		
		
		
		
		
		
		
		
		
		
		
        return $ok_content;;
    }
	 /*zedit（消息内容处理函数）*/





    /*zedit（识别消息类型）*/
    protected function Z_LMC($sub_type,$content)
    {

		if(strpos($content,'{position}') !== false){$sub_type="position";}
		if(strpos($content,'{pay}') !== false){$sub_type="pay";}
		if(strpos($content,'{newnotice}') !== false){$sub_type="newnotice";}
		
		
        return $sub_type;;
    }
	 /*zedit（识别消息类型）*/
	
	
	
}
