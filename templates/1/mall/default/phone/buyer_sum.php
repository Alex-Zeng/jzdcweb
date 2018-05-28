<div id=<?php echo $module['module_name'];?>  class="portlet light sum_card" jzdc-module="<?php echo $module['module_name'];?>" align=left >
    <script>
    $(document).ready(function(){
		$("#<?php echo $module['module_name'];?>").insertAfter("#index_user_html");
		$(".background_mode_1 a[href='./index.php?jzdc=mall.my_cart']").parent().css('display','none');
		$(".background_mode_1 a[href='./index.php?jzdc=mall.my_collect']").parent().css('display','none');
		$(".background_mode_1 a[href='./index.php?jzdc=mall.my_order']").parent().css('display','none');
		$("#<?php echo $module['module_name'];?> .diy_sum_card .s_top .value").each(function(index, element) {
            if($(this).html()!='0'){$(this).css('display','inline-block');}
        });
	});
    </script>
    

	<style>
	#<?php echo $module['module_name'];?>{ display:block !important; width:100%; height:auto; margin-top:1rem; margin:0px;}
	#<?php echo $module['module_name'];?>_html{ display:none;}
	#<?php echo $module['module_name'];?> .card_head{background-color: #4bb2dd; }
	#<?php echo $module['module_name'];?> .diy_sum_card{}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top{ white-space:nowrap; padding-top:1rem;}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top a{ white-space:nowrap; text-align:left; display: inline-block; vertical-align:top; width:20%; overflow:hidden; border-right:#d3d3d3 1px  dashed; padding:0px; margin:0px;}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top span{ display:inline-block; vertical-align:top;}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .name{ width:100%; text-align:center;}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .name:before{ opacity:0.2;  display:block;  font: normal normal normal 2rem/1 FontAwesome;content: "\f07a";}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .order_state_0 .name:before{content:"\f157";}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .diy_favorites .name:before{content:"\f08a";}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .order_state_1 .name:before{content:"\f15c";}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .order_state_2 .name:before{content:"\f0d1";}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .order_state_3 .name:before{conten:"\f157";}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .order_state_4 .name:before{content:"\f0d2";}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .order_state_6 .name:before{content:"\f0d1";}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .order_state_7 .name:before{content:"\f16c";}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_top .value{ margin-left:-1.8rem; width:1.5rem; height:1.5rem; line-height:1.5rem;   border-radius:50%; text-align:center; border:#FFF 1px solid; display:none;background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_bottom{ border-top:1px  dashed #d3d3d3; margin-top:0.6rem; padding-top:1rem;padding-left:2rem;}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_bottom .order_all{ display:block; line-height:3rem;  }
	#<?php echo $module['module_name'];?> .diy_sum_card .s_bottom .order_all .name{ display:inline-block;width:65%;}
	#<?php echo $module['module_name'];?> .diy_sum_card .s_bottom .order_all:before{    font: normal normal normal 1.3rem/1 FontAwesome;content: "\f0f6";   border-radius:3px; padding:0.3rem; text-align:center; display:inline-block; width:2rem; height:2rem; margin-right:0.5rem;background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;  }
	#<?php echo $module['module_name'];?> .diy_sum_card .s_bottom .order_all:after{ display:inline-block; vertical-align:top; float:right; padding-right:0.5rem;   font: normal normal normal 2rem/1 FontAwesome;content: "\f105"; opacity:0.3;}
	
    </style>
    <div class=diy_sum_card>
		<!--
    	<div class=s_top>
        	<a href=./index.php?jzdc=mall.my_cart& class=diy_car_icon>
            <span class=name><?php echo self::$language['cart']?></span><span class=value><?php echo $module['cart'];?></span>
            </a><a href=./index.php?jzdc=mall.my_collect& class=diy_favorites>
            <span class=name><?php echo self::$language['favorites']?></span><span class=value><?php echo $module['favorite'];?></span>
            </a><a href=./index.php?jzdc=mall.my_order&state=0 class=order_state_0>
            <span class=name><?php echo self::$language['order_state'][0]?></span><span class=value><?php echo $module['order_0'];?></span>
            </a><a href=./index.php?jzdc=mall.my_order&state=1 class=order_state_1>
            <span class=name><?php echo self::$language['order_state'][1]?></span><span class=value><?php echo $module['order_1'];?></span>
            </a><a href=./index.php?jzdc=mall.my_order&state=2 class=order_state_2>
            <span class=name><?php echo self::$language['order_state'][2]?></span><span class=value><?php echo $module['order_2'];?></span>
            </a>
        </div>
        -->

		<?php echo $module['s_top'];?>


        <div class=s_bottom>
        	<a href=./index.php?jzdc=mall.my_order& class=order_all><span class=name><?php echo self::$language['all'];?><?php echo self::$language['order'];?></span></a>
        </div>
        
    </div>
    
    <div id=<?php echo $module['module_name'];?>_html>
    	<a href="./index.php?jzdc=mall.my_cart" class=card_head>
        	<span class=big_num><?php echo $module['cart'];?></span>
            <span class=remark><?php echo self::$language['cart'];?></span>
        </a>
    	<div class=card_body>
        	<a href='./index.php?jzdc=mall.my_collect' class=item>
            	<span class=name><?php echo self::$language['favorites']?></span>
            	<span class=value><?php echo $module['favorite'];?></span>
            </a><a href='./index.php?jzdc=mall.my_visit' class=item>
            	<span class=name><?php echo self::$language['pages']['mall.my_visit']['name']?></span>
            	<span class=value><?php echo $module['visit'];?></span>
            </a><a href='./index.php?jzdc=mall.my_order&state=0' class=item>
            	<span class=name><?php echo self::$language['order_state'][0]?><?php echo self::$language['order']?></span>
            	<span class=value><?php echo $module['order_0'];?></span>
            </a><a href='./index.php?jzdc=mall.my_order&state=1' class=item>
            	<span class=name><?php echo self::$language['order_state'][1]?><?php echo self::$language['order']?></span>
            	<span class=value><?php echo $module['order_1'];?></span>
            </a><a href='./index.php?jzdc=mall.my_order&state=2' class=item>
            	<span class=name><?php echo self::$language['goods_to_be_received']?><?php echo self::$language['order']?></span>
            	<span class=value><?php echo $module['order_2'];?></span>
            </a><a href='./index.php?jzdc=mall.my_order&state=6' class=item>
            	<span class=name><?php echo self::$language['order_state'][6]?><?php echo self::$language['order']?></span>
            	<span class=value><?php echo $module['order_6'];?></span>
            </a>
        </div>
        <div class=other>
        	<a href="./index.php?jzdc=mall.receiver"><span class=value><?php echo $module['receiver']?></span></a>
        	<a href="./index.php?jzdc=mall.coupon_usable"><span class=value><?php echo $module['coupon_usable']?></span></a>
        </div>
        
        
    </div>
</div>