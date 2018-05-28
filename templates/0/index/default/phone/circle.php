<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
    <script>
    $(document).ready(function(){
		$("#<?php echo $module['module_name'];?> .list a").click(function(){
			
			if($(this).next().attr('class')=='sub_div'){
				if($(this).next().css('opacity')==0){
					$(this).next().css('opacity',1);
					return false;
				}	
			}
			setCookie('circle',$(this).attr('circle'),30);	
		});
    });
    </script>
	<style>
    #<?php echo $module['module_name'];?>_html{text-align:center; margin-bottom:2rem;} 
    #<?php echo $module['module_name'];?>_html .option_title{  font-size:2rem; line-height:6rem;} 
    #<?php echo $module['module_name'];?>_html .list{ line-height:2rem;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div{ display:inline-block; vertical-align:top; width:33%; overflow:hidden;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div a{ display:block; margin-bottom:1rem;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div a:hover{ opacity:0.8; border-bottom:3px <?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?> solid;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div a img{ width:40%;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div a span{ display:block;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div .sub_div{ opacity:0;} 
	#<?php echo $module['module_name'];?>_html .list .circle_div:hover .sub_div{}
    #<?php echo $module['module_name'];?>_html .list .circle_div .sub_div a{ display:inline-block; width:50%;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div .sub_div a img{} 
    #<?php echo $module['module_name'];?>_html .list .circle_div .sub_div a span{} 
    </style>
    <div id="<?php echo $module['module_name'];?>_html">
    	<div class=option_title><?php echo self::$language['please_select_circle'];?></div>
    	<div class=list><?php echo $module['list'];?></div>
    </div>
</div>
