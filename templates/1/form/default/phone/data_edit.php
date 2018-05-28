<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
    
    
    <script>
    $(document).ready(function(){
		$("#index_user_position a[href='index.php?jzdc=form.data_admin']").attr('href','index.php?jzdc=form.data_admin&table_id=<?php echo $_GET['table_id'];?>');
		$("#<?php echo $module['module_name'];?> select").each(function(index, element) {
            if($(this).attr('jzdc_value')){$(this).val($(this).attr('jzdc_value'));}
        });
		$("#<?php echo $module['module_name'];?> jzdc_radio").each(function(index, element) {
            if($(this).attr('jzdc_value')){
				$("#"+$(this).attr('id')+' input[value="'+$(this).attr('jzdc_value')+'"]').prop('checked',true);
			}
        });
		$("#<?php echo $module['module_name'];?> jzdc_checkbox").each(function(index, element) {
            if($(this).attr('jzdc_value')){
				temp=$(this).attr('jzdc_value').split('/');
				for(v in temp){
					$("#"+$(this).attr('id')+' input[value="'+temp[v]+'"]').prop('checked',true);	
				}
			}
        });
		
		$("#<?php echo $module['module_name'];?> .del_imgs").click(function(){
			$(this).prev('a').animate({opacity:0},"slow",function(){$(this).css('display','none');});
			$(this).css('display','none');
			//$(this).next('br').css('display','none');
			v=$("#<?php echo $module['module_name'];?> #"+$(this).attr('input_name')).attr('old_value');
			v=v.replace('|'+$(this).attr('file'),'');
			$("#<?php echo $module['module_name'];?> #"+$(this).attr('input_name')).attr('old_value',v);
			return false;
		});
		
		$("#<?php echo $module['module_name'];?> .del_files").click(function(){
			$(this).prev('a').animate({opacity:0},"slow",function(){$(this).css('display','none');});
			$(this).css('display','none');
			//$(this).next('br').css('display','none');
			v=$("#<?php echo $module['module_name'];?> #"+$(this).attr('input_name')).attr('old_value');
			v=v.replace('|'+$(this).attr('file'),'');
			$("#<?php echo $module['module_name'];?> #"+$(this).attr('input_name')).attr('old_value',v);
			return false;
		});
		
		$("jzdc_radio input").click(function(){
			$(this).parent('jzdc_radio').val($(this).val());
			
		});
		$("jzdc_checkbox input").click(function(){
			id=$(this).parent('jzdc_checkbox').attr('id');
			v='';
			$("#"+id+" input").each(function(index, element) {
                if($(this).prop('checked')){v+=$(this).val()+'/';}
            });
			$("#"+id).val(v);
		});
		
		$("#<?php echo $module['module_name'];?>_html input").keydown(function(event){
			if(event.keyCode==13 && event.target.tagName!='TEXTAREA'){return exe_jzdc_form_submit();}
		});
		$("#<?php echo $module['module_name'];?> #submit").click(function(){
			exe_jzdc_form_submit();
			return false;
		});        
        $("input[type='radio']").css('border','none');
        $("input[type='checkbox']").css('border','none');
		
		$("#close_button").click(function(){
			$("#fade_div").css('display','none');
			$("#set_jzdc_iframe_div").css('display','none');
			t=$("#jzdc_iframe").attr('src');
			t=t.split('?id=');
			t=t[1].split('&');
			t=t[0];
			temp=getCookie('map_'+t);
			if(temp){
				$("#<?php echo $module['module_name'];?> #"+t).val(getCookie('map_'+t).replace(/%2C/g,','));
			}
			return false;
		});
		$("#<?php echo $module['module_name'];?> input[jzdc_type='map']").focus(function(){
			set_iframe_position(700,900);
			//jzdc_alert(replace_file);
			$("#jzdc_iframe").attr('scrolling','auto');
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			$("#jzdc_iframe").attr('src','http://<?php echo $module['map_api'];?>.lansion.cn/get_point.php?id='+$(this).attr('id')+'&point='+$(this).val());
			return false;	
		});
		
    });
    
function exe_jzdc_form_submit(){
			err=false;
			//alert("dddd");
			try{if(editor){editor.sync();}}catch(e){}
			$("#<?php echo $module['module_name'];?> span").each(function(index, element) {
                if($(this).html()=='<?php echo self::$language['is_null'];?>' || $(this).html()=='<?php echo self::$language['not_match'];?>' || $(this).html()=='<?php echo self::$language['exist_same'];?>'){$(this).html('');}
            });
			$("#<?php echo $module['module_name'];?> span[class='state']").each(function(index, element) {
               $(this).html('');
            });
			var obj=new Object();
			var new_val = $(this).prop('value');
			$(".jzdc_input").each(function(index, element){
				if($(this).prop('value')==undefined){$(this).prop('value','');}
				if($(this).prop('value')=='' && $(this).attr('old_value')=="" && $(this).attr('jzdc_required')==='1'){
					alert( $(this).attr('id')+'='+obj[$(this).attr('id')]);
					$("#"+$(this).attr('id')+'_state').html('<span class=fail><?php echo self::$language['is_null'];?></span>');
					$(this).focus();
					err=true;
					return false;
				}
				if($(this).attr('check_reg')!=''  && $(this).attr('jzdc_required')==='1') {
					temp = $(this).attr('check_reg');
					try {
						if ($(this).prop('value').match(eval(temp)) == null) {
							$("#" + $(this).attr('id') + '_state').html('<span class=fail><?php echo self::$language['not_match'];?></span>');
							$(this).focus();
							err = true;
							return false;
						}

					}catch (err){
						//alert(err.message);
					}
				}

				if ($(this).prop('value')=='' && $(this).attr('old_value')!=undefined && $(this).attr('old_value')!="" ){
					obj[$(this).attr('id')]=$(this).attr('old_value');
				}else{
					obj[$(this).attr('id')]=$(this).prop('value');
				}
			    if($(this).attr('old_value')!=undefined){
					obj[$(this).attr('id')+'_old_value']=$(this).attr('old_value');

			    }
			   //alert( $(this).attr('id')+'='+obj[$(this).attr('id')]);
            });

			if(err){
				return false;
			}
			//return false;
			
			$("#<?php echo $module['module_name'];?> #submit").next('span').html('<span class=\'fa fa-spinner fa-spin\'></span>');
			$.post("<?php echo $module['action_url'];?>&act=update",obj,function(data){
				$("#<?php echo $module['module_name'];?> #submit").next('span').html('');

				try{v=eval("("+data+")");}catch(exception){alert(data);}
				
				if(v.state=='fail'){
					if(v.id){
						$("#"+v.id).focus();
						$("#"+v.id+'_state').html(v.info);	
					}else{
						$("#<?php echo $module['module_name'];?> #submit").next('span').html(v.info);
					}
					//alert(v.id + v.info);
				}else{
					$("#<?php echo $module['module_name'];?>_html").css('text-align','center');
					$("#<?php echo $module['module_name'];?>_html").html(v.info);
				}
	
			});	
			
}        
    </script>
    <script src="./plugin/datePicker/index.php"></script>

	<style>
		#<?php echo $module['module_name'];?>_html{}
		#<?php echo $module['module_name'];?> #gender input{ }
		#<?php echo $module['module_name'];?> #authcode_img{ height:25px;}
		#<?php echo $module['module_name'];?> div{ padding-left: 5px; line-height:3rem; white-space:nowrap;}
		#<?php echo $module['module_name'];?> .m_label{ display:inline-block; width:20%; text-align:right; overflow:hidden; padding-right:5px; }
		#<?php echo $module['module_name'];?> .m_label .required{ color:red; }
		#<?php echo $module['module_name'];?> .input_span{ display:inline-block; width:76%; overflow:hidden; white-space:normal;}
		#<?php echo $module['module_name'];?> legend{ }
		#<?php echo $module['module_name'];?> .form_title_div{border-bottom:1px solid #ccc; margin-bottom:10px; width:100%;}
		#<?php echo $module['module_name'];?> .form_title{font-size:20px;}
    </style>
    <div id="<?php echo $module['module_name'];?>_html">
    <?php echo $module['fields'];?>
	<?php echo $module['submit'];?>
    </div>
</div>
