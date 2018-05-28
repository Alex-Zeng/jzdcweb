<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=center >
 
	<script>
	function get_share_img(){
		imgUrl='http://'+window.location.host+'/'+$("#<?php echo $module['module_name'];?> .icon_div img").attr('src');
		return  imgUrl;
	}
    $(document).ready(function(){
		if($("#<?php echo $module['module_name'];?> .head_banner").html().length<10 ){
			$("#<?php echo $module['module_name'];?> .icon_info").css('margin-top','1rem');	
		}
		$("#<?php echo $module['module_name'];?> .shop_search").keyup(function(event){
            if(event.keyCode==13 && $(this).val()!=''){
				window.location.href='./index.php?jzdc=mall.shop_goods_list&shop_id=<?php echo @$_GET['shop_id']?>&search='+$(this).val();
			}
		});
		$("#<?php echo $module['module_name'];?> .goods_type_switch").click(function(){
			if($("#<?php echo $module['module_name'];?> .types_div").css('display')=='none'){
				$("#<?php echo $module['module_name'];?> .types_div").css('display','block');
			}else{
				$("#<?php echo $module['module_name'];?> .types_div").css('display','none');
			}
			return false;	
		});
		
		$("#<?php echo $module['module_name'];?> .share_switch").click(function(){
			if(!isWeiXin()){alert('<?php echo self::$language['in_weixin_use']?>');return false;}
			$(".weixin_share").css('display','block');
			return false;	
		});
		
		type=get_param('type');
		$("#<?php echo $module['module_name'];?> .current_type_name").html($("#<?php echo $module['module_name'];?> .types_div a[d_id='"+type+"']").html());
    });
    </script>
	<style>
	#top_layout_out #top_layout_inner #layout_top { box-shadow: 0 3px 5px #ccc;  margin-bottom:5px;}
	#top_layout_out #top_layout_inner #layout_top #mall_cart { margin-top:37px;}
	
    #<?php echo $module['module_name'];?>{width:100%; margin-left:auto; margin-right:auto; padding-bottom:0.5rem; }
	#<?php echo $module['module_name'];?> .head_banner{ }
	#<?php echo $module['module_name'];?> .head_banner img{ width:100%; }
	#<?php echo $module['module_name'];?> .icon_info{ margin-top:-45px; font-size:12px;}
	#<?php echo $module['module_name'];?> .icon_info .icon_div{ display:inline-block; vertical-align:top; width:30%; overflow:hidden; text-align:center;	}
	#<?php echo $module['module_name'];?> .icon_info .icon_div img{ padding:0.3rem;  width:80%; border:1px #CCCCCC solid; border-radius:0.5rem; background:#fff;}
	#<?php echo $module['module_name'];?> .icon_info .info{display:inline-block; vertical-align:bottom; width:70%; overflow:hidden;}
	#<?php echo $module['module_name'];?> .icon_info .info a{display:inline-block; vertical-align:top; width:25%; overflow:hidden;}
	#<?php echo $module['module_name'];?> .address{ line-height:2rem; border-top:#999 1px solid; margin-top:1rem; text-align:left; padding:10px;}
	#<?php echo $module['module_name'];?> .shop_search_div{ margin-top:1rem; width:96%;padding-top:0.2rem; line-height:2.5rem; height:2.5rem; border-radius:0.5rem;  border:none;background-color: #ccc;color: #a3a3a3;}
	#<?php echo $module['module_name'];?> .shop_search_div .shop_search{ width:85%; line-height:2rem; height:2rem; padding-left:5px; border-radius:0.5rem;  border:none; outline:none;background-color: #ccc;}
	#<?php echo $module['module_name'];?> .shop_search_div:before{ font:normal normal normal 1.5rem/1 FontAwesome;content:"\f002";}
	#<?php echo $module['module_name'];?> .shop_search_div:after{font:normal normal normal 1.5rem/1 FontAwesome;content:"\f130";}
	#<?php echo $module['module_name'];?> .goods_type:before{ font:normal normal normal 1.5rem/1 FontAwesome;content:"\f009"; opacity:0.4;}
	#<?php echo $module['module_name'];?> .contact:before{ font:normal normal normal 1.5rem/1 FontAwesome;content:"\f095"; opacity:0.4;}
	#<?php echo $module['module_name'];?> .share:before{ font:normal normal normal 1.5rem/1 FontAwesome;content:"\f1e0"; opacity:0.4;}
	#<?php echo $module['module_name'];?> .goods_sum{ font-size:1.2rem;}
	
	#<?php echo $module['module_name'];?> .types_div{ line-height:2rem; padding:1rem; display:none;}
	#<?php echo $module['module_name'];?> .types_div a{ display:block; text-align:left; border-bottom: 1px dashed #CCCCCC;}
	#<?php echo $module['module_name'];?> .types_div .t_1{}
	#<?php echo $module['module_name'];?> .types_div .t_2{ padding-left:2rem;}
	#<?php echo $module['module_name'];?> .types_div .t_3{padding-left:4rem;}
	#<?php echo $module['module_name'];?> .share_list{ display:none; text-align: right;}
    </style>
    <div id="<?php echo $module['module_name'];?>_html">
		<div class=head_banner><?php echo $module['module_content'];?></div>
        <div class=icon_info>
        	<div class=icon_div><img src=./program/mall/shop_icon/<?php echo $module['shop_id']?>.png /></div><div class=info>
            	<a href=./index.php?jzdc=mall.shop_goods_list&shop_id=<?php echo $module['shop_id']?>><div  class=goods_sum><?php echo $module['goods']?></div><div><?php echo self::$language['shop_whole_goods']?></div></a><a href=# class=goods_type_switch><div class=goods_type></div><div class=current_type_name><?php echo self::$language['all_type']?></div></a><?php echo $module['contact']?><a href=# class=share_switch><div class=share></div><div><?php echo self::$language['share']?></div></a>
            </div>
        </div>
        <div class=types_div><?php echo $module['type_list'];?></div>
       
        <div class=shop_search_div><input type=text class=shop_search value="<?php echo @$_GET['search']?>" placeholder="<?php echo $_POST['shop_name']?> <?php echo self::$language['search']?>" /> </div>
    </div>

</div>