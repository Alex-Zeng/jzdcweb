<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
	<script>
	function reset_subtotal_td(shop_id){
		if($("#<?php echo $module['module_name'];?>_html #"+shop_id+" .preferential_way").val()==5){
			$("#<?php echo $module['module_name'];?>_html #"+shop_id+" .subtotal_td").each(function(index, element) {
				$(this).children('.v').html(parseFloat($(this).prev().prev().children('.favorable_price').html()) * parseFloat($(this).parent().attr('quantity')) );
				$(this).children('.v').html(Math.floor($(this).children('.v').html()*100)/100);
				$(this).parent().children('.unit_price_td').addClass('disable');
			});	
		}else{
			$("#<?php echo $module['module_name'];?>_html #"+shop_id+" .subtotal_td").each(function(index, element) {
				$(this).children('.v').html(parseFloat($(this).prev().prev().children('.price').html()) * parseFloat($(this).parent().attr('quantity')));
				$(this).children('.v').html(Math.floor($(this).children('.v').html()*100)/100);
			});	
		}
	}
	
    $(document).ready(function(){
		$(".jzdc_head .refresh").css('display','none');
		$(".jzdc_head").append('<a href=./index.php class=home><?php echo self::$language['home']?></a>');
		$(".jzdc_head").css('display','block');
		$(".page-container").css('padding-top',$(".jzdc_head").height());
		$("#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver #show_more a").click(function(){
			if($("#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content").css('overflow')=='hidden'){
				$("#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content").css('overflow','visible').css('height','auto');
				$(this).parent().attr('class','m_show');
			}else{
				$("#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content").css('overflow','hidden').css('height','17rem');
				$(this).parent().attr('class','m_hide');
			}
			
			return false;	
		});

		
		$("#close_button").click(function(){
			$("#fade_div").css('display','none');
			$("#set_jzdc_iframe_div").css('display','none');
			return false;
		});
		
		$("#<?php echo $module['module_name'];?> .receiver a[iframe='1']").click(function(){
			set_iframe_position($(window).width()-2,$(window).height()-20);
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			$("#jzdc_iframe").attr('src',$(this).attr('href'));
			$("#jzdc_iframe").attr('scrolling',"no");
			return false;	
		});
		
		$(document).on('click',"#<?php echo $module['module_name'];?> .receiver .content .option", function() {
			if($(this).attr('id').replace(/receiver_/,'')=='-1'){
				$("#<?php echo $module['module_name'];?> .delivery_time").css('display','none');
				$("#<?php echo $module['module_name'];?> .express_company_div").css('display','none');	
				$("#<?php echo $module['module_name'];?> .re_info").css('display','none');			
			}else{
				$("#<?php echo $module['module_name'];?> .delivery_time").css('display','block');
				$("#<?php echo $module['module_name'];?> .express_company_div").css('display','block');
				$("#<?php echo $module['module_name'];?> .re_info").css('display','block');
			}
			$("#<?php echo $module['module_name'];?> .receiver").attr('receiver_id',$(this).attr('id').replace(/receiver_/,''));
			$("#<?php echo $module['module_name'];?> .receiver").attr('top_id',$(this).attr('top_id'));
			$("#<?php echo $module['module_name'];?> .receiver .content .option").attr('class','option');
			$(this).addClass('selected');
			$("#<?php echo $module['module_name'];?> .shop_div").each(function(index, element) {
				id=$(this).attr('id');
				set_express_price(id);
				//alert(prices[1]['first_price']);
			});
			set_confirm_receiver();
			return false;	
		});
		$(document).on('click', "#<?php echo $module['module_name'];?>  .receiver .content .edit",function() {
			set_iframe_position($(window).width()-2,$(window).height()-20);
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			$("#jzdc_iframe").attr('src','./index.php?jzdc=mall.receiver_edit&buy_method=<?php echo @$_GET['buy_method'];?>&id='+$(this).parent().attr('id').replace(/receiver_/,''));
			$("#jzdc_iframe").attr('scrolling',"no");

            $.get('<?php echo $module['action_url'];?>&act=get_receiver&id='+getCookie('receiver_id'), function(data){
                $("#<?php echo $module['module_name'];?> .receiver .content #receiver_"+getCookie('receiver_id')).html(data).addClass('selected');
				$("#<?php echo $module['module_name'];?> .receiver").attr('receiver_',getCookie('receiver_id'));
            });
			set_confirm_receiver();
			return false;	
		});
		
		
		$("#<?php echo $module['module_name'];?>_html .preferential_code_span a").click(function(){
			id=$(this).parent().parent().parent().parent().parent().attr('id');
			if($("#<?php echo $module['module_name'];?>_html #"+id+" .preferential_code").val()==''){alert('<?php echo self::$language['please_input'];?>');$("#<?php echo $module['module_name'];?>_html #"+id+" .preferential_code").focus();return false;}
			
			$(this).next().html('<span class=\'fa fa-spinner fa-spin\'></span>');
			url='<?php echo $module['action_url'];?>&act=preferential_code&type='+$("#<?php echo $module['module_name'];?>_html #"+id+" .preferential_way").val()+"&code="+$("#<?php echo $module['module_name'];?>_html #"+id+" .preferential_code").val()+"&promotion_money="+$("#<?php echo $module['module_name'];?>_html #"+id).attr("promotion_money")+"&all_money="+$("#<?php echo $module['module_name'];?>_html #"+id).attr("all_money")+"&shop_id="+id.replace(/shop_/,'');
			$.get(url,function(data){
				//alert(data);
				try{v=eval("("+data+")");}catch(exception){alert(data);}
				
				$("#<?php echo $module['module_name'];?>_html #"+id+" .preferential_code_span a").next().html(v.info);
				if(v.state=='success'){
					$("#<?php echo $module['module_name'];?> #"+id+" .preferential_way_div .v").html(v.money);
					update_amount_payable();
				}else{
					$("#<?php echo $module['module_name'];?> #"+id+" .preferential_way_div .v").html('-0');
				}
				
			});
			
			
			return false;
		});
		
		$("#<?php echo $module['module_name'];?>_html .confirm_order_div .delivery_time .content a").click(function(){
			$("#<?php echo $module['module_name'];?>_html .confirm_order_div .delivery_time .content a").attr('class','');
			$(this).attr('class','selected');
			$("#<?php echo $module['module_name'];?>_html .confirm_order_div .delivery_time").attr('delivery_time',$(this).attr('value'));
			return false;	
		});
		$("#<?php echo $module['module_name'];?>_html .act_div .submit_now").click(function(){
			$(this).next().html('');

			if($("#<?php echo $module['module_name'];?> .confirm_order_div .receiver").attr("receiver_id")==0){
				alert('<?php echo self::$language['please_select'];?>'+$("#<?php echo $module['module_name'];?> .confirm_order_div .receiver .title").html());$(document).scrollTop($("#<?php echo $module['module_name'];?> .confirm_order_div .receiver").offset().top);
				return false;
			}
			//if($("#<?php echo $module['module_name'];?> .confirm_order_div .delivery_time").attr("delivery_time")==''){
			//	alert('<?php echo self::$language['please_select'];?>'+$("#<?php echo $module['module_name'];?> .confirm_order_div .delivery_time .title").html());$(document).scrollTop($("#<?php echo $module['module_name'];?> .confirm_order_div .delivery_time").offset().top);
			//	return false;
			//}

			
			receiver=$("#<?php echo $module['module_name'];?> .confirm_order_div .receiver").attr("receiver_id");
			delivery_time=$("#<?php echo $module['module_name'];?> .confirm_order_div .delivery_time").attr("delivery_time");
			authcode=$("#<?php echo $module['module_name'];?> .confirm_order_div .authcode").val();
			obj=new Object();
			$("#<?php echo $module['module_name'];?> .shop_div").each(function(index, element) {
                id=$(this).attr('id');
				shop_id=id.replace(/shop_/,'');
				obj[shop_id]=new Object();
				//obj[shop_id]['express']=$("#<?php echo $module['module_name'];?> #"+id+" .express").val();
				//obj[shop_id]['preferential_way']=$("#<?php echo $module['module_name'];?> #"+id+" .preferential_way").val();
				//obj[shop_id]['preferential_code']=$("#<?php echo $module['module_name'];?> #"+id+" .preferential_code").val();
				obj[shop_id]['remark']=$("#order_remark").val();
				obj[shop_id]['buyer_order_code']=$("#buyer_order_code").val();
				obj[shop_id]['buyer_comment'] = $("#order_content").val();
				//obj[shop_id]['red_coupon_id']=$("#<?php echo $module['module_name'];?> #"+id).attr('red_coupon_id');
				//obj[shop_id]['credits']=$("#<?php echo $module['module_name'];?> #"+id+" .use_shop_credits_div .m input").val();
				goods_ids='';
				$("#<?php echo $module['module_name'];?> #"+id+" .tr").each(function(index, element) {
                    goods_ids+=$(this).attr('goods_id')+'*'+$(this).attr('quantity')+',';
                });
				obj[shop_id]['goods_ids']=goods_ids;
            });
			$("#<?php echo $module['module_name'];?>_html .act_div .submit_now").css('display','none');
			$("#<?php echo $module['module_name'];?>_html .act_div .submit_now").next().html('<span class=\'fa fa-spinner fa-spin\'></span>  <?php echo self::$language['executing']?>');
			$.post('<?php echo $module['action_url'];?>&act=submit&receiver='+receiver+'&delivery_time='+delivery_time+'&authcode='+authcode+'&credits='+$("#<?php echo $module['module_name'];?> .use_web_credits_div .m input").val(),obj, function(data){
				//alert(data);
				try{v=eval("("+data+")");}catch(exception){alert(data);}
				$("#<?php echo $module['module_name'];?>_html .act_div .submit_now").next().html(v.info);
				if(v.state=='fail'){
					$("#<?php echo $module['module_name'];?>_html .act_div .submit_now").css('display','inline-block');	
					jzdc_alert(v.info);
				}
				if(v.info=='<span class=fail><?php echo self::$language['authcode'];?><?php echo self::$language['is_null'];?></span>'){
					$("#<?php echo $module['module_name'];?>_html .authcode_line").css('display','block');
					$("#<?php echo $module['module_name'];?>_html .authcode").focus();
				}
			});

			return false;
		});
	});
	

	function set_express_price(id){
		
		if($("#<?php echo $module['module_name'];?> #"+id+" .preferential_way").val()==2){
			if($("#<?php echo $module['module_name'];?> #"+id).attr('full_pre')=='<?php echo self::$language['free_shipping']?>'){
				$("#<?php echo $module['module_name'];?> #"+id+' .express').next('.v').html('<?php echo self::$language['free_shipping']?>');
				update_amount_payable();
				return false;	
			}	
		}
		area_top_id=$("#<?php echo $module['module_name'];?> .receiver").attr('top_id');
		area_ids=new Array();
		if(area_top_id){
			area_ids=$("#<?php echo $module['module_name'];?> .receiver #receiver_"+$("#<?php echo $module['module_name'];?> .receiver").attr('receiver_id')).attr('ids');
			if(area_ids){area_ids=area_ids.split(',');}else{area_ids=new Array();}
			
		}
		
		prices=$("#<?php echo $module['module_name'];?> #"+id+" .express option[value='"+$("#<?php echo $module['module_name'];?> #"+id+" .express").val()+"']").attr('prices');
		//alert(prices);
		prices=eval("("+prices+")");
		if($(this).attr('weight')==0){
			$("#<?php echo $module['module_name'];?> #"+id+' .express').next('.v').html('0');	
		}else{
			f_price=$("#<?php echo $module['module_name'];?> #"+id+" .express option[value='"+$("#<?php echo $module['module_name'];?> #"+id+" .express").val()+"']").attr('first_price');
			c_price=$("#<?php echo $module['module_name'];?> #"+id+" .express option[value='"+$("#<?php echo $module['module_name'];?> #"+id+" .express").val()+"']").attr('over_price');
			f_weight=$("#<?php echo $module['module_name'];?> #"+id+" .express option[value='"+$("#<?php echo $module['module_name'];?> #"+id+" .express").val()+"']").attr('first_weight');
			c_weight=$("#<?php echo $module['module_name'];?> #"+id+" .express option[value='"+$("#<?php echo $module['module_name'];?> #"+id+" .express").val()+"']").attr('over_weight');
			for(ai in area_ids){
				if(prices[area_ids[ai]]){
					f_price=prices[area_ids[ai]]['f_p'];
					c_price=prices[area_ids[ai]]['c_p'];
					break;
				}
			
			}
			if(parseFloat($("#<?php echo $module['module_name'];?> #"+id).attr('weight'))<=parseFloat(f_weight)){
				express_price=f_price;
			}else{
				//alert(($("#<?php echo $module['module_name'];?> #"+id).attr('weight')-f_weight)/c_weight+'*'+c_price);
				express_price=parseFloat(f_price)+parseFloat(((parseFloat(($("#<?php echo $module['module_name'];?> #"+id).attr('weight'))-f_weight)/c_weight)*c_price));
				express_price=express_price.toFixed(2);
			}
			if($("#<?php echo $module['module_name'];?> #"+id).attr('weight')==0){express_price=0;}
			$("#<?php echo $module['module_name'];?> #"+id+' .express').next('.v').html(express_price);
		}
		update_amount_payable();
	}
	
	function update_amount_payable(){
		sum_money=0;
		$("#<?php echo $module['module_name'];?> .shop_div").each(function(index, element) {
            id=$(this).attr('id');
			shop_money=parseFloat($(this).attr('all_money'));
			$("#<?php echo $module['module_name'];?> #"+id+" .other .v").each(function(index, element) {
				//alert($(this).html());
                if($.isNumeric($(this).html())){
					
					shop_money+=parseFloat($(this).html());
				}
            });
			if(shop_money<0){shop_money=0;}
			if(parseFloat($("#<?php echo $module['module_name'];?>_html #"+id+" .use_shop_credits_div .e span").html())){
				shop_money-=parseFloat($("#<?php echo $module['module_name'];?>_html #"+id+" .use_shop_credits_div .e span").html());
			}
			
			$("#<?php echo $module['module_name'];?> #"+id+" .shop_money .sum").html(shop_money.toFixed(2));
			sum_money+=shop_money;
        });
		
		if(parseFloat($("#<?php echo $module['module_name'];?>_html  .use_web_credits_div .e span").html())){
			sum_money-=parseFloat($("#<?php echo $module['module_name'];?>_html  .use_web_credits_div .e span").html());
		}
		$("#<?php echo $module['module_name'];?> .amount_payable_line .number").html(sum_money.toFixed(2));
		
	}
	
	function  close_select_window(){
		$("#fade_div").css('display','none');
		$("#set_jzdc_iframe_div").css('display','none');
		if( getCookie('receiver_id')!=''){
			$("<a href='#' class='option' id=receiver_"+getCookie('receiver_id')+"><span class='fa fa-spinner fa-spin'></span> <?php echo self::$language['loading']?></a>").insertBefore("#<?php echo $module['module_name'];?> .receiver .content .new");
			
            $.get('<?php echo $module['action_url'];?>&act=get_receiver&id='+getCookie('receiver_id'), function(data){
				$("#<?php echo $module['module_name'];?> .receiver .content .option").attr('class','option');
                $("#<?php echo $module['module_name'];?> .receiver .content #receiver_"+getCookie('receiver_id')).html(data).addClass('selected');
				$("#<?php echo $module['module_name'];?> .receiver").attr('receiver_id',getCookie('receiver_id'));
				$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')).attr('top_id',$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')+" .top_id").html());
				$("#<?php echo $module['module_name'];?> .receiver").attr('top_id',$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')).attr('top_id'));
				$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')).attr('ids',$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')+" .ids").html())
				set_confirm_receiver();
				
				$("#<?php echo $module['module_name'];?> .shop_div").each(function(index, element) {
					id=$(this).attr('id');
					set_express_price(id);
					//alert(prices[1]['first_price']);
				});
				
            });
			
			
		}
		
	}
	
	function  close_select_window2(){
		
		$("#fade_div").css('display','none');
		$("#set_jzdc_iframe_div").css('display','none');
		if( getCookie('receiver_id')!=''){
            $.get('<?php echo $module['action_url'];?>&act=get_receiver&id='+getCookie('receiver_id'), function(data){
				$("#<?php echo $module['module_name'];?> .receiver .content .option").attr('class','option');
                $("#<?php echo $module['module_name'];?> .receiver .content #receiver_"+getCookie('receiver_id')).html(data).addClass('selected');
				$("#<?php echo $module['module_name'];?> .receiver").attr('receiver_id',getCookie('receiver_id'));
				$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')).attr('top_id',$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')+" .top_id").html());
				$("#<?php echo $module['module_name'];?> .receiver").attr('top_id',$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')).attr('top_id'));
				$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')).attr('ids',$("#<?php echo $module['module_name'];?> #receiver_"+getCookie('receiver_id')+" .ids").html())
				set_confirm_receiver();
				
				$("#<?php echo $module['module_name'];?> .shop_div").each(function(index, element) {
					id=$(this).attr('id');
					set_express_price(id);
					//alert(prices[1]['first_price']);
				});
				
            });
		}
		
	}
	
	function set_confirm_receiver(){
		id=$("#<?php echo $module['module_name'];?> .receiver").attr('receiver_id');
		if(id>0){
			id='receiver_'+id;
			$("#<?php echo $module['module_name'];?> .c_detail").html($("#<?php echo $module['module_name'];?> #"+id+" .area_id").html()+' '+$("#<?php echo $module['module_name'];?> #"+id+" .detail").html());	
			$("#<?php echo $module['module_name'];?> .c_phone").html($("#<?php echo $module['module_name'];?> #"+id+" .name").html()+' '+$("#<?php echo $module['module_name'];?> #"+id+" .phone").html());	
		}	
	}
	
    </script>
    <style>
	#<?php echo $module['module_name'];?>{ background:<?php echo $_POST['jzdc_user_color_set']['container']['background']?>;} 
	#<?php echo $module['module_name'];?>_html{} 
	#<?php echo $module['module_name'];?>_html .buy_method_div{ display:<?php echo $module['buy_method_div_display'];?>;}
	#<?php echo $module['module_name'];?>_html .buy_method_title{ text-align:center;  line-height:50px; font-size:30px; font-weight:bold;} 
	#<?php echo $module['module_name'];?>_html .buy_method{} 
	#<?php echo $module['module_name'];?>_html .buy_method div{ display:inline-block; vertical-align:top; width:49%; overflow:hidden;} 
	#<?php echo $module['module_name'];?>_html .buy_method .left{ text-align:right;} 
	#<?php echo $module['module_name'];?>_html .buy_method .right{ text-align:left;} 
	#<?php echo $module['module_name'];?>_html .buy_method .left a:before{
	font: normal normal normal 100px/1 jzdc;
	
	display:block;
	content: "\f00d";} 
	#<?php echo $module['module_name'];?>_html .buy_method .right a:before{
	font: normal normal normal 100px/1 jzdc;
	
	display:block;
	content: "\f00e";} 
	#<?php echo $module['module_name'];?>_html .buy_method a:hover{}
	#<?php echo $module['module_name'];?>_html .buy_method div a{ display:inline-block; vertical-align:top; font-size:20px; margin:20px; text-align:center; } 
	#<?php echo $module['module_name'];?>_html .buy_method div a img{ border:none;} 
	
	#<?php echo $module['module_name'];?>_html .confirm_order_div{ display:<?php echo $module['confirm_order_div_display'];?>;}
	
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver{  margin-top:2rem; margin-bottom:2rem;box-shadow: 0px 1px 1px 1px rgba(0, 0, 0, 0.1); background:#fff; }
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content{ padding:20px;  overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .option{ display:block; line-height:1.5rem; width:100%; text-align:left; margin-bottom:1rem;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .option .receiver_head{ display:inline-block; vertical-align:top; width:45%; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .option .receiver_head .name{}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .option .receiver_head .tag{background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .option .receiver_head .tag span{  padding:5px;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .option .phone{ text-align:right; display:inline-block; vertical-align:top; width:50%; overflow:hidden;}
	
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .option .edit{ display:none;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .new{ line-height:3rem;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .new .add{ padding:0px; margin:0px; width:100%; height:100%; border:none;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .selected{  border: <?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?> solid 1px;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .selected:hover{ }
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .content .selected .edit{ display:block; line-height:25px; height:25px; overflow:hidden; font-size:1rem; width:60px; text-align:center;  border: #E4E4E4 solid 1px; }
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver #show_more{ text-align:center; line-height:2.1rem; height:2.1rem; margin-left:20px;margin-right:20px; border-top:#DFDFDF 1px solid; padding-bottom:4rem; margin-top:10px;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver #show_more a{ display:inline-block; vertical-align:top; width:100px; border:1px solid #DFDFDF; border-top:none; margin-top:-2px; background:#fff;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver #show_more a:hover{ opacity:0.8;}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .m_hide a{   }
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .m_hide a:before{font: normal normal normal 1rem/1 FontAwesome; content:"\f0d7"; font-size:2rem; }
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .m_show a{ }
	#<?php echo $module['module_name'];?>_html .confirm_order_div .receiver .m_show a:before{font: normal normal normal 1rem/1 FontAwesome; content:"\f0d8"; font-size:2rem; }
	#<?php echo $module['module_name'];?>_html .title{   font-size:1.2rem; line-height:3rem;  margin-top:0.5rem;	 border-bottom: #E8E8E8 solid 2px;  padding-bottom:5px; text-indent: 1em; background:#fff;color:#6C6C6C;}
	#<?php echo $module['module_name'];?>_html .title:before{margin-right:3px; font: normal normal normal 1.2rem/1 FontAwesome; content:"\f04c";}
	
	#<?php echo $module['module_name'];?>_html .content{ padding:10px; margin-bottom:1rem;}
	#<?php echo $module['module_name'];?>_html .content a{ padding:10px; margin-right:10px; display:inline-block; vertical-align:top;  text-align:center; border: #E4E4E4 solid 1px;}
	#<?php echo $module['module_name'];?>_html .content a:hover{ }
	#<?php echo $module['module_name'];?>_html .content .selected{  text-align:center; border: <?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?> solid 1px;}
	
	#<?php echo $module['module_name'];?>_html .confirm_order_div .express_company{}
	
	#<?php echo $module['module_name'];?>_html .confirm_order_div .delivery_time{ margin-top:2rem; margin-bottom:2rem;box-shadow: 0px 1px 1px 1px rgba(0, 0, 0, 0.1); background:#fff; white-space:nowrap; }
	#<?php echo $module['module_name'];?>_html .confirm_order_div .delivery_time a{ margin-bottom:1rem; font-size:0.9rem;}
	
	#<?php echo $module['module_name'];?>_html .confirm_order_div .invoice_info{}
	
	#<?php echo $module['module_name'];?>_html .confirm_order_div .goods_info{}
	#<?php echo $module['module_name'];?>_html .confirm_order_div .goods_info .g_content{ width:100%; margin:0px; padding:0px;}
	
	#<?php echo $module['module_name'];?> [jzdc-table] thead tr td{ background:none;   height:40px; line-height:40px; font-size:1rem; text-align:left;}    
	#<?php echo $module['module_name'];?> [jzdc-table] thead tr td .operation_icon{ background:none;} 
	#<?php echo $module['module_name'];?> [jzdc-table] tbody tr td{ border:none; border-bottom:1px solid #e7e7e7; background:none; text-align:left;}
	#<?php echo $module['module_name'];?> [jzdc-table]{ background:none; border:none;}    
	#<?php echo $module['module_name'];?> [jzdc-table] .even{ background:none; border:none;}    
	#<?php echo $module['module_name'];?> [jzdc-table] .odd{ background:none; border:none;}    
	
	
	
	#<?php echo $module['module_name'];?>_html .confirm_order_div .remark_info .remark{ width:50%; height:50px;}
	#<?php echo $module['module_name'];?>_html .count_info{  margin-top:2rem; margin-bottom:2rem;box-shadow: 0px 1px 1px 1px rgba(0, 0, 0, 0.1); padding:0.5rem; background:#fff;}
	#<?php echo $module['module_name'];?>_html .count_info div{ line-height:1.5rem; }
	#<?php echo $module['module_name'];?>_html .count_info div .m_label{ display:inline-block; vertical-align:top; vertical-align:top; text-align:right; font-weight:bold;}
	#<?php echo $module['module_name'];?>_html .count_info div .m_label .preferential_code{width:120px;}
	#<?php echo $module['module_name'];?>_html .preferential_code_span{ display:none;}
	#<?php echo $module['module_name'];?>_html .preferential_code_span a{   padding:3px;}
	#<?php echo $module['module_name'];?>_html .count_info div .value{ display:inline-block; vertical-align:top; vertical-align:top; text-align:right;color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; }
	#<?php echo $module['module_name'];?>_html .count_info .use_method_line{ display:<?php echo $module['use_method_display'];?>;}
	
	
	
	
	.jzdc_bottom,.cart_goods_sum,.jzdc_bottom_switch{ display:none !important; }
	#index_device{ margin-bottom:120px;}
	#index_navigation_html #navigation_swtich{ display:none; width:0px; height:0px;}
	
	#<?php echo $module['module_name'];?>_html .act_div{ white-space:nowrap;	 width:100%; height:3.57rem; line-height:3.57rem; position:fixed; bottom:0px; left:0px; box-shadow: 0px -2px 1px 1px rgba(0, 0, 0, 0.1); z-index:999999; text-align:left; background:#fff;}
	#<?php echo $module['module_name'];?>_html .act_div a{ text-align:center; display:inline-block; vertical-align:top; width:100%; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .act_div .submit_now{  }
	
	#set_jzdc_iframe_div{top:40%; left:420px; }
	#jzdc_iframe{ height:100px;width:500px; overflow:scroll;}
	#<?php echo $module['module_name'];?>_html .sales_promotion{    padding-left:3px;padding-right:3px; margin-left:3px; border-radius:7px;background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;}
	
	
	
	  
	  
	 
	#<?php echo $module['module_name'];?>_html .tr .subtotal_td{ color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; } 
	#<?php echo $module['module_name'];?> .tr .operation_td { line-height:30px; padding-top:<?php echo $module['operation_td_line_height'];?>px; text-align:left; } 
	
	  
	  
	 #<?php echo $module['module_name'];?> { }
	 #<?php echo $module['module_name'];?>_html .goods_td{ display:inline-block; vertical-align:top; width:60%; overflow:hidden; } 
	 #<?php echo $module['module_name'];?>_html .unit_price_td{display:inline-block;  width:15%;  overflow:hidden;white-space:nowrap; } 
	 #<?php echo $module['module_name'];?>_html .quantity_td{display:inline-block; vertical-align:top; width:10%;  overflow:hidden;white-space:nowrap; } 
	 #<?php echo $module['module_name'];?>_html .subtotal_td{display:inline-block; vertical-align:top; width:14%;  overflow:hidden; text-align:right;white-space:nowrap;} 
	 
	
	 
	 #<?php echo $module['module_name'];?>_html .thead{    margin-top:10px; padding:8px;background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;} 
	 #<?php echo $module['module_name'];?>_html .shop_div{  margin-bottom:2rem; box-shadow: 0px 1px 1px 1px rgba(0, 0, 0, 0.1); background:#fff;}
	 #<?php echo $module['module_name'];?>_html .shop_div .shop_info{  line-height:30px; white-space:nowrap;} 
	 #<?php echo $module['module_name'];?>_html .shop_div .shop_info input{ display:inline-block; vertical-align:top; } 
	 #<?php echo $module['module_name'];?>_html .shop_div .shop_info .name_l{ display:inline-block; vertical-align:top; padding-left:5px;  } 
	 #<?php echo $module['module_name'];?>_html .shop_div .shop_info .name_v{ display:inline-block; vertical-align:top; margin-right:30px; } 
	 #<?php echo $module['module_name'];?>_html .shop_div .shop_info .satisfaction_l{ display:inline-block; vertical-align:top; } 

	 #<?php echo $module['module_name'];?>_html .shop_div .shop_info .satisfaction_v{ display:inline-block; vertical-align:top; margin-right:30px; } 
 	 #<?php echo $module['module_name'];?>_html .shop_div .shop_goods{ border: #CCC solid 1px;} 
	 #<?php echo $module['module_name'];?>_html .shop_div .shop_info .fulfil_preferential{ display:inline-block; line-height:1.5rem;  padding-left:5px; padding-right:5px;   border-radius:8px;background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;}
 	 #<?php echo $module['module_name'];?>_html .shop_div .tr{ height:120px; line-height:120px; border-bottom:1px  solid  #EBEBEB;} 
 	 #<?php echo $module['module_name'];?>_html .shop_div .selected{ color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; } 
	 #<?php echo $module['module_name'];?>_html .shop_div .tr .goods_td{ display:inline-block; vertical-align:top; width:60%; overflow:hidden; white-space:nowrap;} 
	 #<?php echo $module['module_name'];?>_html .shop_div .tr .goods_td .goods_info{ display:block; } 
	 #<?php echo $module['module_name'];?>_html .shop_div .tr .goods_td .goods_info img{ display:inline-block; vertical-align:top; border:none; width:30%; margin:0px; padding:10px; border:none;} 
	 #<?php echo $module['module_name'];?>_html .shop_div .tr .goods_td .goods_info .title_model{ display:inline-block; vertical-align:top;width:67%; line-height:1.2rem;white-space: normal; overflow:hidden; } 
	 #<?php echo $module['module_name'];?>_html .shop_div .tr .goods_td .goods_info .title_model .g_title{ display:block; line-height:1.5rem; } 
	 #<?php echo $module['module_name'];?>_html .shop_div .tr .goods_td .goods_info .title_model .model{ font-style:oblique;} 
	 #<?php echo $module['module_name'];?>_html .shop_div .remark_other{line-height:40px; width:100%;overflow:hidden; }
	 #<?php echo $module['module_name'];?>_html .shop_div .remark_other .remark{ display:inline-block; vertical-align:top; line-height:1.5rem; width:40%; overflow:hidden;}
	 #<?php echo $module['module_name'];?>_html .shop_div .remark_other .remark input{ width:90%;}
	 #<?php echo $module['module_name'];?>_html .shop_div .remark_other .other{ display:inline-block; vertical-align:top; text-align:right; width:60%; overflow:hidden; }
	 #<?php echo $module['module_name'];?>_html .shop_div .remark_other .other .v{ padding-left:10px; padding-right:10px; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; }
	 #<?php echo $module['module_name'];?>_html .shop_div .remark_other .other .sum{ padding-left:10px; padding-right:10px;color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; }
	 #<?php echo $module['module_name'];?>_html .preferential_code_span{ display:none;}
	 #<?php echo $module['module_name'];?>_html  .preferential_code_span input{ width:100px;}
	 #<?php echo $module['module_name'];?>_html .amount_payable_line .number{ font-weight:bold; line-height:1.5rem;}
	 #<?php echo $module['module_name'];?>_html .top_id{ display:none;}
	 #<?php echo $module['module_name'];?>_html .unit_price_td .favorable_price{ display:none;}
	 #<?php echo $module['module_name'];?>_html .disable{ line-height:1.5rem;}
	 #<?php echo $module['module_name'];?>_html .disable .price{ text-decoration:line-through;}
	 #<?php echo $module['module_name'];?>_html .disable .favorable_price{ display:block; font-weight:bold;}
	 #<?php echo $module['module_name'];?>_html .m_label{ text-align:right; display:inline-block; padding-right:10px;}
	 .last_pay,.has_preferential{ }
	 
	 
	#<?php echo $module['module_name'];?> .credits{ display:block; padding-right:0.5rem;}
	#<?php echo $module['module_name'];?> .credits .s{display:inline-block; vertical-align:top; cursor:pointer;}
	#<?php echo $module['module_name'];?> .credits .m{width:5rem;display:inline-block; vertical-align:top;line-height:1.5rem; margin-top:0.5rem; display:none; }
	#<?php echo $module['module_name'];?> .credits .m span{  white-space:nowrap;}
	#<?php echo $module['module_name'];?> .credits .m input{ width:100%;}
	#<?php echo $module['module_name'];?> .credits .e{display:inline-block; vertical-align:top; display:none; }
	#<?php echo $module['module_name'];?> .reduced{ color:#ccc !important; }
	.return_last_page,.home{ color:<?php echo $_POST['jzdc_user_color_set']['nv_1']['text']?> !important;}
	#<?php echo $module['module_name'];?> .ids{ display:none;}
    </style>
    <div id="<?php echo $module['module_name'];?>_html">
    	<div class=buy_method_div>
            <div class=buy_method_title><?php echo self::$language['please_select_buy_method'];?></div>
            <div class=buy_method>
                <div class=left><a href="./index.php?jzdc=index.login"><?php echo self::$language['login_buy'];?></a></div>
                <div class=right><a href="http://<?php echo $module['current_url'];?>&buy_method=unlogin"><?php echo self::$language['direct_buy'];?></a></div>
            </div>
        </div>
        <div class=confirm_order_div>
        
        	<div class=receiver receiver_id="0">
            	<div class=title><?php echo self::$language['receiver'];?></div>
                <div class=content>
                	<?php echo $module['receiver'];?>
                	<div class="new">
                    	<a href="./index.php?jzdc=mall.receiver_add&buy_method=<?php echo @$_GET['buy_method'];?>" iframe="1" class=add><?php echo self::$language['use_new_address'];?></a>
                    </div>
                </div>
                <?php echo $module['show_more'];?>
            </div>
            <!--%
        	<div class=delivery_time delivery_time=''>
            	<div class=title><?php //echo self::$language['delivery_time'];?></div>
                <div class=content>
                	<?php //echo $module['delivery_time_list'];?>
                </div>
            </div>
            %-->

			<div class=delivery_time delivery_time=''>
				<div class=title><?php echo self::$language['buyer_remark'];?></div>
				<div class=content>
					<div class=line style="padding: 4px"><span class=m_label><?php echo self::$language['buyer_order_code'];?>：</span><span class=value><input type="text" id="buyer_order_code" /> <span class=state></span></span></div>
					<div class=line style="padding: 4px"><span class=m_label><?php echo self::$language['buyer_order_remark'];?>：</span><span class=value><input type="text" id="order_remark" /> <span class=state></span></span></div>
                    <div class="line"  style="padding: 4px"><span class=m_label>买家备注：</span><span class=value><textarea  maxlength="75" style="width: 250px;height: 80px"  id="order_content" ></textarea> <span class=state></span></span></div>
				</div>
			</div>

        	<div class=goods_info>
            	<div class=title><?php echo self::$language['goods_info'];?></div>
                <div class=g_content>
        <div class=thead>
            <div class=goods_td><?php echo self::$language['goods']?></div><div class=unit_price_td><?php echo self::$language['unit_price']?></div><div class=quantity_td><?php echo self::$language['quantity']?></div><div class=subtotal_td><?php echo self::$language['subtotal']?></div>
        </div>
    <?php echo $module['goods_html'];?>

                </div>

				<div class=authcode_line style="padding-bottom:20px;  display:<?php echo $module['authcode'];?>">
					<span class=m_label><?php echo self::$language['authcode'];?>: <input type="text" class="authcode" size="8" style="vertical-align:middle;" /> <span></span></span><span class="value" style="text-align:left;">
                	<a href="#" onclick="return change_authcode();" title="<?php echo self::$language['click_change_authcode']?>"><img id="authcode_img" src="./lib/authCode.class.php" style="vertical-align:middle; border:0px;" /></a>
                </span></div>
            </div>
            <div class="act_div">
            <!--<a href="./index.php?jzdc=mall.my_cart" class="go_cart"><?php echo self::$language['go_cart'];?></a> -->
            <a href="#" id=submit class="submit_now"><?php echo self::$language['submit_now'];?></a> <span></span>
            </div>
            
        </div>
        
        <!-- 模态框（Modal） -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" 
   aria-labelledby="myModalLabel" aria-hidden="true">
   <div class="modal-dialog">
      <div class="modal-content">
         <div class="modal-header">
            <button type="button" class="close" 
               data-dismiss="modal" aria-hidden="true">
                  &times;
            </button>
            <h4 class="modal-title" id="myModalLabel">
               <b><?php echo self::$language['goods_moved'];?></b>
            </h4>
         </div>
         <div class="modal-body">
             <div class=alone_buy><?php echo $module['alone_buy'];?></div>
         </div>
         <div class="modal-footer">
            <button type="button" class="btn btn-default" 
               data-dismiss="modal"><?php echo self::$language['close']?>
            </button>
            
         </div>
      </div>
</div></div>
        
       
    </div>
</div>