<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left>
<script>
$(document).ready(function(){
	
	
});

</script>

<style>

#middle_malllayout_inner {background:none;}
#<?php echo $module['module_name'];?> { margin-top:1rem; white-space:normal; padding:0px; clear:both; }
#<?php echo $module['module_name'];?> a{ }
#<?php echo $module['module_name'];?> k{ }
#<?php echo $module['module_name'];?>_html{}

#<?php echo $module['module_name'];?>_html .list .goods_list{}

#<?php echo $module['module_name'];?>_html .module_title_div{  line-height:3rem; border-bottom:1px solid #E6E6E6;}
#<?php echo $module['module_name'];?>_html .module_title_div .name{padding-left:0.5rem; font-size:1.2rem;}
#<?php echo $module['module_name'];?>_html .module_title_div .name:hover{ color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?> !important;}
#<?php echo $module['module_name'];?>_html .module_title_div .more{ float:right; padding-right:0.5rem;}
#<?php echo $module['module_name'];?>_html .module_title_div .more:hover{ color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?> !important;}
#<?php echo $module['module_name'];?>_html .list .goods_list {}
#<?php echo $module['module_name'];?>_html .list .goods_list .goods{ display:inline-block; vertical-align:top; vertical-align:top;  width:48%; height:19rem;  overflow:hidden; border:1px solid #fff; margin:3px;	}
#<?php echo $module['module_name'];?>_html .list .goods_list .goods:hover{ border:1px solid <?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>;}
#<?php echo $module['module_name'];?>_html .list .goods_list .goods:hover .button_div{ display:block;}
#<?php echo $module['module_name'];?>_html .list .goods_list .goods .goods_a{ display:block; text-align:center;margin:auto; padding-bottom:15px;}
#<?php echo $module['module_name'];?>_html .list .goods_list .goods .goods_a img{  height:13rem; margin-top:1rem; border:none;}
#<?php echo $module['module_name'];?>_html .list .goods_list .goods .goods_a .title{ display:block; text-align:center; padding-left:5px;  height:2rem; line-height:2rem; font-size:1rem; overflow:hidden; margin:auto;white-space: nowrap;text-overflow: ellipsis;}
#<?php echo $module['module_name'];?>_html .list .goods_list .goods .goods_a .price_span{ display:block; text-align:left;height:2rem; line-height:2rem overflow:hidden;  font-size:1rem; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?> !important;}
#<?php echo $module['module_name'];?>_html .list .goods_list .goods .goods_a .price_span .money_symbol{}
#<?php echo $module['module_name'];?>_html .list .goods_list .goods .goods_a .price_span .money_value{}
</style>

	
<div id="<?php echo $module['module_name'];?>_html" class="module_div_bottom_margin">
    <div class=module_title_div><a href="./index.php?jzdc=mall.interest_goods_list" class=name><?php echo self::$language['functions']['mall.interest_goods']['description']?></a><a  href="./index.php?jzdc=mall.interest_goods_list" class=more><?php echo self::$language['more']?></a></div>
    <div class=content>
		<div class=list>
			<div class=goods_list><?php echo $module['list'];?></div>
        </div>
    </div>
</div>
</div>