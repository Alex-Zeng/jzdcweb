<div id=<?php echo $module['module_name'];?> jzdc-module="<?php echo $module['module_name'];?>" align=left >
    <script>
    $(document).ready(function(){
		circle=get_param('circle');
		if(circle==''){circle=getCookie('circle');}
		$("#<?php echo $module['module_name'];?> a[circle='"+circle+"']").addClass('c');
		if(circle!=0){
			setCookie('circle',circle,30);
			$("#<?php echo $module['module_name'];?> .current_circle").html($("#<?php echo $module['module_name'];?> a[circle='"+circle+"']").html());
		}else{
			setCookie('circle',0,30);
			$("#<?php echo $module['module_name'];?> .current_circle").html('<?php echo self::$language['circle']?>');
		}
		
		$("#<?php echo $module['module_name'];?> .list a").click(function(){
			setCookie('circle',$(this).attr('circle'),30);	
			if($(this).parent().attr('class')=='circle_div'){
				
				url=window.location.href;
				url=replace_get(url,'circle',$(this).attr('circle'));
				window.location.href=url;
				return false;
				
			}
		});
		
		$("#<?php echo $module['module_name'];?> .list .circle_div").each(function(index, element) {
            if(index==3){
				$(this).attr('class','circle_div more');
				$(this).children('a').attr('href','./index.php?jzdc=index.circle');
				$(this).children('a').children('span').html('<?php echo self::$language['more']?>...');
			}
            if(index>3){$(this).css('display','none');}
			
        });
		
		
    });
    </script>
	<style>
	#<?php echo $module['module_name'];?>{ display:inline-block; width:20%; height:10rem; overflow:hidden;}
	#<?php echo $module['module_name'];?> img{ display:none;}
    #<?php echo $module['module_name'];?>_html{text-align:left; margin-bottom:2rem;} 
    #<?php echo $module['module_name'];?>_html .option_title{  font-size:2rem;margin-bottom:2rem;} 
    #<?php echo $module['module_name'];?>_html .list{ line-height:2rem; display:none; white-space:nowrap;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div{ display:inline-block; vertical-align:top; width:20%; overflow:hidden;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div a{ display:block; margin-bottom:1rem; padding-left:5px;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div a:hover{ opacity:0.8; border-bottom:3px <?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?> solid;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div a img{ width:40%;} 
    #<?php echo $module['module_name'];?>_html .list .circle_div a span{ display:block;} 
	#<?php echo $module['module_name'];?>_html .list .circle_div .c{ opacity:0.6; }
	#<?php echo $module['module_name'];?>_html .list .circle_div .sub_div{ display:none;}
	
	#<?php echo $module['module_name'];?>_html .current_circle_div{text-align:left; line-height:2rem; padding-top:15%;}
	#<?php echo $module['module_name'];?>_html .current_circle_div:hover .current_circle{ color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; }
	#<?php echo $module['module_name'];?>_html .current_circle_div:hover .list{ display:block;}
	#<?php echo $module['module_name'];?>_html .current_circle_div .c_label{ display:none;}
	#<?php echo $module['module_name'];?>_html .current_circle_div .current_circle{}
	#<?php echo $module['module_name'];?>_html .current_circle_div .current_circle:after{font: normal normal normal 1rem/1 FontAwesome;margin-left: 3px;content: "\f107";}
    </style>
    <div id="<?php echo $module['module_name'];?>_html">
    	<div class=current_circle_div>
        	<span class=c_label><?php echo self::$language['circle']?></span><a href=./index.php?jzdc=index.circle class=current_circle><?php echo self::$language['circle']?></a>
            <div class=list ><?php echo $module['list'];?></div>
        </div>
    	
    </div>
</div>
