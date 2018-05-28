<div id=<?php echo $module['module_name'];?> jzdc-module="<?php echo $module['module_name'];?>" align=left >
	<script src="./plugin/datePicker/index.php"></script>
    <script>
	
    $(document).ready(function(){
		$("#<?php echo $module['module_name'];?> .view_logistics").each(function(index, element) {
            id=$(this).attr('order_id');
			$(this).attr('href',$("#<?php echo $module['module_name'];?> #tr_"+id+" .express_code a").attr('href'));
        });
		$("#<?php echo $module['module_name'];?> .state_remark").each(function(index, element) {
            if(!$(this).children('a').attr('href')){
				$(this).css('display','none');
			}
        });
		if(get_param('state')!=''){$("#state").prop('value',get_param('state'));}
		
		$("#close_button").click(function(){
			$("#fade_div").css('display','none');
			$("#set_jzdc_iframe_div").css('display','none');
			return false;
		});

		$(document).on('click',"#<?php echo $module['module_name'];?> [jzdc-table] .confirm_receipt", function() {
  			id=$(this).attr('d_id');
			confirm_return=confirm('确认已收货吗?');
			if(confirm_return){
				$("#<?php echo $module['module_name'];?> #state_"+id).html('<span class=\'fa fa-spinner fa-spin\'></span>');
				$.get('<?php echo $module['action_url'];?>&act=confirm_receipt',{id:id}, function(data){
					//alert(data);
					try{v=eval("("+data+")");}catch(exception){alert(data);}
					
					$("#state_"+id).html(v.info);
					if(v.state=='success'){
						$("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][6];?>');
						$("#<?php echo $module['module_name'];?> #tr_"+id+" .confirm_receipt").css('display','none');
                        location.reload();
                    }
				});
			}
			return false;
		});

        $(document).on('click',"#<?php echo $module['module_name'];?> [jzdc-table] .confirm_check", function() {
            id=$(this).attr('d_id');
            confirm_return=confirm('确认质检通过吗?');
            if(confirm_return){
                $("#<?php echo $module['module_name'];?> #state_"+id).html('<span class=\'fa fa-spinner fa-spin\'></span>');
                $.get('<?php echo $module['action_url'];?>&act=confirm_check',{id:id}, function(data){
                    //alert(data);
                    try{v=eval("("+data+")");}catch(exception){alert(data);}

                    $("#state_"+id).html(v.info);
                    if(v.state=='success'){
                        $("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][6];?>');
                        $("#<?php echo $module['module_name'];?> #tr_"+id+" .confirm_check").css('display','none');
                        location.reload();

                    }
                });
            }
            return false;
        });

        $(document).on('click',"#<?php echo $module['module_name'];?> [jzdc-table] .check_fail", function() {
            id=$(this).attr('d_id');
            confirm_return=confirm('确认质检不通过吗?');
            if(confirm_return){
                $("#<?php echo $module['module_name'];?> #state_"+id).html('<span class=\'fa fa-spinner fa-spin\'></span>');
                $.get('<?php echo $module['action_url'];?>&act=check_fail',{id:id}, function(data){
                    //alert(data);
                    try{v=eval("("+data+")");}catch(exception){alert(data);}

                    $("#state_"+id).html(v.info);
                    if(v.state=='success'){
                        $("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][6];?>');
                        $("#<?php echo $module['module_name'];?> #tr_"+id+" .confirm_check").css('display','none');
                        location.reload();

                    }
                });
            }
            return false;
        });

        $(document).on('click',"#<?php echo $module['module_name'];?> [jzdc-table] .confirm_send", function() {
            id=$(this).attr('d_id');
            express = $("#express"+id).val();
            express_code  = $("#express_code"+id).val();

            confirm_return=confirm('确认发货吗?');
            if(confirm_return){
                $("#<?php echo $module['module_name'];?> #state_"+id).html('<span class=\'fa fa-spinner fa-spin\'></span>');
                $.get('<?php echo $module['action_url'];?>&act=confirm_send',{id:id,express:express,express_code:express_code}, function(data){
                    //alert(data);
                    try{v=eval("("+data+")");}catch(exception){alert(data);}

                    $("#state_"+id).html(v.info);
                    if(v.state=='success'){
                        $("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][6];?>');
                        $("#<?php echo $module['module_name'];?> #tr_"+id+" .confirm_check").css('display','none');
                        location.reload();

                    }
                });
            }
            return false;
        });
        $(document).on('click',"#<?php echo $module['module_name'];?> [jzdc-table] .receive_money", function() {
            id=$(this).attr('d_id');
            confirm_return=confirm('确认收款吗?');
            if(confirm_return){
                $("#<?php echo $module['module_name'];?> #state_"+id).html('<span class=\'fa fa-spinner fa-spin\'></span>');
                $.get('<?php echo $module['action_url'];?>&act=receive_money',{id:id}, function(data){
                    //alert(data);
                    try{v=eval("("+data+")");}catch(exception){alert(data);}

                    $("#state_"+id).html(v.info);
                    if(v.state=='success'){
                        $("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][6];?>');
                        $("#<?php echo $module['module_name'];?> #tr_"+id+" .confirm_check").css('display','none');
                        location.reload();

                    }
                });
            }
            return false;
        });

		$("#<?php echo $module['module_name'];?> .add_comment").click(function(){
			
			set_iframe_position($(window).width()-100,$(window).height()-200);
			//jzdc_alert(replace_file);
			$("#jzdc_iframe").attr('scrolling','auto');
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			$("#jzdc_iframe").attr('src','./index.php?jzdc=mall.buyer_comment&order_id='+$(this).parent().parent().next('.comment').attr('order_id')+'&goods_id='+$(this).parent().parent().next('.comment').attr('goods_id'));
			//alert($("#jzdc_iframe").attr('src'));
			return false;	
		});
		$(document).on('click',"#<?php echo $module['module_name'];?> .comment .edit",function(){
			set_iframe_position($(window).width()-100,$(window).height()-200);
			//jzdc_alert(replace_file);
			$("#jzdc_iframe").attr('scrolling','auto');
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			$("#jzdc_iframe").attr('src','./index.php?jzdc=mall.buyer_comment&order_id='+$(this).parent().parent().parent().attr('order_id')+'&goods_id='+$(this).parent().parent().parent().attr('goods_id'));
			//alert($("#jzdc_iframe").attr('src'));
			return false;	
		});
		$(document).on('click',"#<?php echo $module['module_name'];?> .comment .del",function(){
			if(confirm("<?php echo self::$language['delete_confirm']?>")){
				$.get('<?php echo $module['action_url']?>&act=del_comment&comment_id='+$(this).attr('d_id'),function(data){
					
				});
				$(this).parent().parent().animate({opacity:0},"slow");
			}
			return false;	
		});
		
		$("#<?php echo $module['module_name'];?> .order_state").hover(function(){
			if($(this).children('.cancel_reason').html()){
				$("#<?php echo $module['module_name'];?> .right_notice .m").html($(this).children('.cancel_reason').html());
				$("#<?php echo $module['module_name'];?> .right_notice").css('display','block').css('left',$(this).offset().left+$(this).width()+5).css('top',$(this).offset().top);
			}	
			},function(){
			if($(this).children('.cancel_reason').html()){
				$("#<?php echo $module['module_name'];?> .right_notice").css('display','none');
			}	
		});
		
		$(window).scroll(function(){
			if($(window).scrollTop()>350){
				$(".sort .sort_inner").css('width',$("#<?php echo $module['module_name'];?>").width());
				$(".sort").css('width','100%').css('left','0px');
				$(".sort").css('position','fixed').css('top','0px').css('margin-top','0px');
			}else{
				$(".sort").css('position','static').css('margin-top','10px');
			}		
		});

		$("#<?php echo $module['module_name'];?> .order_tr").each(function(index, element) {
            $(this).children('div').css('height',$(this).height());
        });
    });
    
    function update_comment(order_id,goods_id,time,content){
		if(!$("#<?php echo $module['module_name'];?> #tr_"+order_id+ " [goods_id='"+goods_id+"']").html()){
			$("#<?php echo $module['module_name'];?> #tr"+order_id+ " .other").append('<div class=comment  order_id="'+order_id+'" goods_id="'+goods_id+'"><div class=buyer></div></div>');
		}
		
		$("#<?php echo $module['module_name'];?> #tr_"+order_id+ " [goods_id='"+goods_id+"']").html('<div class=buyer><span class=time>'+time+'</span><span class=content>'+content+'<a href=# title="<?php echo self::$language['edit'];?>" class=edit> </a> <a href=# title="<?php echo self::$language['del'];?>" class=del> </a></span><span class=user><?php echo self::$language['myself'];?></span></div>');
		$("#<?php echo $module['module_name'];?> #"+id+" .add_comment").css('display','none');
	}
	
	function update_cancel_state(id){
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .pay_time_limit").css('display','none');	
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .go_pay").css('display','none');	
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][4];?>');
		if($("#<?php echo $module['module_name'];?> #tr_"+id+" .del").html()){
			$("#<?php echo $module['module_name'];?> #tr_"+id+" .cancel").css('display','none');	
			$("#<?php echo $module['module_name'];?> #tr_"+id+" .state_remark").css('display','none');	
		}else{
			$("#<?php echo $module['module_name'];?> #tr_"+id+" .cancel").attr('class','del').html('<?php echo self::$language['del']?>');
		}
	}
		
	function update_state_7(id){
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][7];?>');
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .apply_refund").html('<?php echo self::$language['edit'];?><?php echo self::$language['apply'];?>');
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .del").css('display','none');	
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .confirm_receipt").css('display','none');	
	}
    
	function update_state_9(id){
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][9];?>');
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .upload_refund_voucher").html('<?php echo self::$language['edit_refund_voucher'];?>');
	}

    function export_order(){
        $("#export_state").html("<span class='fa fa-spinner fa-spin'></span>");
        $.post('<?php echo $module['action_url'];?>&act=export', {id: 0}, function (data) {
            try {
                v = eval("(" + data + ")");
            } catch (exception) {
                alert(data);
            }
            if (v.state == 'success') {
                $("#export_state").html(v.info);
            }else{
                $("#export_state").html(v.info);
            }
        });

    }

    </script>
	<style>
	#<?php echo $module['module_name'];?> .light{ background:<?php echo $_POST['jzdc_user_color_set']['module']['background']?>;}
    #<?php echo $module['module_name'];?>{background:<?php echo $_POST['jzdc_user_color_set']['container']['background']?>  !important;}
	#<?php echo $module['module_name'];?> input {margin:0 5px;}
    #<?php echo $module['module_name'];?> .add_comment{ padding-left:10px; font-style:oblique;}
    #<?php echo $module['module_name'];?> .m_row,.filter{ background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; padding:0.3rem;}
    #<?php echo $module['module_name'];?> .filter{box-shadow: 0px 2px 5px 1px rgba(0, 0, 0, 0.1);} 
	
	
	.mall_order{ background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; margin-top:1rem;margin-bottom:1rem;box-shadow: 0px 2px 5px 2px rgba(0, 0, 0, 0.1);padding:0.5rem;}
	.mall_order .order_head{ line-height:2rem; border-bottom: 1px solid  #EEE; }
	.mall_order .order_head .shop_name{ display:inline-block; vertical-align:top; width:50%; color: blue}
	.mall_order .order_head .shop_name:after{margin-left:8px; font: normal normal normal 1rem/1 FontAwesome; content:"\f105"; }
	.mall_order .order_head .order_state{display:inline-block; vertical-align:top; width:50%; text-align:right; font-weight:bold; color:red;}
	.preferential_td{text-align:right; line-height:3rem;}
	.preferential_td div{ display:inline-block;}
	.preferential_td .big_money{ color:#F00; }
	.operation_td{ text-align:right;line-height:25px;}
	.goods_td{}
	.goods_td .goods_info{ padding:0.3rem;  margin-bottom:0.5rem;}
	.goods_td .goods_info .goods_div{ border-bottom: 1px #CCCCCC dashed; padding-top:0.3rem; padding-bottom:0.3rem;}
	.goods_td .goods_info .icon{ display:inline-block; vertical-align:top; width:20%; text-align:center;}
	.goods_td .goods_info .icon img{ width:80%;}
	.goods_td .goods_info .other{display:inline-block; vertical-align:top; width:80%; color:#999; }
	.goods_td .goods_info .other a{ color:#999; }
	.goods_td .goods_info .other .title{ line-height:1.5rem; height:3rem; overflow:hidden; }
	.goods_td .goods_info .other .price{ 		}
	.state_remark{ display: block; text-align:right; line-height:3rem;}	
	.state_remark br{ display:none;}	
	.state_remark div{  display:inline-block;}	
	.state_remark a{ display:inline-block; margin-left:0.5rem; padding-left:0.5rem; padding-right:0.5rem; line-height:2rem; border-radius:0.3rem; background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?> !important;}
	.preferential_way{ padding-right:1rem;}
	.preferential_way br{ display:none;}
	.express_code{ display:none;}
	
	#<?php echo $module['module_name'];?> .goods_info .comment{ text-align:right; line-height:2rem; padding-top:1rem; }
	#<?php echo $module['module_name'];?> .goods_info .comment .buyer{ white-space:nowrap;}
	#<?php echo $module['module_name'];?> .goods_info .comment .seller{ margin-top:10px;white-space:nowrap;}
	#<?php echo $module['module_name'];?> .goods_info .comment .user{ display:inline-block; width:50px; text-align:left;}
	#<?php echo $module['module_name'];?> .goods_info .comment .user:before{margin-right:8px; color:#F90; font: normal normal normal 1rem/1 FontAwesome; content:"\f0da";color:<?php echo $_POST['jzdc_user_color_set']['nv_3_hover']['background']?>;}
	#<?php echo $module['module_name'];?> .goods_info .comment .content{display:inline-block; padding-left:10px;  padding-right:10px; border-radius:5px; background:<?php echo $_POST['jzdc_user_color_set']['nv_3_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_3_hover']['text']?>; font-size:1rem;  max-width:70%; white-space: normal;}
	#<?php echo $module['module_name'];?> .goods_info .comment .content .edit{ width:20px; height:25px; display:inline-block;}
	#<?php echo $module['module_name'];?> .goods_info .comment .content .del{ width:20px; height:25px; display:inline-block;}
	#<?php echo $module['module_name'];?> .goods_info .comment .content .edit:before{margin-left:8px; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; font: normal normal normal 1rem/1 FontAwesome; content:"\f040";}
	#<?php echo $module['module_name'];?> .goods_info .comment .content .del:before{margin-left:8px; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; font: normal normal normal 1rem/1 FontAwesome; content:"\f014";}
	#<?php echo $module['module_name'];?> .goods_info .comment .content a:hover{ text-decoration:none;}
	#<?php echo $module['module_name'];?> .goods_info .comment .time{ padding-right:10px; color:#CCC; font-size:13px;}
	.refund{ margin-left:10px; opacity:0.5;}
	#<?php echo $module['module_name'];?> .o_price{text-decoration: line-through; opacity:0.4; font-size:0.9rem;}
    </style>
    <div id="<?php echo $module['module_name'];?>_html"  jzdc-table=1>
    <div  class="portlet light" >
        <div class="m_row"><div class="half"><div class="dataTables_length"><select class="form-control" id="page_size" ><option value="10">10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option></select> <?php echo self::$language['per_page']?></m_label></div></div><div class="half"><div class="dataTables_filter"><m_label><?php echo self::$language['search']?>:<input type="search"  placeholder="<?php echo self::$language['goods_name']?>/<?php echo self::$language['order_number']?>/<?php echo self::$language['receiver_info'];?>" class="form-control" ></m_label></div></div></div>
		
    <div class="filter"><?php echo self::$language['content_filter']?>:
        <?php echo $module['filter']?> <span><a href="#" onclick="export_order();" class="submit">导出订单</a></span><span id='export_state' class="state"></span>
                 <span id=time_limit><span class=start_time_span><?php echo self::$language['start_time']?></span><input type="text" id="start_time" name="start_time" value="<?php echo @$_GET['start_time'];?>"  onclick=show_datePicker(this.id,'date') onblur= hide_datePicker()  /> -
       <span class=end_time_span><?php echo self::$language['end_time']?></span><input type="text" id="end_time" name="end_time"  value="<?php echo @$_GET['end_time'];?>"  onclick=show_datePicker(this.id,'date') onblur= hide_datePicker()  /> <a href="#" onclick="return time_limit();" class="submit"><?php echo self::$language['submit']?></a></span>

        
    </div>
    </div>
    <div class=order_div>
    	 <?php echo $module['list']?>
    </div>
   
    <?php echo $module['page']?>
    </div>
</div>