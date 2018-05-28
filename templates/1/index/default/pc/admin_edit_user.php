<div id=<?php echo $module['module_name'];?>  class="portlet" jzdc-module="<?php echo $module['module_name'];?>" align=left >
<script src="./plugin/datePicker/index.php"></script>
	<script>
    $(document).ready(function(){
		enter_to_tab();
		
				
        $("#<?php echo $module['module_name'];?>_html .up_img").each(function(i,e){
            $(e).error(function(){$(e).attr("src","./<?php echo get_template_dir(__FILE__);?>img/defualt.png");});
            //jzdc_alert(this.fileSize);
            if(this.fileSize<=0){this.src="./<?php echo get_template_dir(__FILE__);?>img/defualt.png";}
        });
		
        $('#icon_ele').insertBefore($('#icon_state'));
        $('#license_photo_front_ele').insertBefore($('#license_photo_front_state'));
        $('#license_photo_reverse_ele').insertBefore($('#license_photo_reverse_state'));
        
        $("#icon").change(function(){jzdc_alert('hidden');});
        
        
        $("input[type='text']").blur(function(){
            //jzdc_alert(this.id);
            	
            json="{'"+this.id+"':'"+replace_quot(this.value)+"'}";
            try{json=eval("("+json+")");}catch(exception){alert(json);}
            $("#"+this.id+"_state").html("<span class='fa fa-spinner fa-spin'></span>");
            $("#"+this.id+"_state").load('<?php echo $module['action_url'];?>&update='+this.id+"&id=<?php echo $_GET['id']?>",json,function(){
                if($(this).html().length>10){
                    try{v=eval("("+$(this).html()+")");}catch(exception){alert($(this).html());}


                    $(this).html(v.info);
                    if(v.state=='fail'){$(this).html('');}else{}
                }
            });
        });

        $("#psw_submit").click(function(){
            //jzdc_alert(this.id);
            psw = $("#password").val();
            if(psw.length == 0|| psw < 0){
                alert("请输入新密码");
                return;
            }

            json="{'password':'"+replace_quot(psw)+"'}";
            //alert(json);
            try{json=eval("("+json+")");}catch(exception){alert(json);}
            $("#password_state").html("<span class='fa fa-spinner fa-spin'></span>");
            $("#password_state").load('<?php echo $module['action_url'];?>&update=password&id=<?php echo $_GET['id']?>',json,function(){
            if($(this).html().length>10){
                try{v=eval("("+$(this).html()+")");}catch(exception){alert($(this).html());}
                $(this).html(v.info);
                if(v.state=='fail'){$(this).html('失败');}else{}
            }
            });
        });
        
        $("#edit_user_form tr").css('display','none');
        $("#update_password tr").css('display','block');
        $("#update_transaction_password tr").css('display','block');
        
        field=get_param('field').split("|");
        for(var v in field){
            
            $("#tr_"+field[v]).css('display','block');
        }
        //jzdc_alert(field);
        if(field==''){
            $("#edit_user_form tr").css('display','block');
        }else{
            document.title='<?php echo self::$language['require_info']?>';	
        }
        if(field!='transaction_password'){$("#tr_transaction_password").css("display","none");}else{document.title='<?php echo self::$language['modify_transaction_password']?>';}
        if(field!='password'){$("#tr_password").css("display","none");}else{document.title='<?php echo self::$language['modify_password']?>';	}
    
    
    });
    
    
    function submit_hidden(id){
        //jzdc_alert(id);
        obj=document.getElementById(id);
        if(obj.value==''){}
        json="{'"+obj.id+"':'"+replace_quot(obj.value)+"'}";
        try{json=eval("("+json+")");}catch(exception){alert(json);}
        $("#"+obj.id+"_state").html("<span class='fa fa-spinner fa-spin'></span>");
        $("#"+obj.id+"_state").load('<?php echo $module['action_url'];?>&update='+obj.id+"&id=<?php echo $_GET['id']?>",json,function(){
            if($(this).html().length>10){
                //jzdc_alert($(this).html());
                try{v=eval("("+$(this).html()+")");}catch(exception){alert($(this).html());}


                $(this).html(v.info);
                if(v.state=='fail'){$(this).html('');}else{}
                imgs=obj.value.split("|");
                if(v.state=='success'){$("#"+id+"_img").attr("src","./program/index/user_"+id+"/"+imgs[imgs.length-1]);}
            }
        });
        
    }
    
    
    function set_area(id,v){
        $("#"+id).prop('value',v);
        submit_hidden(id);	
    }
    
    
    </script>
	<style>
    #<?php echo $module['module_name'];?>{ min-height:600px;}
    #<?php echo $module['module_name'];?>_html{line-height:40px;}
    #<?php echo $module['module_name'];?>_html .module_div{}
	
    #<?php echo $module['module_name'];?>_html #icon_file{ border:none;}
	#<?php echo $module['module_name'];?>_html #license_photo_front_file{ border:none;}
	#<?php echo $module['module_name'];?>_html #license_photo_reverse_file{ border:none;}
    #<?php echo $module['module_name'];?>_html .input_text{width:150px;}
    #<?php echo $module['module_name'];?>_html .focus{ }
    #<?php echo $module['module_name'];?>_html .m_label{width:150px; text-align:right; padding-right:5px;}
	#<?php echo $module['module_name'];?>_table .odd{ }
	#<?php echo $module['module_name'];?>_table .even{ }
    </style>
    <div id="<?php echo $module['module_name'];?>_html">
    <div id=login_div style="display:none;" ></div>
    <form id="edit_user_form" name="edit_user_form" method="POST" action="<?php echo $module['action_url'];?>" onSubmit="return exe_check();">
    <table border="0" cellpadding="0" cellspacing="0" id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left _table style="width:100%;">
    <tr id="tr_username"><td class="m_label"><?php echo self::$language['username']?></td><td align="left"><?php echo $module['username']?><span id="username_state"></span></td></tr>
    
    <tr id="tr_nickname"><td class="m_label"><?php echo self::$language['nickname']?></td><td align="left"><input type="text" id="nickname" name="nickname" value="<?php echo $module['nickname']?>" /><span id="nickname_state"></span></td></tr>
    <tr id="tr_icon"><td class="m_label"><?php echo self::$language['icon']?></td><td align="left" id="tr_td_icon">
    <img id="icon_img" class="up_img" src="./program/index/user_icon/<?php echo $module['icon']?>" width="150"><br />
    <span id="icon_state"></span></td></tr>
    
    <tr id="tr_email"><td class="m_label"><?php echo self::$language['email']?></td><td align="left"><?php echo $module['email']?><span id="email_state"></span></td></tr>
    
    <tr id="tr_phone"><td class="m_label"><?php echo self::$language['phone']?></td><td align="left"><?php echo $module['phone']?><span id="phone_state"></span></td></tr>

    <tr id="tr_real_name"><td class="m_label"><?php echo self::$language['real_name']?></td><td align="left"><input type="text" id="real_name" name="real_name" value="<?php echo $module['real_name']?>" /><span id="real_name_state">(请填写真实姓名或企业名称)</span></td></tr>

    <tr id="tr_psw"><td class="m_label">密码</td><td align="left"><input type="password" id="password" name="password" value="" /><span id="password_state"></span>
         <span class=input_span><a href="#" id=psw_submit class="submit"><span class=b_start> </span><span class=b_middle>重置密码</span></a> </span></td></tr>




        </table>
    </form>
    </div>

</div>