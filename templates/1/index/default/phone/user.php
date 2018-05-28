<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
    <script>
    $(document).ready(function(){
		if('<?php echo $module['weixin_auto_login']?>'=='0' || !isWeiXin()){
			$("#<?php echo $module['module_name'];?> .unlogin").css('display','inline-block');	
		}
    });	
	
	</script>
    
    <style>
	#index_foot,#index_device{ display:none;}
	.container{ }
    #<?php echo $module['module_name'];?>{background:<?php echo $_POST['jzdc_user_color_set']['container']['background']?>;}
    #<?php echo $module['module_name'];?>_html{ margin:0px; margin-bottom:0.5rem; background:#fff; padding-bottom:0.3rem; }
	
	#<?php echo $module['module_name'];?>_html .head_user_info{}
	#<?php echo $module['module_name'];?>_html .head_user_info .bg{ max-height:6rem; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .head_user_info .bg img{ width:100%;}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo{ margin-top:-4.5rem;}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .icon{ display:inline-block; vertical-align:top; width:35%; overflow:hidden; text-align:center;}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .icon img{ width:70%; border-radius:50%; border:#FFF 3px solid; }
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo{display:inline-block; vertical-align:top; width:65%; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_top{  height:4.5rem; color:#fff;}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_top a{color:#fff; }
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_top .m_nickname{ line-height:2.5rem; font-weight:bold; font-size:1.5rem; display:block; width:100%;overflow:hidden;    white-space: nowrap;    text-overflow: ellipsis;	}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_top .m_nickname:after{font: normal normal normal 1rem/1 FontAwesome;margin-left: 10px;content: "\f040";}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_top .group{}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_top .unlogin{ float:right;  padding-right:0.5rem; display:none;}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_top .unlogin:after{font: normal normal normal 18px/1 FontAwesome;margin-left: 5px;content: "\f08b";}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_bottom{ padding-top:0.5rem; padding-bottom:0.5rem;     white-space: nowrap;} 
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_bottom a{ display:inline-block; vertical-align:top; width:33%; text-align:center; border-right:1px solid #d3d3d3;overflow:hidden;} 
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_bottom a:last-child{ border:none;}
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_bottom a span{ display:block; } 
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_bottom a .value{font-size:1.3rem; } 
	#<?php echo $module['module_name'];?>_html .head_user_info .icon_uinfo .uinfo .u_bottom a .name{ } 
	
	
	#<?php echo $module['module_name'];?> .background_mode_1{list-style:none; line-height:2rem; padding-top:1rem; }
	#<?php echo $module['module_name'];?> .background_mode_1 ul{ list-style:none; background:#fff;}
	#<?php echo $module['module_name'];?> .background_mode_1 li{ list-style:none;background:#fff;  }
	#<?php echo $module['module_name'];?> .background_mode_1 li a{ background:#fff; color:#000; }
	#<?php echo $module['module_name'];?> .background_mode_1 li a .value_int{ display:inline-block; vertical-align:top;  border-radius:50%; text-indent:0px; width:1.5rem; text-align:center; height:1.5rem; line-height:1.5rem; margin-top:0.8rem; margin-right:0.8rem; float:right; font-size:1rem;background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;}
	#<?php echo $module['module_name'];?> .background_mode_1 li a .value_str{ display:inline-block; vertical-align:top;height:2rem; line-height:2rem; margin-top:1rem; margin-right:0.8rem; float:right; opacity:0.3; }
	#<?php echo $module['module_name'];?> .background_mode_1 > li:first-child{ display:none;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li{ line-height:2rem; font-size:1rem; list-style:none;   margin-bottom:1rem; border-bottom:1px solid #d7d7d7; white-space:nowrap;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li:before{ font: normal normal normal 2rem/1 FontAwesome;margin-right: 5px;content:"\f04c";  margin-left:0px; display:inline-block; vertical-align:top; width:3%; overflow:hidden;color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li > a{border-bottom:1px solid #d7d7d7; display:inline-block; vertical-align:top; width:97%; overflow:hidden; margin-left:-3%; text-indent:1rem;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li > a img{ display:none;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li > a i{ display:none;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul{list-style:none; line-height:4rem; font-size:1.2rem; }
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li{ white-space:nowrap;list-style:none;border-bottom:1px  dashed #d7d7d7; display:block;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li:after{font: normal normal normal 2rem/1 FontAwesome;margin-right: 5px;content:"\f105";  }
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li:first-child{ display:none; }
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li a{ text-indent:2rem; display:inline-block; vertical-align:top; width:95%;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li a img{ width:2rem;  border-radius:20%; margin-right:0.4rem; background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li >ul{ }
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li >ul > li{ white-space:nowrap;list-style:none;border-bottom:1px  dashed #d7d7d7; display:block;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li >ul > li:after{font: normal normal normal 2rem/1 FontAwesome;margin-right: 5px;content:"\f105";  }	
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li >ul > li:first-child{ display:block;}
	#<?php echo $module['module_name'];?> .background_mode_1 > li > ul >li >ul > li a{ text-indent:4rem; display:inline-block; vertical-align:top; width:95%;}

	.no_after:after{ display:none !important;}
	.no_after{ display:block !important;}
	.sum_card{ display:none;}
	.head_user_info{border-bottom:1px solid #f3f3f3;}
	.search_div_out{ text-align:center; margin:auto; width:85%;  padding-top:0.3rem;}
	#<?php echo $module['module_name'];?> .search_div input{ width:100%; border-radius:5px; background:#ededed;}
	#<?php echo $module['module_name'];?> .search_result_div{background-color:#fff; width:79%; overflow:hidden; text-align:left; border:1px solid #c5c5c5; borer-top:0px; display:none;}
	#<?php echo $module['module_name'];?> .search_result_div a{ display:block; line-height:2rem; padding-left:5px;}
	#<?php echo $module['module_name'];?> .search_result_div a:hover{ background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:#fff;}
	#<?php echo $module['module_name'];?> .search_result_div{}

    </style>
    
    <div id="<?php echo $module['module_name'];?>_html">
        <div class=head_user_info>
        	<div class=bg><img src="<?php echo $module['banner_path']?>" /></div>
            <div class=icon_uinfo>
            	<div class=icon><a href="./index.php?jzdc=index.personal_center"><img alt="" class="user_icon" src="./program/index/user_icon/default.png"></a></div><div class=uinfo>
                	<div class=u_top>
                    	<a href='<?php echo $module['edit_url'];?>' class=m_nickname><?php echo $module['user']['nickname']?></a>
                        <span class=group><?php echo $module['user']['group']?></span> <a class="unlogin" href="./receive.php?target=index::user&act=unlogin" class="icon-logout"><span><?php echo self::$language['unlogin_short'];?></span></a>
                    </div>
                    <div class=u_bottom>
<!--                    	<a href="./index.php?jzdc=index.money_log"><span class=value>--><?php //echo $module['user']['money']?><!--</span><span class=name>--><?php //echo self::$language['user_money']?><!--</span></a><a href="./index.php?jzdc=index.credits_log"><span class=value>--><?php //echo $module['user']['credits']?><!--</span><span class=name>--><?php //echo self::$language['credits']?><!--</span></a><a href="./index.php?jzdc=index.site_msg_addressee"><span class=value>--><?php //echo $module['msg'];?><!--</span><span class=name>--><?php //echo self::$language['site_msg']?><!--</span></a>-->
                    </div>
                </div>
            </div>
        </div>

<!--    <div class=search_div_out>
        <div class=search_div><input type="text" class=search placeholder="<?php echo self::$language['search']?>" /></div>
        <div class=search_result_div></div>
    </div>-->

        
    </div>
    
    
    <ul class=background_mode_1></ul>
    
    <script>
    $(document).ready(function(){
		html='';
		temp='(<?php echo $module['data'];?>)';
		if(temp!='()'){
			arr=eval(temp);
			index=1;
			page_size=8;
			page=1;
			html_sub='';
			for(i in arr){
				if(!arr[i]['name']){continue;}
				html_sub+='<a class="c_'+index+'" href="'+arr[i]['url']+'" target="'+arr[i]['open_target']+'"><span class=icon><img src="'+arr[i]['icon_path']+'" /></span><span class=name>'+arr[i]['name']+'</span></a>';
				if(index%page_size==0){
					html+='<div class="page_'+page+' swiper-slide">'+html_sub+'</div>';
					page++;
					html_sub='';
				}
				index++;
			}
			index--;
			if(index%page_size!=0){
				html+='<div class="page_'+page+' swiper-slide">'+html_sub+'</div>';
			}
			if(index<5){$('.swiper-container').css('height',$('.swiper-container').height()/2+20);}
			$("#<?php echo $module['module_name'];?> .swiper-wrapper").html(html);
		}
		
		
		$("#<?php echo $module['module_name'];?> .search").keyup(function(){
			key=$(this).val();
			str='';
			if(key==''){
				
			}else{
				$("#<?php echo $module['module_name'];?> .swiper-slide span").each(function(index, element) {
					if($(this).html().indexOf(key)!=-1){
						str+='<a href='+$(this).parent().attr('href')+'>'+$(this).html()+'</a>';
					}
				});
			}
			$("#<?php echo $module['module_name'];?> .search_result_div").html(str);
			if(str==''){
				$("#<?php echo $module['module_name'];?> .search_result_div").css('display','none');
			}else{
				$("#<?php echo $module['module_name'];?> .search_result_div").css('display','block');
			}
		});
		
		$("#<?php echo $module['module_name'];?> .search_result_div").css('width',$("#<?php echo $module['module_name'];?> .search").width()+7);
		
		$("#<?php echo $module['module_name'];?> .show_sum_div a").click(function(){
			if($(".sum_card").attr('class')=='portlet light sum_card'){
				$(".sum_card").addClass('show_sum_card');
			}else{
				$(".sum_card").removeClass('show_sum_card');
			}
			return false;
		});
		
    });
    </script>

	<style>
	.swiper-slide a{ padding-left:1rem; display:block; border-bottom:1px dashed #ccc; padding-bottom:5px;margin-bottom:5px;}
	.swiper-slide a:after{font: normal normal normal 1rem/1 FontAwesome;margin-left: 10px;content:"\f105"; float:right; padding-right:5px;}
	.swiper-slide a img{ width:30px; background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; margin-right:10px; border-radius:4px;}
	.diy_sum_card .s_bottom{padding-left:1rem !important;}
	

.show_sum_card{ display:block; width:100%;}	

.show_sum_div{ text-align:center; line-height:3rem;}
.show_sum_div a:after{font: normal normal normal 1rem/1 FontAwesome;margin-left: 10px;content:"\f103"; padding-left:1px;}
    </style>
    
    
    <div class="swiper-container">
      <div class="swiper-wrapper">
      	
      </div>
     
    </div>
    <div class="pagination"></div>
    
<!--	<div class=show_sum_div><a class=show_sum_a>--><?php //echo self::$language['view_sum_module']?><!--</a></div>-->
    
    
</div>

