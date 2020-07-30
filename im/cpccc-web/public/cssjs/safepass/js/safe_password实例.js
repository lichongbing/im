//---------------------------------------------------------------安全密码模块
function safe_password(gotojs)
{
		//关闭浮动
		$(".close").click(function(){
			$(".ftc_wzsf").hide();
			$(".mm_box li").removeClass("mmdd");
			$(".mm_box li").attr("data","");
			i = 0;
		});
		//数字显示隐藏
		$(".xiaq_tb").click(function(){
			$(".numb_box").slideUp(500);
		});
		$(".mm_box").click(function(){
			$(".numb_box").slideDown(500);
		});


		$(".ftc_wzsf").fadeIn();
		var i = 0;
		$(".nub_ggg li .zf_num").click(function(){
				
			if(i<6){
				$(".mm_box li").eq(i).addClass("mmdd");
				$(".mm_box li").eq(i).attr("data",$(this).text());
				i++
				if (i==6) {
				  setTimeout(function(){
					var data = "";
						$(".mm_box li").each(function(){
						data += $(this).attr("data");
					});
					//alert("支付成功"+data);
					m_loading('正在验证');
					/*----------------------------AJAX-------------------------------------------*/
						var app_user_id = $api.getStorage('app_user_id');
						var app_user_username = $api.getStorage('app_user_username');
						var domain=window.location.host;
						api.ajax({
							url : '//'+domain+'/my/my.php?t=safepassword',
							method : 'post',
							dataType : 'json',
							timeout : '20',
							data : {
								values : {
									safepassword : data,
									app_user_id : app_user_id,
									app_user_username : app_user_username
								}
							}
						}, function(ret, err) {
							var state = ret.state;
							if (state == '0') {m_msg("对不起 登录错误 请重新登录");m_closeall();return false;}
							if (state == '1') {m_msg("交易密码错误");m_closeall();return false;}
							if (state == '9') {m_closeall();eval(gotojs);}
						});
					/*----------------------------AJAX-------------------------------------------*/
					
					
					
				  },100);
				};
			} 
		});
			
		$(".nub_ggg li .zf_del").click(function(){
			if(i>0){
				i--
				$(".mm_box li").eq(i).removeClass("mmdd");
				$(".mm_box li").eq(i).attr("data","");
			}
		});
		
		$(".nub_ggg li .zf_empty").click(function(){
			$(".mm_box li").removeClass("mmdd");
			$(".mm_box li").attr("data","");
			i = 0;
		});
	
}
//---------------------------------------------------------------安全密码模块