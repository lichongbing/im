function m_msg(text){layer.open({content:text,skin: 'msg',time: 3});}

function m_loading(text) {layer.open({type: 2,content:text,time:5});}

function m_closeall(){layer.closeAll();}


/******************************@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*****公用函数*/

/*将URL加载为HTML*/	
function WinHtml(http,anim)
{
	m_msg('调试信息，请忽略，中断PO.003');
	$.ajax({url:http,success: function(result){
		result=result.replace(/\n/g,"");
		result=result.replace(/\r/g,"");
		result=result.replace(/\r\n/g,"");		
		//console.log(result);
		var result_json = eval("(" + result + ")");	
		var html_content=result_json.html;
		m_msg('调试信息，请忽略，中断PO.004');
		html_content=html_content.replace(/\\/g,"");
		m_msg('调试信息，请忽略，中断PO.005');
        if (result_json.code==0)
		{
			m_msg('调试信息，请忽略，中断PO.006');
			layer.open({
				type: 1
				,shadeClose:false
				,content: html_content
				,anim: anim
				,style: 'bottom:0; width: 100%;height: 100%; min-width: 320px; max-width: 750px; padding:0 0; border:none; background-color: #f2f2f2;'
			});
			//m_msg('调试信息，请忽略，中断PO.007');
		}
			//m_msg('调试信息，请忽略，中断PO.008');
    }});
}
/*将URL加载为HTML*/	

/*消息推送*/	
function m_send(to_toid,to_type,to_content,runjs,to_te)
{
	//alert(to_toid+'|'+to_type+'|'+to_content);
	var domain=window.location.host;
	$.ajax(
	{
		url : '//'+domain+'/api/message/send',
		method : 'POST',
		dataType : 'json',
		timeout : '100000',//千制
		data : {'sak':'99e36bdf9c656e89f03687825285fed1','to':to_toid,'type':to_type,'content':to_content,'to_te':to_te}, 
		success:function(ret){eval(runjs);},error:function(err){/*alert(JSON.stringify(err));*/}
	}); 
}
/*消息推送*/	


/*更新消息*/	
function m_get(get_id,get_type,get_limit)
{
	//alert(to_toid+'|'+to_type+'|'+to_content);
	var domain=window.location.host;
	$.ajax(
	{
		url : '//'+domain+'/api/message/get',
		method : 'POST',
		dataType : 'json',
		timeout : '100000',//千制
		data : {'sak':'99e36bdf9c656e89f03687825285fed1','id':get_id,'type':get_type,'limit':get_limit}, 
		success:function(ret){m_msg("加载成功");},error:function(err){/*alert(JSON.stringify(err));*/}
	}); 
}
/*更新消息*/	


/*随机数*/	
function random(lower, upper) {
	return Math.floor(Math.random() * (upper - lower+1)) + lower;
}  
/*随机数*/

/******************************@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*****公用函数*/



/******************************@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*****支付模块*/
function Pay_Open(te,toid)
{
	m_msg('调试信息，请忽略，中断PO.001');
	$("#MessagePanel").hide();
	var te;/*1转账，2普通红包，3群组红包*/
	if (te==1){te_title="好友转账";}
	if (te==2){te_title="普通红包";}
	if (te==3){te_title="群组红包";}
	
	var http="/my/html_pay.php?t=show&te="+te+"&toid="+toid;
	m_msg('调试信息，请忽略，中断PO.002');
	WinHtml(http,'up');
}


/*只能输入数字且保留小数点后两位*/
function Pay_Num(obj,dot) 
{ 
	if (dot==0)
	{
		obj.value = obj.value.replace(/[^\d]/g,"");
		obj.value = obj.value.replace(/^[0](\d+)*$/g,'$1');//只能数字，且第一位不能是0
	}
	if (dot==2)
	{
		//先把非数字的都替换掉，除了数字和. 
		obj.value = obj.value.replace(/[^\d.]/g,"");
		//必须保证第一个为数字而不是. 
		obj.value = obj.value.replace(/^\./g,""); 
		//保证只有出现一个.而没有多个. 
		obj.value = obj.value.replace(/\.{2,}/g,"."); 
		//保证.只出现一次，而不能出现两次以上 
		obj.value = obj.value.replace(".","$#$").replace(/\./g,"").replace("$#$","."); 
		//只能输入两个小数
		obj.value = obj.value.replace(/^(\-)*(\d+)\.(\d\d).*$/,'$1$2.$3'); 
	}

}
/*只能输入数字且保留小数点后两位*/

/*强制两位小数*/
function Pay_2Num(x){
      var f = Math.round(x*100)/100; 
      var s = f.toString(); 
      var rs = s.indexOf('.'); 
      if (rs < 0) { 
        rs = s.length; 
        s += '.'; 
      } 
      while (s.length <= rs + 2) { 
        s += '0'; 
      } 
      return s; 
}
/*强制两位小数*



/*实时显示金额*/
function Pay_Show()
{
	var nowmoney=Pay_2Num($("#money").val());
	var nownumber=$("#number").val();
	$("#nowmoney").html(nowmoney);
	if (nowmoney>0 && nowmoney<=200)
	{
			$("#moneysubmit").removeClass('submit_no');
			$("#moneysubmit").addClass('submit_ok');
			$("#msgbox").hide();$("#msgbox").html('');
			$('#moneysubmit').attr("disabled",false);
	}
	else
	{
		if (nowmoney>200)
		{
			$("#msgbox").html('单次红包金额不能超过200元');$("#msgbox").show();
		}
		$("#moneysubmit").removeClass('submit_ok');
		$("#moneysubmit").addClass('submit_no');	
		$('#moneysubmit').attr("disabled",true);
	}
	
	if (nownumber==0 & nownumber=='')
	{
		$("#msgbox").html('红包个数不能低于1个');$("#msgbox").show();
		$("#moneysubmit").removeClass('submit_ok');
		$("#moneysubmit").addClass('submit_no');	
		$('#moneysubmit').attr("disabled",true);
	}
	
	if (nownumber>200)
	{
		$("#msgbox").html('红包个数不能大于200个');$("#msgbox").show();
		$("#moneysubmit").removeClass('submit_ok');
		$("#moneysubmit").addClass('submit_no');	
		$('#moneysubmit').attr("disabled",true);
	}
	
	if (nownumber>(nowmoney*100))
	{
		$("#msgbox").html('单个红包金额不能低于0.01元');$("#msgbox").show();
		$("#moneysubmit").removeClass('submit_ok');
		$("#moneysubmit").addClass('submit_no');	
		$('#moneysubmit').attr("disabled",true);	
	}
}

function Pay_No()
{
	var nowmoney=Pay_2Num($("#money").val());
	if (nowmoney>0 && nowmoney<=20000)
	{
			$("#moneysubmit").removeClass('tasubmit_no');
			$("#moneysubmit").addClass('tasubmit_ok');
			$("#msgbox").hide();$("#msgbox").html('');
			$('#moneysubmit').attr("disabled",false);
	}
	else
	{
		if (nowmoney>20000)
		{
			$("#msgbox").html('单次转账金额不能超过20000元');
			$("#msgbox").show();
		}
		$("#moneysubmit").removeClass('tasubmit_ok');
		$("#moneysubmit").addClass('tasubmit_no');
		$('#moneysubmit').attr("disabled",true);
	}
}
/*实时显示金额*/


/*确认支付*/
function Pay_Confirm()
{
	te=$("#te").val();
	toid=$("#toid").val();
	money=Pay_2Num($("#money").val());
	number=$("#number").val();
	remarks=$("#remarks").val();
	//alert("类型："+te+"，对象："+toid+"钱数："+money+"数量："+number+"备注："+remarks);
	
	/*----------------------------AJAX-------------------------------------------*/
	var domain=window.location.host;//alert(domain);
	$.ajax(
	{
		url : '//'+domain+'/my/data_center.php?t=payconfirm',
		method : 'POST',
		dataType : 'json',
		timeout : '100000',//千制
		data : {'te':te,'te':te,'toid':toid,'money':money,'number':number,'remarks':remarks}, 
		success:function(ret)
		{
			var state = ret.state;
			if (state == 0) {m_closeall();m_msg("校验错误 请重新登录");return false;}
			if (state == 1) {m_msg("余额不足");return false;}
			if (state == 2) {m_msg("无接收人");return false;}
			if (state == 7) {m_closeall();m_msg("网络错误");return false;}
			
			if (state == 9) 
			{
				/*消息推送*/
				var to_id=ret.to_id;
				var to_type=ret.to_type;
				var to_content=ret.to_content;
				//alert(to_id+"-"+to_type+"-"+to_content);
				m_send(to_id,to_type,to_content,'m_closeall();m_msg("操作成功");',0);
				/*消息推送*/
				
			}
	}, 
	error:function(err){/*alert(JSON.stringify(err));*/}}); 
	/*----------------------------AJAX-------------------------------------------*/
}
/*确认支付*/


/*接收款项*/
function Pay_Operation(p_uid,p_tid,p_te,p_money,p_number,p_remarks,p_nickname,p_amount_id)
{
	if (p_te==1){te_title="接收转账";mn_title="接收";bc="#02a0ea";cc="#FFFFFF";}
	if (p_te==2){te_title="领取红包";mn_title="领取";bc="#f6604f";cc="#FFFFFF"}
	if (p_te==3){te_title="领取群红包";mn_title="领取";bc="#f6604f";cc="#FFFFFF"}
	
	/*----------------------------AJAX-------------------------------------------*/
		var domain=window.location.host;
		$.ajax(
		{
			url : '//'+domain+'/my/data_center.php?t=payoperation',
			method : 'POST',
			dataType : 'json',
			timeout : '100000',
			data : {'p_uid':p_uid,'p_tid':p_tid,'p_te':p_te,'p_money':p_money,'p_number':p_number,'p_remarks':p_remarks,'p_nickname':p_nickname,'p_amount_id':p_amount_id}, 
			success:function(ret)
			{
				var state = ret.state;
				//if (ret.html!=""){alert(ret.html);}
				if (state == 1) 
				{
					m_closeall();
					layer.open(
					{
						type: 1,
						content: '<div style="background:'+bc+'; color:'+cc+'; font-size:0.3rem; padding:0.5rem;border-radius:0.1rem 0.1rem 0 0;">您确定'+te_title+'吗？</div>',
						anim: 'up',
						style: 'position:fixed; left:10%; width: 80%; border:none;border-radius:0.25rem;',
						btn: ['<span style="color:#7b7b7b;">取消</span>', '<span style="color:'+bc+'">'+mn_title+'</span>'],
						yes: function(index){layer.close(index);if (ret.my==1){Pay_Display(p_uid,p_tid,p_te,p_money,p_number,p_remarks,p_nickname,p_amount_id);layer.close(index);}},
						no: function(index, layero){Pay_Collect(p_uid,p_tid,p_te,p_money,p_number,p_remarks,p_nickname,p_amount_id);layer.close(index);}
					});



					return false;
				}
				
				if (state == 9)
				{
					Pay_Display(p_uid,p_tid,p_te,p_money,p_number,p_remarks,p_nickname,p_amount_id);
					return false;
				}
				
			}, 
			error:function(err){/*m_closeall();m_msg("数据错误[PC0099]");return false;alert(JSON.stringify(err));*/}
		}); 
	/*----------------------------AJAX-------------------------------------------*/

}

function Pay_Collect(p_uid,p_tid,p_te,p_money,p_number,p_remarks,p_nickname,p_amount_id)
{
	/*----------------------------AJAX-------------------------------------------*/
		var domain=window.location.host;
		$.ajax(
		{
			url : '//'+domain+'/my/data_center.php?t=paycollect',
			method : 'POST',
			dataType : 'json',
			timeout : '100000',
			data : {'p_uid':p_uid,'p_tid':p_tid,'p_te':p_te,'p_money':p_money,'p_number':p_number,'p_remarks':p_remarks,'p_nickname':p_nickname,'p_amount_id':p_amount_id}, 
			success:function(ret)
			{

				
				var state = ret.state;
				if (state == 1) {m_closeall();m_msg("数据错误[PC001]");return false;}
				if (state == 2) {m_closeall();m_msg("数据错误[PC002]");return false;}
				if (state == 3) {m_closeall();m_msg("数据错误[PC003]");return false;}
				if (state == 4) {m_closeall();m_msg("数据错误[PC004]");return false;}
				if (state == 5) {m_closeall();m_msg("数据错误[PC005]");return false;}
				if (state == 10) {m_closeall();m_msg("数据错误[PC0010]");return false;}
				
				
				
				
				if (state == 6) {m_closeall();m_msg("已经接收过了！");}
				if (state == 7) {m_closeall();m_msg("领取接收成功");}
				if (state == 8) {m_closeall();m_msg("你领过这个红包了！");}
				if (state == 9) {m_closeall();m_msg("收取成功啦！");}

				
				if (p_te==1){m_type="friend";m_id=p_uid;}
				if (p_te==2){m_type="friend";m_id=p_uid;}
				if (p_te==3){m_type="group";m_id=p_tid;}

				m_message=ret.message;
				
				if (state == 7 || state == 9) 
				{
					m_send(m_id,m_type,"{nb}{newnotice}"+m_message+"",'',0);
				}

				
				Pay_Display(p_uid,p_tid,p_te,p_money,p_number,p_remarks,p_nickname,p_amount_id);
				
		}, 
		error:function(err){m_closeall();m_msg("数据错误[PC0099]");return false;/*alert(JSON.stringify(err));*/}}); 
	/*----------------------------AJAX-------------------------------------------*/

}
/*接收款项*/


/*显示款项信息*/
function Pay_Display(p_uid,p_tid,p_te,p_money,p_number,p_remarks,p_nickname,p_amount_id)
{
	var http="/my/html_collect.php?p_uid="+p_uid+"&p_tid="+p_tid+"&p_te="+p_te+"&p_money="+p_money+"&p_number="+p_number+"&p_remarks="+p_remarks+"&p_nickname="+p_nickname+"&p_amount_id="+p_amount_id;
	WinHtml(http,'up');
}
/*显示款项信息*/


/******************************@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@*****支付模块*/




//---------------------------------------------------------------安全密码模块
//调起安全密码
function Pay_SafePass(te,runjs)
{
	if (te==1){te_title="好友转账";}if (te==2){te_title="好友红包";}if (te==3){te_title="群组红包";}
	var syn=parseFloat($("#money").val());
	syn=syn.toFixed(2);
	if (syn>0)
	{
		$("#safe_amount_num").html("<span>￥</span>"+syn);
		$("#safe_amount_title").html(te_title);
		Pay_SafePassShow(runjs);
	}
}
//调起安全密码

function Pay_SafePassShow(runjs)
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
					
					m_loading('正在验证');
					/*----------------------------AJAX-------------------------------------------*/
						var domain=window.location.host;
						$.ajax(
						{
							url : '//'+domain+'/my/data_center.php?t=safepassword',
							method : 'POST',
							dataType : 'json',
							timeout : '100000',
							data : {'safepassword':data}, 
							success:function(ret)
							{
								var state = ret.state;
								if (state == 0) {m_closeall();m_msg("对不起，登录错误，请重新登录!");return false;}
								if (state == 1) {m_msg("交易密码错误");return false;}
								if (state == 2) {m_closeall();m_msg("还未设置交易密码，请在“我的”中设置！");return false;}
								if (state == 9) {eval(runjs);m_closeall();}
						}, 
						error:function(err){/*alert("对不起，数据错误，请重试或联系技术部门！0");alert(JSON.stringify(err));*/}}); 
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


//------------------地图模块------------------地图模块-----------------地图模块------------------地图模块--------------地图模块------------------------地图模块
function Map_Show(name,address,location)
{
	layer.open({
				type: 1
				,shadeClose:false
				,content: '<div id="selectheader" class="im_my_header"><i class="iconfont icon-fanhui1" onClick="layer.closeAll();"></i>'+name+'</div><iframe id="showmapbox" frameborder="0" scrolling="no" style="width: 100%;height:100%;" src="//uri.amap.com/marker?position='+location+'&name='+name+'&src=zhfh5&coordinate=gaode&callnative=1"></iframe><div id="allmapbox" style="display:none"></div>'
				,anim: 'up'
				,style: 'bottom:0; width: 100%;height: 100%; min-width: 320px; max-width: 750px; padding:0 0; border:none; background-color: #f2f2f2;'
	});

			$("#showmapbox").css("height",(document.body.clientHeight)-($("#selectheader").height()));
}


/*打开地图*/
function Map_Select(to_toid,to_type)
{
			layer.open({
				type: 1
				,shadeClose:false
				,content: '<div id="selectheader" class="im_my_header"><i class="iconfont icon-fanhui1" onClick="layer.closeAll();"></i>选择位置</div><iframe id="amapbox" frameborder="0" scrolling="no" style="width: 100%;height:100%;" src=""></iframe><div id="allmapbox" style="display:none"></div>'
				,anim: 'up'
				,style: 'bottom:0; width: 100%;height: 100%; min-width: 320px; max-width: 750px; padding:0 0; border:none; background-color: #f2f2f2;'
			});

			$("#amapbox").css("height",(document.body.clientHeight)-($("#selectheader").height()));
			
			Map_Start(to_toid,to_type);
}
/*打开地图*/

/*启动地图*/
function Map_Start(to_toid,to_type)
{
			$(function () 
			{
				var map = new AMap.Map('');
				map.plugin('AMap.Geolocation', function ()
				{
					var geolocation = new AMap.Geolocation({enableHighAccuracy: true,/*是否使用高精度定位，默认：true*/convert:true,showMarker:true,panToLocation:true,timeout: 5000});
					geolocation.getCurrentPosition();map.addControl(geolocation);AMap.event.addListener(geolocation, 'complete', onComplete);AMap.event.addListener(geolocation, 'error', onError);
					/*----------------------------AMAP定位失败*/
					function onError(data)
					{
						/*使用BMAP再次定位*/
						var map = new BMap.Map("allmapbox");
						var point = new BMap.Point(116.331398,39.897445);
						map.centerAndZoom(point,12);
					
						var geolocation = new BMap.Geolocation();
						geolocation.getCurrentPosition(function(r){
							if(this.getStatus() == BMAP_STATUS_SUCCESS){
								var mk = new BMap.Marker(r.point);
								map.addOverlay(mk);
								map.panTo(r.point);
								//alert('您的位置：'+r.point.lng+','+r.point.lat);
								$('#amapbox').attr('src','https://m.amap.com/picker/?center='+r.point.lng+','+r.point.lat+'&key=04a1f5a18228005316b84ceac82591bc');
							}
							else {
								/*二次失败后直接定位到北京*/
								//alert('failed'+this.getStatus());
								m_msg("精定位异常，请输入位置关键词！");
								$('#amapbox').attr('src','https://m.amap.com/picker/?center=116.331398,39.897445&key=04a1f5a18228005316b84ceac82591bc');
								/*二次失败后直接定位到北京*/
							}        
						},{enableHighAccuracy: true})
						/*使用BMAP再次定位*/
						
					}
					/*----------------------------AMAP定位失败*/
					
					/*----------------------------AMAP定位成功*/
					function onComplete(data) 
					{
						//alert(JSON.stringify(data));
						lng=data.position.lng;
						lat=data.position.lat;
						address=data.formattedAddress;
						$('#amapbox').attr('src','https://m.amap.com/picker/?center='+lng+','+lat+'&key=04a1f5a18228005316b84ceac82591bc');
					}
					/*----------------------------AMAP定位成功*/
					
					
				})
			});
			
			/*添加句柄事件*/
			(function(){
						var iframe = document.getElementById('amapbox').contentWindow;
						document.getElementById('amapbox').onload = function(){iframe.postMessage('hello','https://m.amap.com/picker/');};
						
							window.onmessage=function(e)//window.addEventListener("message", function(e)//之前，因重复绑定事件所以换为这种
							{
								m_loading('正在处理...');
								
								/*----------------------------AJAX消息推送-------------------------------------------*/
									var to_content='{nb}{position}['+e.data.name+'{|}'+e.data.address+'{|}'+e.data.location+']';
									var domain=window.location.host;
									//alert(to_toid+"----------"+to_type);
									m_send(to_toid,to_type,to_content,'m_closeall();',0)
								/*----------------------------AJAX消息推送-------------------------------------------*/
								
							};//之前}, false);
						
			}())
			/*添加句柄事件*/
					
			
				var mock = {
					log: function(result) {
						window.parent.setIFrameResult('log', result);
					}
				}
				console = mock;
				window.Konsole = {
					exec: function(code) {
						code = code || '';
						try {
							var result = window.eval(code);
							window.parent.setIFrameResult('result', result);
						} catch (e) {
							window.parent.setIFrameResult('error', e);
						}
					}
				}
			
				var mock = {
					log: function(result) {
						window.parent.setIFrameResult('log', result);
					}
				}
				console = mock;
				window.Konsole = {
					exec: function(code) {
						code = code || '';
						try {
							var result = window.eval(code);
							window.parent.setIFrameResult('result', result);
						} catch (e) {
							window.parent.setIFrameResult('error', e);
						}
					}
				}
}
/*启动地图*/
//------------------地图模块------------------地图模块-----------------地图模块------------------地图模块--------------地图模块------------------------地图模块






//------------------是关模块------------------是关模块-----------------是关模块------------------是关模块--------------是关模块------------------------是关模块



/*----------------------------设置客户端名称------------------------------*/
function set_winname()
{
	
	var domain=window.location.host;

	var url=window.location.href;
	var teid=url.match(/i=(\S*)/)[1];

	$.ajax(
	{
		url : '//'+domain+'/my/data_center.php?t=winname',
		method : 'POST',
		dataType : 'json',
		timeout : '100000',//千制
		data : {'teid':teid}, 
		success:function(ret)
		{
			if (ret.code=='0')
			{
				$("#tename").html(ret.tename);
				$(document).attr("title",ret.tename);
			}
		}, 
		error:function(err){alert(JSON.stringify(err));}
	}); 
}
set_winname();
/*----------------------------设置客户端名称------------------------------*/


/*----------------------------设置欢迎场景-------------------------*/
function set_welcome()
{
	var domain=window.location.host;
	var url=window.location.href;
	var teid=url.match(/i=(\S*)/)[1];

	
	$.ajax(
	{
		url : '//'+domain+'/my/data_center.php?t=welcome',
		method : 'POST',
		dataType : 'json',
		timeout : '100000',//千制
		data : {'teid':teid}, 
		success:function(ret)
		{
			if (ret.sm_code=='8')
			{
				/*----------------------------AJAX消息推送-------------------------------------------*/
					var to_toid=ret.sm_toid;var to_type=ret.sm_type;var to_content=ret.sm_content;
					var to_content=to_content.replace(new RegExp(/<br>/g),'\r\n');//处理换行等问题，在PHP端替换为<br>,在此处替换回来
					//alert(to_toid+'|'+to_type+'|'+to_content);
					m_send(to_toid,to_type,to_content,'',1);
					 
					//反向发消息
			}
		}, 
		error:function(err){/*alert(JSON.stringify(err));*/}
	}); 
	
}
setTimeout(function(){set_welcome();},random(1000,5000));
/*----------------------------设置欢迎场景-------------------------*/

//------------------是关模块------------------是关模块-----------------是关模块------------------是关模块--------------是关模块------------------------是关模块




//-------我的模块-----------
function Ns_safesetting()
{
	var http="/my/html_set.php?t=listshow";
	WinHtml(http,'up');
}

function Ns_safesetting_password()
{
	var http="/my/html_set.php?t=password";
	WinHtml(http,'up');
}

function Ns_safesetting_safepassword()
{
	var http="/my/html_set.php?t=safepassword";
	WinHtml(http,'up');
}




function Ns_save(field)
{
	var field,field_val,password,safepassword;
	
	var field_val=$("#"+field+"").val();
	
	if (field=="password"){var password=$("#password").val();if (password==""){m_msg('登录密码不能为空');return false;}}
	if (field=="safepassword"){var safepassword=$("#safepassword").val();if (safepassword==""){m_msg('交易密码不能为空');return false;}}
	
	$.ajax(
	{
		url : '/my/data_center.php?t=settingsave',
		method : 'POST',
		dataType : 'json',
		timeout : '100000',//千制
		data : {'password':password,'safepassword':safepassword}, 
		success:function(ret)
		{
			if (ret.code=='0')
			{
				if (ret.state=="0"){m_msg('安全验证失败，操作失败！');}
				if (ret.state=="9"){m_msg('操作已成功');}
				setTimeout("m_closeall()",500)
				
			}
		}, 
		error:function(err){alert(JSON.stringify(err));}
	}); 
	
}


function Ns_wallet(){
	
	var domain=window.location.host;
	var url=window.location.href;
	var teid=url.match(/i=(\S*)/)[1];
	
	
	var http="/my/html_wallet.php?t=show";
	WinHtml(http,'up');
}

function Ns_bill()
{
	var http="/my/html_bill.php?t=list";
	WinHtml(http,'up');
}

//-------我的模块-----------