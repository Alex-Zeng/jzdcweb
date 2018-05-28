<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
    <script>
    $(document).ready(function(){
		user_geo='<?php echo $module['my_latlng'];?>';
			$("#<?php echo $module['module_name'];?>  .navigate").each(function(index, element) {
				if('<?php echo $module['map_api']?>'=='baidumap'){
					$(this).attr('href','http://api.map.baidu.com/direction?origin=latlng:'+user_geo+'|name:我的位置&destination=latlng:'+$(this).attr('destination')+'|name:'+$(this).parent().parent().children('.name').html()+'&mode=driving&region='+$(this).parent().parent().children('.name').html()+'&output=html&src=yourCompanyName|yourAppName');
				}else{
					$(this).attr('href','https://www.google.com/maps/dir/'+user_geo+'/'+$(this).attr('destination'));
				}
                
            });	
		
		$("#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_1 a").hover(function(){
			$("#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_1 a").removeClass('show_sub');
			$("#<?php echo $module['module_name'];?> .circle_2 div").css('display','none');
			$("#<?php echo $module['module_name'];?> .circle_2 div[upid="+$(this).attr('circle')+"]").css('display','block');
		});
		$("#<?php echo $module['module_name'];?> .circle_2 div").hover(function(){
			$("#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_1 a[circle="+$(this).attr('upid')+"]").addClass('show_sub');	
		});
		
		$("#<?php echo $module['module_name'];?>_html .circle_filter .circle_list a[circle]").click(function(){
			url=window.location.href;
			url=replace_get(url,'circle',$(this).attr('circle'));
			window.location.href=url;
			//window.location.href='./index.php?jzdc=mall.shop_list&circle='+$(this).attr('circle');
			return false;
		});
		
		$("#<?php echo $module['module_name'];?>_html .tag_list a").click(function(){
			url=window.location.href;
			url=replace_get(url,'tag',$(this).attr('tag'));
			window.location.href=url;
			return false;
		});
		tag=get_param('tag');
		if(tag==''){tag=0;}
		$("#<?php echo $module['module_name'];?> .tag_list a[tag='"+tag+"']").addClass('c');

		circle=get_param('circle');
		if(circle==''){circle=getCookie('circle');}
		$("#<?php echo $module['module_name'];?>  .circle_filter .circle_list a[circle='"+circle+"']").addClass('c');
		
		if($("#<?php echo $module['module_name'];?> a[circle='"+circle+"']").parent().attr('upid')){
			$("#<?php echo $module['module_name'];?> a[circle='"+circle+"']").parent().css('display','block');
			$("#<?php echo $module['module_name'];?> a[circle='"+$("#<?php echo $module['module_name'];?> a[circle='"+circle+"']").parent().attr('upid')+"']").addClass('show_sub');
		}
		
		$("#<?php echo $module['module_name'];?> .data_state").each(function(index, element) {
            $(this).val($(this).attr('jzdc_value'));
        });
		
		
        var get_search=get_param('search');
        if(get_search.length<1){
            var state=get_param('state');
            if(state!=''){$("#state_filter").prop("value",state);}
        }
        
		var area_province=get_param('area_province');
		if(area_province!=''){$("#area_province").prop('value',area_province);}
		var area_city=get_param('area_city');
		if(area_city!=''){$("#area_city").prop('value',area_city);}
		var area_county=get_param('area_county');
		if(area_county!=''){$("#area_county").prop('value',area_county);}
		var area_twon=get_param('area_twon');
		if(area_twon!=''){$("#area_twon").prop('value',area_twon);}
		var area_village=get_param('area_village');
		if(area_village!=''){$("#area_village").prop('value',area_village);}
		var area_group=get_param('area_group');
		if(area_group!=''){$("#area_group").prop('value',area_group);}
		
		
		$(".load_js_span").each(function(index, element) {
            $(this).load($(this).attr('src'));
        });
    });
    
    function jzdc_table_filter(id){
		if($("#"+id).prop("value")!=-1){
			key=id.replace("_filter","");
			url=window.location.href;
			url=replace_get(url,key,$("#"+id).prop("value"));
			if(key!="search"){url=replace_get(url,"search","");}
			if(key=='area_province'){url=replace_get(url,"area_city","");url=replace_get(url,"area_county","");url=replace_get(url,"area_twon","");url=replace_get(url,"area_village","");url=replace_get(url,"area_group","");}
			if(key=='area_city'){url=replace_get(url,"area_county","");url=replace_get(url,"area_twon","");url=replace_get(url,"area_village","");url=replace_get(url,"area_group","");}
			if(key=='area_county'){url=replace_get(url,"area_twon","");url=replace_get(url,"area_village","");url=replace_get(url,"area_group","");}
			if(key=='area_twon'){url=replace_get(url,"area_village","");url=replace_get(url,"area_group","");}
			if(key=='area_village'){url=replace_get(url,"area_group","");}

			window.location.href=url;	
		}
    }
    </script>
    <style>
    #<?php echo $module['module_name'];?>{}	
	#<?php echo $module['module_name'];?>_html [jzdc-table] .filter{}
	#<?php echo $module['module_name'];?>_html [jzdc-table] .filter .m_label{ margin-left:100px; display:inline-block; vertical-align:top; margin-top:3px;}
	#<?php echo $module['module_name'];?>_html  #search_filter{width:300px;}
	.area_div{ float:right; padding-right:10px;}
	#<?php echo $module['module_name'];?>_html .shop_div{ padding-bottom:1rem;margin-top:1rem; height:10rem; overflow:hidden; white-space:nowrap; border-bottom:1px dashed #CCCCCC;}	
	#<?php echo $module['module_name'];?>_html .shop_div .info{ display:inline-block; vertical-align:top; width:35%; overflow:hidden; white-space:nowrap;}	
	#<?php echo $module['module_name'];?>_html .shop_div .info .icon{display:inline-block; vertical-align:top; width:35%; overflow:hidden; border:solid  #F5F5F5 1px; text-align:center; }
	#<?php echo $module['module_name'];?>_html .shop_div .info .icon:hover{ opacity:0.7;}
	#<?php echo $module['module_name'];?>_html .shop_div .info .icon img{ width:80%;border:none;}
	#<?php echo $module['module_name'];?>_html .shop_div .info .other{display:inline-block; vertical-align:top; padding-left:10px; width:60%; overflow:hidden; line-height:2rem;}
	#<?php echo $module['module_name'];?>_html .shop_div .info .other .name{ font-weight:bold; }
	#<?php echo $module['module_name'];?>_html .shop_div .info .other .sum{}
	#<?php echo $module['module_name'];?>_html .shop_div .info .other .m_label{ }
	#<?php echo $module['module_name'];?>_html .shop_div .info .other .area{width:100%; overflow:hidden;    text-overflow: ellipsis;}
	#<?php echo $module['module_name'];?>_html .shop_div .info .other .main_business{ white-space:nowrap; overflow:hidden; text-overflow: ellipsis; height:30px;width:100%;}
	#<?php echo $module['module_name'];?>_html .shop_div .goods{ display:inline-block; vertical-align:top; width:65%; overflow:hidden; white-space:nowrap;}	
	#<?php echo $module['module_name'];?>_html .shop_div .goods a{ display:inline-block; vertical-align:top; width:18%; text-align:center;  border:solid  #F5F5F5 1px; margin-right:2%;}	
	#<?php echo $module['module_name'];?>_html .shop_div .goods a:hover{ opacity:0.7;}
	#<?php echo $module['module_name'];?>_html .shop_div .goods a img{ width:90%;border:none;}	
	
	#<?php echo $module['module_name'];?>_html .tag_div{ margin-bottom:1rem; line-height:2rem;}
	#<?php echo $module['module_name'];?>_html .circle_filter{ line-height:2rem; }
	#<?php echo $module['module_name'];?>_html .c_label{ display:inline-block; vertical-align:top; width:5%; overflow:hidden; text-align:right;   padding-right:1%; white-space:nowrap; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .tag_list{display:inline-block; vertical-align:top; width:93%; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .tag_list a{ display:inline-block; vertical-align:top;  width:5%; text-align:center; margin-right:1%; white-space:nowrap; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .tag_list .c{background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;   }
	#<?php echo $module['module_name'];?>_html .tag_list a:hover{ background:<?php echo $_POST['jzdc_user_color_set']['nv_2_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_2_hover']['text']?>; }
	
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list{display:inline-block; vertical-align:top; width:93%; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_1{}
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_1 a{ display:inline-block; vertical-align:top;  width:5%; text-align:center; margin-right:1%; white-space:nowrap; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_1 .c{  background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; }
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_1 .show_sub{ border-bottom:2px <?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?> solid;}
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_1 a:hover{background:<?php echo $_POST['jzdc_user_color_set']['nv_2_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_2_hover']['text']?>;  }
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_2{}
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_2 { margin-top:0.5rem; height:2rem; line-height:2rem;}
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_2 div{ display:none; }
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_2 div a{ display:inline-block; vertical-align:top;  width:5%; margin-right:1%; text-align:center;  white-space:nowrap; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_2 div a:hover{background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; opacity:0.8;  }
	#<?php echo $module['module_name'];?>_html .circle_filter .circle_list .circle_2 div .c{ background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; }
	
	#<?php echo $module['module_name'];?>_html .shop_div .info .navigate:after{font: normal normal normal 14px/1 FontAwesome;margin-left: 5px; content: '\f041'; opacity:0.6;}
    </style>
	<div id="<?php echo $module['module_name'];?>_html"  jzdc-table=1>
    <div class=tag_div><div class=c_label><?php echo self::$language['tag']?>:</div><div class=tag_list><?php echo $module['tag'];?></div></div>	
    <div class=circle_filter>
    	<div class=c_label><?php echo self::$language['circle']?>:</div><div class=circle_list>
        	<div class=circle_1><?php echo $module['circle_list'];?></div>
        	<div class=circle_2><?php echo $module['circle_list_sub'];?></div>
        </div>
    </div>
    
    <div class="filter">
    	 <input type="text" name="search_filter" id="search_filter" value="<?php echo @$_GET['search']?>" placeholder="<?php echo self::$language['shop_name']?>/<?php echo self::$language['main_business']?>/<?php echo self::$language['phone']?>" />
        <a href="#" onclick="return e_search();" class="search"><span class=b_start> </span><span class=b_middle><?php echo self::$language['search']?></span><span class=b_end> </span></a> 
        
    </div>
    <?php echo $module['list']?>
    
    <?php echo $module['page']?>
    </div>
    </div>

</div>