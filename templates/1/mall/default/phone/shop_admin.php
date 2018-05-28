<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
    <script>
    $(document).ready(function(){
		$("#<?php echo $module['module_name'];?> .pay_agent").click(function(){
			if(confirm("<?php echo self::$language['opration_confirm']?>")){
			id=$(this).parent().parent().parent().parent().parent().attr('id').replace(/tr_/,'');
			$("#<?php echo $module['module_name'];?> #tr_"+id+" .pay_state").html('<span class=\'fa fa-spinner fa-spin\'></span>');
			$.post('<?php echo $module['action_url'];?>&act=pay_agent',{id:id}, function(data){
				//alert(data);
				try{v=eval("("+data+")");}catch(exception){alert(data);}
				$("#<?php echo $module['module_name'];?> #tr_"+id+" .pay_state").html(v.info);
			});
			}
			return false;
		});
		
		$("#<?php echo $module['module_name'];?> .data_state").each(function(index, element) {
            $(this).val($(this).attr('jzdc_value'));
        });
		
		$("#<?php echo $module['module_name'];?> .trusteeship").each(function(index, element) {
            $(this).val($(this).attr('jzdc_value'));
        });
		
		$("#close_button").click(function(){
			$("#fade_div").css('display','none');
			$("#set_jzdc_iframe_div").css('display','none');
			return false;
		});
		$(document).on('click','#<?php echo $module['module_name'];?> .edit',function(){
			set_iframe_position($(window).width()-100,$(window).height()-200);
			//jzdc_alert(replace_file);
			$("#jzdc_iframe").attr('scrolling','auto');
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			$("#jzdc_iframe").attr('src',$(this).attr('href'));
			return false;	
		});
		$(document).on('click','#<?php echo $module['module_name'];?> .set',function(){
			set_iframe_position($(window).width()-100,$(window).height()-200);
			//jzdc_alert(replace_file);
			$("#jzdc_iframe").attr('scrolling','auto');
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			$("#jzdc_iframe").attr('src',$(this).attr('href'));
			return false;	
		});
		
		$("#<?php echo $module['module_name'];?> .data_state").change(function(){
			$(this).next().css('display','block');	
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
	
	
    function update(id){
        var sequence=$("#<?php echo $module['module_name'];?> #sequence_"+id);	
        if(sequence.prop('value')==''){$("#<?php echo $module['module_name'];?> #state_"+id).html('<?php echo self::$language['please_input']?><?php echo self::$language['sequence']?>');sequence.focus();return false;}	
        if($("#<?php echo $module['module_name'];?> #tr_"+id+" .cause").css('display')=='block' && $("#<?php echo $module['module_name'];?> #tr_"+id+" .cause").val()==''){$("#<?php echo $module['module_name'];?> #state_"+id).html('<?php echo self::$language['please_input']?><?php echo self::$language['cause']?>');$("#<?php echo $module['module_name'];?> #tr_"+id+" .cause").focus();return false;}	
        
        $("#<?php echo $module['module_name'];?> #state_"+id).html('<span class=\'fa fa-spinner fa-spin\'></span>');
        $.get('<?php echo $module['action_url'];?>&act=update',{state:$("#<?php echo $module['module_name'];?> #data_state_"+id).prop('value'),sequence:sequence.prop('value'),trusteeship:$("#<?php echo $module['module_name'];?> #trusteeship_"+id).prop('value'),id:id,cause:$("#<?php echo $module['module_name'];?> #tr_"+id+" .cause").val()}, function(data){
           //alert(data);
            try{v=eval("("+data+")");}catch(exception){alert(data);}
            $("#<?php echo $module['module_name'];?> #state_"+id).html(v.info);
        });
        	
       return false; 
    }
    function del(id){
        if(confirm("<?php echo self::$language['delete_confirm']?>")){
			$("#<?php echo $module['module_name'];?> #state_"+id).html('<span class=\'fa fa-spinner fa-spin\'></span>');
            $.get('<?php echo $module['action_url'];?>&act=del',{id:id}, function(data){
				//alert(data);
                try{v=eval("("+data+")");}catch(exception){alert(data);}
				
                $("#<?php echo $module['module_name'];?> #state_"+id).html(v.info);
                if(v.state=='success'){
                $("#tr_"+id+" td").animate({opacity:0},"slow",function(){$("#tr_"+id).css('display','none');});
                }
            });
        }
        	
       return false; 
    }
	function update_tag(id,c){
		$("#fade_div").css('display','none');
		$("#set_jzdc_iframe_div").css('display','none');
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .tag").html(c);
	}
    </script>
    <style>
    #<?php echo $module['module_name'];?>{}
    #<?php echo $module['module_name'];?>_html{}
    #<?php echo $module['module_name'];?> .set{display:inline-block; vertical-align:top; width:30px; text-decoration:none;}
	#<?php echo $module['module_name'];?> .set:before{font: normal normal normal 15px/1 FontAwesome;content: "\f040";}
    #<?php echo $module['module_name'];?>_html .info{ width:250px; line-height:25px;}
    #<?php echo $module['module_name'];?>_html .info .icon{}
    #<?php echo $module['module_name'];?>_html .info .icon .icon_logo{ display:inline-block; vertical-align:top; width:50%; overflow:hidden;}
    #<?php echo $module['module_name'];?>_html .info .icon .icon_logo img{ width:90%;}
    #<?php echo $module['module_name'];?>_html .info .icon .icon_aside{ display:inline-block; vertical-align:top; width:50%; overflow:hidden;}
    #<?php echo $module['module_name'];?>_html .info .other{ }
    #<?php echo $module['module_name'];?>_html .info .other .area{ font-size:1rem; }
    #<?php echo $module['module_name'];?>_html .info .other .address{ font-size:1rem; }
	#<?php echo $module['module_name'];?>_html .sequence{ width:50px;}
	#<?php echo $module['module_name'];?>_html .cause{ width:80px; display:none;}
	#<?php echo $module['module_name'];?>_html .name{ font-weight:bold;}
	#<?php echo $module['module_name'];?>_html .main_business{   height:25px; overflow:hidden;}
	#<?php echo $module['module_name'];?>_html [jzdc-table] .filter{ line-height:40px;}
	#<?php echo $module['module_name'];?>_html #search_filter{ width:50%;}
	
	#<?php echo $module['module_name'];?>_html .operation_td a{ display:block;}
	#<?php echo $module['module_name'];?>_html .pay_agent{ font-size:13px;}
	.filter{ line-height:2rem;}
	.filter .submit{ float:right;}
    </style>
	<div id="<?php echo $module['module_name'];?>_html"  jzdc-table=1>
    
    <div class="portlet-title">
        <div class="caption"><?php echo $module['jzdc_table_name']?></div>
    </div>
                        
                        
                        
    <div class="m_row"><div class="half"><div class="dataTables_length"><select class="form-control" id="page_size" ><option value="10">10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option></select> <?php echo self::$language['per_page']?></m_label></div></div><div class="half"><div class="dataTables_filter"><m_label><?php echo self::$language['search']?>:<input type="search"  placeholder="<?php echo self::$language['shop_name']?>/<?php echo self::$language['username']?>/<?php echo self::$language['main_business']?>/<?php echo self::$language['phone']?>/<?php echo self::$language['email']?>"  class="form-control" ></m_label></div></div></div>
    
    
    <div class="filter"><?php echo self::$language['content_filter']?>:
        <?php echo $module['filter']?>
		<?php echo $module['batch_msg'];?>
        <?php echo $module['area'];?>
    </div>
    <div class=table_scroll><table class="table table-striped table-bordered table-hover dataTable no-footer"  role="grid" style="width:100%" cellpadding="0" cellspacing="0" >
         <thead>
            <tr>
                <td style="text-align:left;"><?php echo self::$language['info']?></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="goods|desc" class="sorting"  asc="goods|asc"><?php echo self::$language['goods']?></a></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="order|desc" class="sorting"  asc="order|asc"><?php echo self::$language['order']?></a></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="money|desc" class="sorting"  asc="money|asc"><?php echo self::$language['money']?></a></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="evaluation_0|desc" class="sorting"  asc="evaluation_0|asc"><?php echo self::$language['evaluation'][0]?></a></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="evaluation_1|desc" class="sorting"  asc="evaluation_1|asc"><?php echo self::$language['evaluation'][1]?></a></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="evaluation_2|desc" class="sorting"  asc="evaluation_2|asc"><?php echo self::$language['evaluation'][2]?></a></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="reg_time|desc" class="sorting"  asc="reg_time|asc"><?php echo self::$language['time']?></a></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="sequence|desc" class="sorting"  asc="sequence|asc"><?php echo self::$language['sequence']?></a></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="state|desc" class="sorting"  asc="state|asc"><?php echo self::$language['state']?></a></td>
                <td ><a href=# title="<?php echo self::$language['order']?>" desc="state|desc" class="sorting"  asc="state|asc"><?php echo self::$language['trusteeship']?></a></td>
                <td width="15%"  style="text-align:left;"><span class=operation_icon>&nbsp;</span><?php echo self::$language['operation']?></td>
            </tr>
        </thead>
        <tbody>
            <?php echo $module['list']?>
        </tbody>
    </table></div>
    <div class=m_row><?php echo $module['page']?></div>
    </div>
    </div>

</div>
