<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
    <script>
    $(document).ready(function(){
		if(get_param('type')!=''){$("#<?php echo $module['module_name'];?> #type_filter").val(get_param('type'));}		
		$("#<?php echo $module['module_name'];?>_html .print_set .print").click(function(){
			window.print();
			return false;	
		});
		
		$("#<?php echo $module['module_name'];?>_html .print_set .submit").click(function(){
			if($("#<?php echo $module['module_name'];?> .print_set .min").val()==''){alert('<?php echo self::$language['is_null']?>');}
			if($("#<?php echo $module['module_name'];?> .print_set .max").val()==''){alert('<?php echo self::$language['is_null']?>');}
			window.location.href='./index.php?jzdc=mall.goods_label&min='+$("#<?php echo $module['module_name'];?> .print_set .min").val()+'&max='+$("#<?php echo $module['module_name'];?> .print_set .max").val()+"&search="+$("#<?php echo $module['module_name'];?> .print_set #search_filter").val()+"&type="+$("#<?php echo $module['module_name'];?> .print_set #type_filter").val();
			return false;	
		});
		
		
		
    });
	
    </script>
	<style>
    #<?php echo $module['module_name'];?>{} 
    #<?php echo $module['module_name'];?>_html{}
    #<?php echo $module['module_name'];?>_html .print_set{ padding-bottom:10px; }
    #<?php echo $module['module_name'];?>_html .print_set input{ width:3rem; text-align:center;}
    #<?php echo $module['module_name'];?>_html .print_set div{ display:inline-block; vertical-align:top; }
    #<?php echo $module['module_name'];?>_html .print_set .p_left{ width:80%;}
    #<?php echo $module['module_name'];?>_html .print_set .p_right{ text-align:right;width:20%;}
    #<?php echo $module['module_name'];?>_html .print_set .submit{ }
	
	#<?php echo $module['module_name'];?>_html .print_content{ width:200px; margin:0px; padding:0px;  text-align:center; margin:auto;}
	#<?php echo $module['module_name'];?>_html .print_content .goods{ display:block; text-align:center;width:200px; height:120px; overflow:hidden;margin:0px; padding:0px; margin:auto; }
	#<?php echo $module['module_name'];?>_html .print_content .goods img{ width:90%; height:70px; padding-top:5px;}
	#<?php echo $module['module_name'];?>_html .print_content .goods span{ display:block;font-size:1.3rem; }
	
	
   
   @media print {
	   body div{ padding:0px; margin:0px;}
	   body,#<?php echo $module['module_name'];?>,#<?php echo $module['module_name'];?>_html{ width:200px;overflow:hidden; margin:auto; text-align:center;}
	   #<?php echo $module['module_name'];?>{ margin:0px; padding:0px;}
	   #<?php echo $module['module_name'];?>_html{ margin:0px; padding:0px;}
	   #<?php echo $module['module_name'];?>_html .print_set{ display:none;}
	   #<?php echo $module['module_name'];?>_html .print_content{ width:200px; }
	}
    </style>
    <div id="<?php echo $module['module_name'];?>_html" jzdc-table=1>
        
    	<div class=print_set>
        <div class=p_left>
            <div class="filter"><?php echo self::$language['content_filter']?>:<?php echo $module['filter']?> <input type=text id=search_filter name=search_filter value="<?php echo @$_GET['search']?>" placeholder="<?php echo self::$language['search']?>" style="width:100px; margin-right:40px;" /></div>
        
        <input type="text" value="<?php echo @$_GET['min']?>" class=min /> - <input type="text" value="<?php echo @$_GET['max']?>" class=max /> <?php echo self::$language['bar_code_length']?> <a href=# class=submit><?php echo self::$language['inquiry']?></a>
        </div><div class=p_right><a href=# class="add print"><?php echo self::$language['print']?></a></div>
        </div>
    	<div class=print_content><?php echo $module['list'];?></div>
        
    </div>
</div>
