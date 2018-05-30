<div id=<?php echo $module['module_name'];?>  jzdc-module="<?php echo $module['module_name'];?>" align=left >
	<script src="./plugin/datePicker/index.php"></script>
    <script>
    $(document).ready(function(){
        <?php echo $module['script']?>

		$("#<?php echo $module['module_name'];?> .view_logistics").each(function(index, element) {
            id=$(this).attr('order_id');
			$(this).attr('href',$("#<?php echo $module['module_name'];?> #head_"+id+" .express_code a").attr('href'));
        });
		if(get_param('state')!=''){$("#state").prop('value',get_param('state'));}
		if(get_param('pay_method')!=''){$("#pay_method").prop('value',get_param('pay_method'));}
		if(get_param('buy_method')!=''){$("#buy_method").prop('value',get_param('buy_method'));}
		if(get_param('preferential_way')!=''){$("#preferential_way").prop('value',get_param('preferential_way'));}
		if(get_param('invoice')!=''){$("#invoice").prop('value','<?php echo @$_GET['invoice'];?>');}
		if(get_param('express')!=''){$("#express").prop('value',get_param('express'));}
		if(get_param('pay_method_remark')!=''){$("#pay_method_remark").prop('value',get_param('pay_method_remark'));}		
		$("#<?php echo $module['module_name'];?> .days a[se='<?php echo @$_GET['start_time']?>-<?php echo @$_GET['end_time']?>']").attr('class','current');
		
		$("#<?php echo $module['module_name'];?> .edit_a[act='edit_quantity']").each(function(index, element) {
            $(this).attr('d_id',$(this).next('input').attr('id'));
        });
		
		$("#close_button").click(function(){
			$("#fade_div").css('display','none');
			$("#set_jzdc_iframe_div").css('display','none');
			return false;
		});


		$(document).on('click','#<?php echo $module['module_name'];?> .edit_a,#<?php echo $module['module_name'];?> .edit_b,#<?php echo $module['module_name'];?> .edit_c',function(){
			set_iframe_position(800,500);
			//jzdc_alert(replace_file);
			$("#jzdc_iframe").attr('scrolling','auto');
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			url='./index.php?jzdc=mall.order_edit&id='+$(this).attr('d_id')+'&act='+$(this).attr('act');
			//window.location.href=url;
			$("#jzdc_iframe").attr('src',url);
			return false;	
		});
		
		$(document).on('click', "#<?php echo $module['module_name'];?> .mall_order .del",function() {
			if(confirm("<?php echo self::$language['delete_confirm']?>")){
				id=$(this).parent().parent().attr('id').replace(/tr_/,'');
				$("#<?php echo $module['module_name'];?> #state_"+id).html('<span class=\'fa fa-spinner fa-spin\'></span>');
				$.get('<?php echo $module['action_url'];?>&act=del',{id:id}, function(data){
					try{v=eval("("+data+")");}catch(exception){alert(data);}
					$("#state_"+id).html(v.info);
					if(v.state=='success'){
						$("#tr_"+id).parent().animate({opacity:0},"slow",function(){$("#tr_"+id).parent().css('display','none');});
					}
				});
			}
			return false; 	
		});
		
		$(document).on('click',"#<?php echo $module['module_name'];?>  .confirm_refund", function() {
			if(confirm("<?php echo self::$language['confirm_refund_notice']?>")){
				id=$(this).attr('d_id');
				$("#<?php echo $module['module_name'];?> #state_"+id).html('<span class=\'fa fa-spinner fa-spin\'></span>');
				$.get('<?php echo $module['action_url'];?>&act=confirm_refund',{id:id}, function(data){
					//alert(data);
					try{v=eval("("+data+")");}catch(exception){alert(data);}
					
					$("#state_"+id).html(v.info);
					if(v.state=='success'){
						$("#tr_"+id+" .operation_td").html('');
						$("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][10];?>');
					}
				});
			}
			return false; 	
		});

        $(document).on('click','#<?php echo $module['module_name'];?>  .pending_price',function(){
            id=$(this).parent().parent().parent().attr('id').replace(/tr_/,'');
            price  = $("#p_price_"+id).val();
            if(price.length == 0|| price < 0){
                alert("请输入正确的核实价格");
                return false;
            }
            if(confirm("确定要将订单状态改为:待签约?")){
                $.get('<?php echo $module['action_url'];?>&act=pending_price', {id: id, price: price}, function (data) {
                    try {
                        v = eval("(" + data + ")");
                    } catch (exception) {
                        alert(data);
                    }

                    $("#state_" + id).html(v.info);
                    if (v.state == 'success') {
                        location.reload();
                    }
                });
            }

            return false;
        });

        $(document).on('click','#<?php echo $module['module_name'];?>  .pending_price2',function(){

            id=$(this).parent().parent().parent().attr('id').replace(/tr_/,'');

            number1  = $("#contract_number_"+id).val();
            date1 = $("#pay_date_"+id).val();
            price1  = $("#price_"+id).val();
            if(price1.length == 0|| price1 < 0){
                alert("请输入正确的核实价格");
                return false;
            }
            //单价
            var unit_price = $('#p_unit_price_'+id).val();
            if(unit_price.length == 0|| unit_price < 0){
                alert("请输入正确的单价");
                return false;
            }
            //商品数量
            var amount = $('#p_amount_'+id).val();
            if(amount.length == 0|| amount < 0){
                alert("请输入正确的商品数量");
                return false;
            }

            if(number1.length == 0){
                alert("请输入正确的合同编号");
                return false;
            }
            if(date1.length > 0){
                var reg = /^(\d{4})-(\d{2})-(\d{2})$/;
                if (!reg.test(date1)){
                    alert("账期截止日的格式为yyyy-mm-dd");
                    return false;
                }
                //
                var sdate = date1.split('-');
                var date = new Date(sdate[0], sdate[1]-1, sdate[2]);

                if( date <= new Date()){
                    alert("账期截止日必须是将来日期");
                    return false;
                }

            }

            if(confirm("确定要提交吗?")){
                $.get('<?php echo $module['action_url'];?>&act=pending_price2', {id: id, price: price1, number: number1, date:date1,product_amount:amount,unit_price:unit_price}, function (data) {
                    try {
                        v = eval("(" + data + ")");
                    } catch (exception) {
                        alert(data);
                    }

                    $("#state_" + id).html(v.info);
                    if (v.state == 'success') {
                        location.reload();
                    }
                });
            }

            return false;
        });

        $(document).on('click','#<?php echo $module['module_name'];?>  .pending_sign',function(){

            id=$(this).parent().parent().parent().attr('id').replace(/tr_/,'');

            number1  = $("#contract_number_"+id).val();
            date1 = $("#pay_date_"+id).val();

            if(number1.length == 0){
                alert("请输入正确的合同编号");
                return false;
            }
            if(date1.length > 0){
                var reg = /^(\d{4})-(\d{2})-(\d{2})$/;
                if (!reg.test(date1)){
                    alert("账期截止日的格式为yyyy-mm-dd");
                    return false;
                }
                //
                var sdate = date1.split('-');
                var date = new Date(sdate[0], sdate[1]-1, sdate[2]);

                if( date <= new Date()){
                    alert("账期截止日必须是将来日期");
                    return false;
                }

            }

            if(confirm("确定要提交吗?")){
                $.get('<?php echo $module['action_url'];?>&act=pending_sign', {id: id, number: number1, date:date1}, function (data) {

                    try {
                        v = eval("(" + data + ")");
                    } catch (exception) {
                        alert(data);
                    }

                    $("#state_" + id).html(v.info);
                    if (v.state == 'success') {
                        location.reload();
                    }
                });
            }

            return false;
        });

        $(document).on('click','#<?php echo $module['module_name'];?>  .pending_pay',function(){
            id=$(this).parent().parent().attr('id').replace(/tr_/,'');

            if(selPayType == 1){
                //转账
                number1  = $("#pay_number_"+id).val();
                date1  = $("#pay_date_"+id).val();
                if(number1.length == 0){
                    alert("请输入转账流水号");
                    return false;
                }

                if(date1.length == 0){
                    alert("转输入到账日期");
                    return false;
                }

            }else{
                //商票
                number1  = $("#bill_number_"+id).val();
                date1  = $("#bill_time_"+id).val();
                if(number1.length == 0){
                    alert("请输入商票票号");
                    return false;
                }

                if(date1.length == 0){
                    alert("转输入承兑日期");
                    return false;
                }
            }
            picture1 = $("#icon"+id).val();
            if(picture1.length == 0){
                alert("请上传回执图片");
                return false;
            }

            if(date1.length > 0){
                var reg = /^(\d{4})-(\d{2})-(\d{2})$/;
                if (!reg.test(date1)){
                    alert("日期格式不对,正确格式为yyyy-mm-dd");
                    return false;
                }
            }

            if(confirm("确定提交吗?")){
                $.get('<?php echo $module['action_url'];?>&act=pending_pay', {id: id, type:selPayType, number:number1, date:date1, picture:picture1}, function (data) {

                    try {
                        v = eval("(" + data + ")");
                    } catch (exception) {
                        alert(data);
                    }

                    $("#state_" + id).html(v.info);
                    if (v.state == 'success') {
                        location.reload();
                    }
                });
            }

            return false;
        });

        $(document).on('click','#<?php echo $module['module_name'];?>  .question_fix',function(){
            id=$(this).parent().parent().attr('id').replace(/tr_/,'');
            $.get('<?php echo $module['action_url'];?>&act=question_fix', {id: id}, function (data) {
                try {
                    v = eval("(" + data + ")");
                } catch (exception) {
                    alert(data);
                }

                $("#state_" + id).html(v.info);
                if (v.state == 'success') {
                    location.reload();
                }
            });

            return false;
        });


         $(document).on('click','#<?php echo $module['module_name'];?>  .cancel',function(){
             id=$(this).parent().parent().attr('id').replace(/tr_/,'');
             reason = prompt("请输入拒绝理由:","");
             if (reason != null) {
                 $.get('<?php echo $module['action_url'];?>&act=cancel', {id: id, cancel_reason:reason}, function (data) {
                     try {
                         v = eval("(" + data + ")");
                     } catch (exception) {
                         alert(data);
                     }

                     $("#state_" + id).html(v.info);
                     if (v.state == 'success') {
                         location.reload();
                     }
                 });
             }

             return false;
         });



        //$(document).on('click','#<?php echo $module['module_name'];?>  .cancel',function(){
			//set_iframe_position(800,500);
			//jzdc_alert(replace_file);
			//$("#jzdc_iframe").attr('scrolling','auto');
			//$("#fade_div").css('display','block');
			//$("#set_jzdc_iframe_div").css('display','block');
			//$("#jzdc_iframe").attr('src','./index.php?jzdc=mall.order_cancel_seller&id='+$(this).attr('d_id'));
			//return false;
		//});
		
		$(document).on('click','#<?php echo $module['module_name'];?>  .view_apply',function(){
			set_iframe_position(1000,500);
			//jzdc_alert(replace_file);
			$("#jzdc_iframe").attr('scrolling','auto');
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			$("#jzdc_iframe").attr('src','./index.php?jzdc=mall.view_refund&id='+$(this).attr('d_id'));
			return false;	
		});
		
		$(document).on('click','#<?php echo $module['module_name'];?>  .view_refund_voucher',function(){
			set_iframe_position(1000,500);
			//jzdc_alert(replace_file);
			$("#jzdc_iframe").attr('scrolling','auto');
			$("#fade_div").css('display','block');
			$("#set_jzdc_iframe_div").css('display','block');
			$("#jzdc_iframe").attr('src','./index.php?jzdc=mall.view_refund_express&id='+$(this).attr('d_id'));
			return false;	
		});

		$("#<?php echo $module['module_name'];?>  .print_order").click(function(){
			ids=get_ids();
			if(ids==''){$("#state_select").html("<?php echo self::$language['select_null']?>");return false;}
			$("#state_select").html('');
			$(this).attr('href',$(this).attr('href_pre')+"&ids="+ids);
				
		});
		
		$("#<?php echo $module['module_name'];?>  .order_print_list").click(function(){
			ids=get_ids();
			if(ids==''){$("#state_select").html("<?php echo self::$language['select_null']?>");return false;}
			$("#state_select").html('');
			$(this).attr('href',$(this).attr('href_pre')+"&ids="+ids);
				
		});
		
		$("#<?php echo $module['module_name'];?>  .express_print").click(function(){
			ids=get_ids();
			if(ids==''){$("#state_select").html("<?php echo self::$language['select_null']?>");return false;}
			$("#state_select").html('');
			$(this).attr('href',$(this).attr('href_pre')+"&ids="+ids);
				
		});
		
		$("#<?php echo $module['module_name'];?>   .print_tag").click(function(){
			ids=get_goods_ids();
			if(ids==''){$("#state_select").html("<?php echo self::$language['select_null']?>");return false;}
			$("#state_select").html('');
			$(this).attr('href',$(this).attr('href_pre')+"&ids="+ids);
				
		});
		
        $(" .id").change(function(){
            if($(this).prop('checked')){
                $("#tr_"+this.id).addClass('checked');
				$("#tr_"+this.id+" .goods_id").prop('checked',true);
            }else{
                $("#tr_"+this.id).removeClass('checked');
				$("#tr_"+this.id+" .goods_id").prop('checked',false);
            }
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
				$(".sort").css('position','fixed').css('top','0px').css('margin-top','0px').css('box-shadow','0 0 5px #888');
			}else{
				$(".sort").css('position','static').css('margin-top','10px').css('box-shadow','none');
			}		
		});


		$("#<?php echo $module['module_name'];?> .order_tr").each(function(index, element) {
            $(this).children('div').css('height',$(this).height());
        });
    });
    var selPayType = 1;
    function payTypeChange(obj, id){
        selPayType = obj.options[obj.options.selectedIndex].value;

        if (selPayType == 1){
            $("#type1_"+id).css('display','block');  // 转账
            $("#type2_"+id).css('display','none');  // 汇票

        }else{
            $("#type1_"+id).css('display','none');  // 转账
            $("#type2_"+id).css('display','block');  // 汇票
        }

    }

    var signType = 1;
    function signTypeChange(obj, id){
        signType = obj.options[obj.options.selectedIndex].value;

        if (signType == 1){
            $("#sign1_"+id).css('display','block');
            $("#sign2_"+id).css('display','none');

        }else{
            $("#sign1_"+id).css('display','none');
            $("#sign2_"+id).css('display','block');
        }

    }


    
    
    function del_select(){
        ids=get_ids();
        if(ids==''){$("#state_select").html("<?php echo self::$language['select_null']?>");return false;}
		$("#state_select").html('');
        if(confirm("<?php echo self::$language['delete_confirm']?>")){
		
        idss=ids;
        ids=ids.split("|");	
        for(id in ids){
            if(ids[id]!=''){$("#state_"+ids[id]).html('<span class=\'fa fa-spinner fa-spin\'></span>');}	
        }
            $.get('<?php echo $module['action_url'];?>&act=del_select',{ids:idss}, function(data){
               //alert(data);
				try{v=eval("("+data+")");}catch(exception){alert(data);}
				
                $("#state_select").html(v.info);
                if(v.state=='success'){
                //alert(ids);	
                success=v.ids.split("|");
                for(id in ids){
                    //jzdc_alert(ids[id]);
                    if(in_array(ids[id],success)){
                        $("#state_"+ids[id]).html("<span class=success><?php echo self::$language['success'];?></span>");	
                        $("#tr_"+ids[id]).parent().css('display','none');
                    }else{
                        $("#state_"+ids[id]).html("<?php echo self::$language['fail'];?>");	
                    }	
                }
                }
            });
        }	
         return false;	
    }
    
	function update_express_cost_buyer(id,v){
		$("#<?php echo $module['module_name'];?> #head_"+id+" .express_cost_buyer .value").html(v);
	}
	function update_actual_money(id,v){
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .actual_money .value").html(v);
	}
	function update_sum_money(id,v){
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .sum_money .value").html(v);
	}
	function update_express_cost_seller(id,v){
		$("#<?php echo $module['module_name'];?> #head_"+id+" .express_cost_seller .value").html(v);
	}
	function update_seller_remark(id,v){
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .seller_remark .value").html(v);
	}
	function update_change_price_reason(id,v){
		if($("#<?php echo $module['module_name'];?> #tr_"+id+" .change_price_reason").html()){
			$("#<?php echo $module['module_name'];?> #tr_"+id+" .change_price_reason").html(v);
		}else{
			$("<div class=change_price_reason>"+v+"</div>").insertAfter("#<?php echo $module['module_name'];?> #tr_"+id+" .actual_money");
		}
	}
	
	function update_receiving_extension(id,v){$("#<?php echo $module['module_name'];?> #tr_"+id+" .day").html(v);}
	function update_express(id,v){$("#<?php echo $module['module_name'];?> #head_"+id+" .express").html(v);}
	function update_express_code(id,v){$("#<?php echo $module['module_name'];?> #head_"+id+" .express_code").html(v);}
	function update_action_button_2(id){
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][2];?>');
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .operation_td").html("<a href='#' class=edit_c d_id="+id+" act='order_state_2'><?php echo self::$language['edit'];?><?php echo self::$language['express_2'];?></a><br /><a href='#' class=cancel><?php echo self::$language['cancel'];?><?php echo self::$language['order_id'];?></a>");
	}
	
    function select_all(){
		//jzdc_alert('select_all');
        $(" tbody .id").prop('checked',true);
        $(" tbody .goods_id").prop('checked',true);
        $(" tbody tr").addClass('checked');
        return false;	
    }
    function reverse_select(){
        $(" tbody .id").each(function(){
            $(this).prop("checked",!this.checked);
            if($(this).prop('checked')){
                $("#tr_"+this.id).addClass('checked');
				$("#tr_"+this.id+" .goods_id").prop('checked',true);
            }else{
                $("#tr_"+this.id).removeClass('checked');
				$("#tr_"+this.id+" .goods_id").prop('checked',false);
            }
                  
        });
       return false; 	
    }
    
    function get_ids(){
        ids='';
        $("#<?php echo $module['module_name'];?> .id").each(function(){
            if($(this).prop("checked")){ids+=this.id+"|";}              
        });
        return ids;
    }
    function get_goods_ids(){
        ids='';
        $("#<?php echo $module['module_name'];?> .goods_id").each(function(){
            if($(this).prop("checked")){ids+=this.id+"|";}              
        });
        return ids;
    }
	
	function update_order_goods_quantity(id,quantity,money){
		g_id=$("#<?php echo $module['module_name'];?> #"+id).parent().parent().parent().attr('id');
		g_id=g_id.replace(/g_/,'');
		o_id=$("#<?php echo $module['module_name'];?> #g_"+g_id).parent().parent().parent().attr('id');
		o_id=o_id.replace(/tr_/,'');
		//alert(g_id+','+o_id);
		$("#<?php echo $module['module_name'];?> #g_"+g_id+" .g_quantity").html(parseFloat($("#<?php echo $module['module_name'];?> #g_"+g_id+" .g_quantity").html())+parseFloat(quantity));
		temp=parseFloat($("#<?php echo $module['module_name'];?> #g_"+g_id+" .g_sum_money").html())+parseFloat(money);
		temp=temp.toFixed(2);
		$("#<?php echo $module['module_name'];?> #g_"+g_id+" .g_sum_money").html(temp);
		
		temp=parseFloat($("#<?php echo $module['module_name'];?> #head_"+o_id+" .buyer_info .goods_money .value").html())+parseFloat(money);
		temp=temp.toFixed(2);
		$("#<?php echo $module['module_name'];?> #head_"+o_id+" .buyer_info .goods_money .value").html(temp);
		
		temp=parseFloat($("#<?php echo $module['module_name'];?> #tr_"+o_id+" .money_div .sum_money .value").html())+parseFloat(money);
		temp=temp.toFixed(2);
		$("#<?php echo $module['module_name'];?> #tr_"+o_id+" .money_div .sum_money .value").html(temp);
		
		temp=parseFloat($("#<?php echo $module['module_name'];?> #tr_"+o_id+" .money_div .actual_money .value").html())+parseFloat(money);
		temp=temp.toFixed(2);
		$("#<?php echo $module['module_name'];?> #tr_"+o_id+" .money_div .actual_money .value").html(temp);
		
		
	}
	
	function update_cancel_state(id,state){
		if(state=='success'){
			$("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][5];?>');
				$("#<?php echo $module['module_name'];?> #tr_"+id+" .edit_c").css('display','none');	
				$("#<?php echo $module['module_name'];?> #tr_"+id+" .cash_on_delivery").css('display','none');	
				$("#<?php echo $module['module_name'];?> #tr_"+id+" .cancel").css('display','none');	
			if($("#<?php echo $module['module_name'];?> #tr_"+id+" .del").html()){
				
			}else{
				$("#<?php echo $module['module_name'];?> #tr_"+id+" .cancel").attr('class','del').html('<?php echo self::$language['del']?>');
			}
		}
	}
	
	function update_state_8(id){
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .order_state").html('<?php echo self::$language['order_state'][8];?>');
		$("#<?php echo $module['module_name'];?> #tr_"+id+" .view_apply").css('display','none');	
	}
	
    </script>
	<style>
	#set_jzdc_iframe_div{top:40%; left:420px; }
	#jzdc_iframe{ height:100px;width:500px; overflow:scroll;}

	#<?php echo $module['module_name'];?> .light{ background:<?php echo $_POST['jzdc_user_color_set']['module']['background']?>;}
    #<?php echo $module['module_name'];?>{ background:none;}
	#<?php echo $module['module_name'];?> input {margin:0 5px;}
    #<?php echo $module['module_name'];?> .add_comment{ padding-left:10px; font-style:oblique;}
    #<?php echo $module['module_name'];?>  .filter{ line-height:50px; padding-bottom:1rem;} 
	
    #<?php echo $module['module_name'];?> #search_filter{ width:280px;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_info{  padding-left:10px; height:2.85rem; line-height:2.85rem; background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_info a{color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;}
        #<?php echo $module['module_name'];?> .title_tr .buyer_info br{display:none;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_info .buyer{ display:inline-block; vertical-align:top; min-width:120px; margin-right:20px;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_info .add_time{ display:inline-block; vertical-align:top; min-width:120px; margin-right:20px;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_info .order_id{ display:inline-block; vertical-align:top; min-width:140px; margin-right:20px;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_info .goods_money{ display:inline-block; vertical-align:top; min-width:180px; margin-right:20px;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_info .express_cost_buyer{ display:inline-block; vertical-align:top; min-width:150px; margin-right:20px;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_info .express_cost_seller{ display:inline-block; vertical-align:top; min-width:150px; margin-right:20px;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_info .invoice{ display:inline-block; vertical-align:top; min-width:120px; margin-right:20px;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_address{padding-left:10px; font-size:1rem; height:2.2rem; line-height:2.2rem;  background: #EFEFEF;color:#999;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_address .delivery_time{  padding-left:20px; color: #888;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_address .express{  padding-left:20px; color: #888;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_address .express_code{  padding-left:10px; color: #888;}
	#<?php echo $module['module_name'];?> .title_tr .buyer_address .express_code a{color: #888;}
	
	#<?php echo $module['module_name'];?> .order_tr{ white-space:nowrap;}
	#<?php echo $module['module_name'];?> .checkbox_td{ display:inline-block; vertical-align:top; width:2%; overflow:hidden; text-align:left;}
	#<?php echo $module['module_name'];?> .goods_td{ display:inline-block; vertical-align:top; width:50%;padding-left:1%; overflow:hidden; text-align:left;}
	#<?php echo $module['module_name'];?> .preferential_td{ display:inline-block; vertical-align:top; width:10%; padding-left:1%; padding-top:10px; overflow:hidden; border-left:#CCC dashed 1px;  text-align:center; white-space:normal;}
	#<?php echo $module['module_name'];?> .state_td{ display:inline-block; vertical-align:top; width:10%; padding-left:1%;padding-top:10px;border-left:#CCC dashed 1px; overflow:hidden;text-align:center;white-space:normal;}
	#<?php echo $module['module_name'];?> .operation_td{ display:inline-block; vertical-align:top; width:20%;padding-left:1%;padding-top:10px;border-left:#CCC dashed 1px;  overflow:hidden;white-space:normal;}
	#<?php echo $module['module_name'];?> .goods_info{ padding-top:10px;padding-bottom:10px; }
	#<?php echo $module['module_name'];?> .goods_info .goods{ padding-bottom:2px;  }
	#<?php echo $module['module_name'];?> .goods_info .goods .icon{ display:inline-block; vertical-align:top; width:60px; height:60px;}
	#<?php echo $module['module_name'];?> .goods_info .goods .icon img{ width:60px; height:60px; border:none;}
	#<?php echo $module['module_name'];?> .goods_info .goods .title_price{ padding-left:10px;  display:inline-block; vertical-align:top; width:600px;}
	#<?php echo $module['module_name'];?> .goods_info .goods .title_price .title{text-align:left; display:block; line-height:20px; height:40px; font-size:1rem; text-decoration:none; overflow:hidden; white-space: normal;text-overflow: ellipsis;}
	#<?php echo $module['module_name'];?> .goods_info .goods .title_price .price{ font-size:1rem; line-height:20px; text-align:left;}
	#<?php echo $module['module_name'];?> .goods_info .goods .title_price .price a{ padding-right:30px;}
	
	#<?php echo $module['module_name'];?> .goods_info .goods .comment{ text-align:right; line-height:25px;}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .buyer{}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .seller{ margin-top:10px;}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .user{ display:inline-block; width:50px; text-align:left;}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .user:before{margin-right:8px; color:#F90; font: normal normal normal 1rem/1 FontAwesome; content:"\f0da";}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .content{background: #F90; display:inline-block; padding-left:10px;  padding-right:10px; border-radius:5px; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; font-size:1rem;  max-width:70%;}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .content .edit{ width:20px; height:25px; display:inline-block;}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .content .del{ width:20px; height:25px; display:inline-block;}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .content .edit:before{margin-right:8px; color:#F90; font: normal normal normal 1rem/1 FontAwesome; content:"\f0da";}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .content .del:before{margin-right:8px; color:#F90; font: normal normal normal 1rem/1 FontAwesome; content:"\f0da";}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .content a:hover{ text-decoration:none;}
	#<?php echo $module['module_name'];?> .goods_info .goods .comment .time{ padding-right:10px; color:#CCC; font-size:13px;}
	
	
	#<?php echo $module['module_name'];?> .remark{ color: #777; font-size:1rem;}
	
	
	#<?php echo $module['module_name'];?> .checkbox_td{ vertical-align:top; padding-top:8px;}
	#<?php echo $module['module_name'];?> .preferential_way{ background:<?php echo $_POST['jzdc_user_color_set']['nv_2_hover']['background']?>; margin-top:10px; padding-left:5px; padding-right:5px; border-radius:5px;}
	#<?php echo $module['module_name'];?> .money_div{}
	#<?php echo $module['module_name'];?> .money_div .sum_money{}
	#<?php echo $module['module_name'];?> .money_div .sum_money .value{}
	#<?php echo $module['module_name'];?> .money_div .actual_money{}
	#<?php echo $module['module_name'];?> .money_div .actual_money .value{}
	#<?php echo $module['module_name'];?> .change_price_reason{ font-style:oblique; font-size:1rem;}
	
	#<?php echo $module['module_name'];?> .preferential_way{ line-height:20px; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;display:inline-block; padding:5px;}
	
	#<?php echo $module['module_name'];?> .state_remark{}
	#<?php echo $module['module_name'];?> .state_remark .pay_method{  display:inline-block; padding:5px; line-height:20px; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>;border-radius:5px;}
	
	#<?php echo $module['module_name'];?> .operation_td a{ font-size:16px;}
	#<?php echo $module['module_name'];?> .operation_td .time_limit {padding-left: 18px;font-size: 1rem;color: #808080;}
	#<?php echo $module['module_name'];?> .operation_td .time_limit:before{margin-right:8px; color:#F90; font: normal normal normal 1rem/1 FontAwesome; content:"\f017";}
	
	#<?php echo $module['module_name'];?> .edit_a{ display:inline-block; height:20px; width:20px; }
	#<?php echo $module['module_name'];?> .edit_b{ display:inline-block; height:20px; width:20px;}
	#<?php echo $module['module_name'];?> .edit_a:before{margin-left:3px; color:#F90; font: normal normal normal 1rem/1 FontAwesome; content:"\f044";}
	#<?php echo $module['module_name'];?> .edit_b:before{margin-left:3px; color:#F90; font: normal normal normal 1rem/1 FontAwesome; content:"\f040";}
	#<?php echo $module['module_name'];?> .filter #time_limit .submit .b_middle{background: #ccc;padding: 5px 10px;border-radius:2px;}
	#<?php echo $module['module_name'];?> .filter .search .b_middle{background: #ccc;padding: 5px 10px; border-radius:2px;}
	#<?php echo $module['module_name'];?> .order_state{ display:block; background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;  cursor:pointer; margin-bottom:10px; border-radius:5px;}
    #<?php echo $module['module_name'];?> .operation_btn{ display:block; text-align: center; width:10rem; background:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['background']?>; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>;  cursor:pointer; margin-bottom:10px; border-radius:5px;}
    #<?php echo $module['module_name'];?> .go_pay{ display:block; background: #F60; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; padding-left:5px;}
	#<?php echo $module['module_name'];?> .big_money{ font-size:18px; font-weight:bold; text-align:center; line-height:40px;}
	#<?php echo $module['module_name'];?> .pay_time_limit{ line-height:22px; font-size:1rem; color: #666;}
	#<?php echo $module['module_name'];?> .view_logistics{ display:block;}
	#<?php echo $module['module_name'];?> .cancel_reason{}
    #<?php echo $module['module_name'];?> .line{ line-height:2.5rem;white-space:nowrap;}
    #<?php echo $module['module_name'];?> .line .m_label{ display:inline-block; vertical-align: middle; width:25%; text-align:right;  box-shadow:none;}
    #<?php echo $module['module_name'];?> .line .value{ display:inline-block; vertical-align:top; width:75%; white-space:normal; }
    #<?php echo $module['module_name'];?> .line .value input{ width:80%;}
    .right_notice{display:none;position:absolute;height:30px; line-height:30px; overflow:hidden; white-space:nowrap; color:<?php echo $_POST['jzdc_user_color_set']['nv_1_hover']['text']?>; z-index:9999;}
	.mall_order{ padding:0px !important; overflow:hidden; margin-bottom:30px;}
	/*.mall_order:hover{ opacity:0.9;}*/
	.sort{ z-index:99999; line-height:2rem; height:2rem; padding:0px !important; margin:0px;background-color:#fff; margin-top:15px;}
	.sort .sort_inner{ margin:auto;}
	.sort div{ padding-top:0px !important;}
	.credits_remark{ display:inline-block; vertical-align:top; float:right; padding-right:0.5rem;	}
	.goods_id{ display:none;}
	.refund{ margin-left:10px; opacity:0.5;}
	[pay_method_remark=weixin]:before{margin-left:2px; font: normal normal normal 1rem/1 FontAwesome; content:"\f1d7";}
	[pay_method_remark=weixin_wap]:after{margin-left:2px; font: normal normal normal 1rem/1 FontAwesome; content:"\f1d7";}
	[pay_method_remark=weixin_wap]:before{margin-left:2px; font: normal normal normal 1rem/1 FontAwesome; content:"\f10b";}
	[pay_method_remark=alipay]:before{margin-left:2px; font: normal normal normal 1rem/1 jzdc; content:"\f01d";}
	[pay_method_remark=alipay_wap]:before{margin-left:2px; font: normal normal normal 1rem/1 jzdc; content:"\f01d";}
	[pay_method_remark=weixin_wap]:before{margin-left:2px; font: normal normal normal 1rem/1 FontAwesome; content:"\f10b";}
	.days a{ margin-right:5px;}
    #<?php echo $module['module_name'];?> .days .current{ color:#FFF; background-color:#F30; padding-left:5px; padding-right:5px; }	
	#<?php echo $module['module_name'];?> .o_price{text-decoration: line-through; opacity:0.4; font-size:0.9rem;}

    </style>
    <div id="<?php echo $module['module_name'];?>_html"  jzdc-table=1>
    
    
    <div class=right_notice><span class=s>&nbsp;</span><span class=m>&nbsp;wertgh 444</span><span class=e>&nbsp;</span></div>
    
    
    <div  class="portlet light" >
    	<div class="portlet-title">
            <div class="caption"><?php echo $module['jzdc_table_name']?></div>

            <div class="actions">
                <span><a href="<?php echo $module['action_url'];?>&act=export" target="_blank" class="submit">导出订单</a></span>

                <!--
                <span id=state_select></span>
                <div class="btn-group">
                    <a class="btn" href="javascript:;" data-toggle="dropdown"><i class="fa fa-check-circle"></i><?php echo self::$language['operation']?><?php echo self::$language['selected']?><i class="fa fa-angle-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="#" class="del" onclick="return del_select();"><?php echo self::$language['del']?></a></li> 

                        
                    </ul>
                </div>-->
            </div>
    	</div>
    
        <div class="m_row"><div class="half"><div class="dataTables_length"><select class="form-control" id="page_size" ><option value="10">10</option><option value="20">20</option><option value="50">50</option><option value="100">100</option></select> <?php echo self::$language['per_page']?></m_label></div></div><div class="half"><div class="dataTables_filter"><m_label><?php echo self::$language['search']?>:<input type="search"  placeholder="<?php echo self::$language['goods_name']?>/<?php echo self::$language['order_number']?>/<?php echo self::$language['buyer']?><?php echo self::$language['username']?>/<?php echo self::$language['receiver_info'];?>" class="form-control" style="width:500px;" ></m_label></div></div></div>
		
    <div class="filter"><?php echo self::$language['content_filter']?>:
        <?php echo $module['filter']?>
    </div>
    <div class=sum_div>
		<?php echo self::$language['sum']?>：<span class=sum_value><?php echo $module['sum']['sum']?></span><?php echo self::$language['yuan']?>
        <span id=time_limit><span class=days><?php echo $module['days'];?></span>  <span class=start_time_span><?php echo self::$language['start_time']?></span><input type="text" id="start_time" name="start_time" value="<?php echo @$_GET['start_time'];?>"  onclick=show_datePicker(this.id,'date') onblur= hide_datePicker()  /> -
       <span class=end_time_span><?php echo self::$language['end_time']?></span><input type="text" id="end_time" name="end_time"  value="<?php echo @$_GET['end_time'];?>"  onclick=show_datePicker(this.id,'date') onblur= hide_datePicker()  /> <a href="#" onclick="return time_limit();" class="submit"><?php echo self::$language['submit']?></a></span>
    </div>
    
    </div>

    <div class="sort">
    	<div class="sort_inner">
        <div class=checkbox_td><input type="checkbox" group-checkable=1></div>
        <div class=goods_td><a href=#  title="<?php echo self::$language['order']?>" desc="add_time|desc" class="sorting"  asc="add_time|asc"><?php echo self::$language['time']?></a></div>
        <div class=preferential_td><a href=# title="<?php echo self::$language['order']?>" desc="actual_money|desc" class="sorting"  asc="actual_money|asc"><?php echo self::$language['money']?></a></div>
        <div class=state_td><a href=# title="<?php echo self::$language['order']?>" desc="state|desc" class="sorting"  asc="state|asc"><?php echo self::$language['state']?></a></div>
        <div class=operation_td><span class=operation_icon> </span><?php echo self::$language['operation']?></div>
        </div>
    </div>
    
    

	<?php echo $module['list']?>
    <?php echo $module['page']?>
    </div>



</div>
