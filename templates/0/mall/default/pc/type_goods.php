<div id=<?php echo $module['module_name'];?> jzdc-module="<?php echo $module['module_name'];?>" align=left >
	<script>
    $(document).ready(function(){
		$("#mall_type_all").css('width',$("#<?php echo $module['module_name'];?> .type_list_div").width());
		$("html,body").animate({scrollTop: $("#<?php echo $module['module_name'];?>").offset().top-50}, 1000);
		$("#<?php echo $module['module_name'];?>").css('height',$(window).height()*0.9);
		$("#<?php echo $module['module_name'];?>_html").css('height',$(window).height()*0.9);
		$("#<?php echo $module['module_name'];?> .type_list_div .no_scroll").css('height',$(window).height()*0.9+20);
		$("#<?php echo $module['module_name'];?> .goods_list_div .no_scroll").css('height',$(window).height()*0.9+20);
		$("#<?php echo $module['module_name'];?> .no_scroll").scroll(function(){
			$("#<?php echo $module['module_name'];?> .no_scroll .g_module").each(function(index, element) {
				if($(this).offset().top<460){
					$("#<?php echo $module['module_name'];?> .type_list_div a").removeClass('current');
					$("#<?php echo $module['module_name'];?> .type_list_div a[go='"+$(this).attr('id')+"']").addClass('current');	
				}
				
            });
		});
		
		$("#<?php echo $module['module_name'];?> .type_list_div a").each(function(index, element) {
			if(!$("#<?php echo $module['module_name'];?> .goods_list_div #"+$(this).attr('go')).attr('id')){
				$(this).css('opacity',0.2);
			}
        });
		
		$("#<?php echo $module['module_name'];?> .type_list_div a").click(function(){
			$("#<?php echo $module['module_name'];?> .type_list_div a").removeClass('current');
			$(this).addClass('current');
			var scroll_top=0;
			var id=$(this).attr('go');
			if(!$("#<?php echo $module['module_name'];?> .goods_list_div #"+$(this).attr('go')).attr('id')){
				return false;
			}
			$("#<?php echo $module['module_name'];?> .goods_list_div .no_scroll > div").each(function(index, element) {
				if($(this).attr('id')==id){return false;}
              	scroll_top+=$(this).height();
            });
			$("#<?php echo $module['module_name'];?> .goods_list_div .no_scroll").scrollTop(scroll_top-10);
			
			return false;	
		});
		
		
		
	
	$("#<?php echo $module['module_name'];?>_html .add_cart").click(function(){
		if($(this).attr('option_enable')==0 || $(this).attr('s_id')!='0'){
			if($(this).attr('class')==''){return true;}
			if($(this).attr('s_id')=='0'){
				id=$(this).attr('href').replace(/\.\/index\.php\?jzdc=mall\.goods&id=/,'');
				var price=$(this).parent().prev().children('.money_value').html();
			}else{
				id=$(this).attr('s_id');
				var price=$(this).attr('s_price');
			}
			add_cart(id,price);
			
			$(this).attr('class','');
			$(this).attr('user_color','');
			$(this).html('<a href="./index.php?jzdc=mall.my_cart" class=view><span class=success><?php echo self::$language['success'];?></span><?php echo self::$language['view']?></a>');
			return false;
		}
	});
	
	function add_cart(id,price){
		//alert(id+','+price);
		var old_cart=getCookie('mall_cart');
		if(old_cart!=''){
			old_cart=old_cart.replace(/%3A/g,':');
			old_cart=old_cart.replace(/%2C/g,',');
			//alert(old_cart);
temp=old_cart;
			old_cart=eval("("+temp+")");
			if(old_cart[id]){
				old_cart[id]['quantity']=parseInt(old_cart[id]['quantity'])+1;	
			}else{
				old_cart[id]=new Array();
				old_cart[id]['quantity']=1;		
				old_cart[id]['price']=price;		
			}
			old_cart[id]['time']=Date.parse(new Date());
			str='';
			for(v in old_cart){
				str+='"'+v+'":{"quantity":"'+old_cart[v]['quantity']+'","time":"'+old_cart[v]['time']+'","price":"'+old_cart[v]['price']+'"},';	
			}
			str=str.substring(0,str.length-1);
			str='{'+str+'}';
		}else{
			str='{"'+id+'":{"quantity":"1","time":"'+Date.parse(new Date())+'","price":"'+price+'"}}';
		}
		//alert(str);
		setCookie('mall_cart',str,30);
		try{update_cart_goods_sum();}catch(e){}
		$.get("./receive.php?target=mall::cart&act=update_cart");
	}
	
		
		
    });
	
    </script>
    <style>
	#<?php echo $module['module_name'];?>{overflow:hidden; margin-top:1rem; } 
	#<?php echo $module['module_name'];?> .goods_list_div{ text-align:right; display:inline-block; vertical-align:top; width:85%; height:100%;  white-space:normal; overflow:hidden; background:#fff;overflow: hidden;}
	#<?php echo $module['module_name'];?> .goods_list_div .g_module{ text-align:left;}
	#<?php echo $module['module_name'];?> .goods_list_div .g_module a{}

	#<?php echo $module['module_name'];?>_html  .goods{ display:inline-block; vertical-align:top; vertical-align:top;; width:16%; height:17.5rem; overflow:hidden; margin-left:1.5%;margin-right:1.5%;   white-space:nowrap; text-align:left; white-space:nowrap;}
	#<?php echo $module['module_name'];?>_html .right_border{ border-right:1px solid #e7e7e7;}
	#<?php echo $module['module_name'];?>_html  .goods:hover{background:#f9f9f9; }
	#<?php echo $module['module_name'];?>_html  .goods:hover .button_div{ display:block;}
	#<?php echo $module['module_name'];?>_html  .goods .goods_a{ display:block; text-align:center; margin:auto; padding-bottom:0px;}
	#<?php echo $module['module_name'];?>_html  .goods .goods_a img{  height:12rem; margin-top:1rem; border:none;}
	#<?php echo $module['module_name'];?>_html  .goods .goods_a .title{ display:block; text-align:left;  overflow:hidden; font-size:1rem;  white-space: nowrap;text-overflow: ellipsis; padding-left:0.5rem;}
	#<?php echo $module['module_name'];?>_html  .goods .price_span{ display:inline-block; vertical-align:top; text-align:left; height:28px; line-height:28px; overflow:hidden;  font-size:0.9rem;padding-left:10px;  width:60%; overflow:hidden; text-overflow: ellipsis;color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>;}
	#<?php echo $module['module_name'];?>_html  .goods .price_span .money_symbol{}
	#<?php echo $module['module_name'];?>_html  .goods .price_span .money_value{}
	
	#<?php echo $module['module_name'];?>_html .goods .button_div{ padding-right:0px; display:inline-block; vertical-align:top; float:right;}
	#<?php echo $module['module_name'];?>_html .add_cart{ margin-right:20px;}
	#<?php echo $module['module_name'];?>_html a .view{ font-size:0.8rem;}
	#<?php echo $module['module_name'];?>_html a .view:before{ display:none;}
	#<?php echo $module['module_name'];?>_html .add_cart:hover{ opacity:0.9;}
	#<?php echo $module['module_name'];?>_html .add_cart:before {font: normal normal normal 1.2rem/1 FontAwesome;margin-right: 5px;content: "\f07a";color:<?php echo $_POST['jzdc_user_color_set']['nv_2_hover']['background']?>;}
	
	
	#<?php echo $module['module_name'];?> .type_list_div{ display:inline-block; vertical-align:top; width:15%; height:100%; overflow:hidden;white-space:normal;background-color:rgba(247,247,247,1); color:#000;}
	#<?php echo $module['module_name'];?> .type_list_div a{ display:block; color:#FFF; padding:5px; text-align:center; line-height:2.5rem; border-bottom:1px dashed #ccc; color:#000; white-space:nowrap;}
	#<?php echo $module['module_name'];?> .type_list_div .type_0{ font-size:1.1rem; }
	#<?php echo $module['module_name'];?> .type_list_div .type_1{ font-size:1rem;}
	#<?php echo $module['module_name'];?> .type_list_div a:hover{ opacity:0.6;}
	#<?php echo $module['module_name'];?> .type_list_div  .current{ background:#fff; color:<?php echo $_POST['jzdc_user_color_set']['nv_2']['background']?>; }
	#<?php echo $module['module_name'];?> .type_list_div  .current:hover{ opacity:1;}
	#<?php echo $module['module_name'];?> .goods_list_div .no_scroll{ width:103%;overflow:scroll; height:400px; padding-top:10px; }
	#<?php echo $module['module_name'];?> .type_list_div .no_scroll{width:112%;overflow:scroll; height:400px; }
	.table_scroll{ height:80%; overflow:hidden;}
	.table_scroll .no_scroll{ height:120%; width:120%; overflow:scroll;}
	#<?php echo $module['module_name'];?> .g_module_name{ margin-left:1rem; margin-right:1rem; text-align:center; line-height:2rem; height:1.1rem; border-bottom:1px dashed #ccc; margin-bottom:1rem; margin-top:1rem;}
	#<?php echo $module['module_name'];?> .g_module_name span{ background:#fff; padding-left:5px; padding-right:5px; border-radius:3px; margin-bottom:5px;}
	

    </style>
    <div id="<?php echo $module['module_name'];?>_html"  jzdc-table=1>

	<div class=type_list_div><div class=no_scroll><?php echo $module['type_list'];?></div></div><div class=goods_list_div><div class=no_scroll><?php echo $module['goods_list'];?></div></div>
           
    </div>
</div>