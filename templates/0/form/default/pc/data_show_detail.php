<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
    
    
    <script>
    $(document).ready(function(){
		$(".load_js_span").each(function(index, element) {
            $(this).load($(this).attr('src'));
        });
        $("#submit_pass").click(function(){
            submit_pass();
            return false;
        });

        $("#submit_refuse").click(function(){
            submit_refuse();
            return false;
        });

		$.get("<?php echo $module['count_url']?>");
    });

    function submit_pass(){

        $.post("<?php echo $module['action_url'];?>&act=pass",obj,function(data){
            $("#<?php echo $module['module_name'];?> #submit").next('span').html('');
            //alert(data);
            try{v=eval("("+data+")");}catch(exception){alert(data);}

            if(v.state=='fail'){
                alert(v.info);
            }else{
                $("#<?php echo $module['module_name'];?>_html").css('text-align','center');
                $("#<?php echo $module['module_name'];?>_html").html(v.info);

                $("#submit_refuse").hide();
                $("#submit_pass").hide();
            }

        });
    }

    function submit_refuse(){
        reason = prompt("请输入拒绝理由:","");
        if (reason != null) {
            $.post("<?php echo $module['action_url'];?>&act=refuse",{reason:reason},function(data){
                $("#<?php echo $module['module_name'];?> #submit").next('span').html('');
                //alert(data);
                try{v=eval("("+data+")");}catch(exception){alert(data);}

                if(v.state=='fail'){
                    alert(v.info);
                }else{
                    $("#<?php echo $module['module_name'];?>_html").css('text-align','center');
                    $("#<?php echo $module['module_name'];?>_html").html(v.info);

                    $("#submit_refuse").hide();
                    $("#submit_pass").hide();
                }

            });
        }
    }
        
    </script>

	<style>
    #<?php echo $module['module_name'];?>{}
    #<?php echo $module['module_name'];?> div{ line-height:3rem;}
    #<?php echo $module['module_name'];?> .m_label{ display:inline-block; width:14%; text-align:right; overflow:hidden; padding-right:5px; opacity:0.4;}
    #<?php echo $module['module_name'];?> .input_span{ display:inline-block; width:85%; overflow:hidden;}
    #<?php echo $module['module_name'];?> legend{ }
    #<?php echo $module['module_name'];?> .img_thumb{ max-height:12rem; margin:10px;}
    </style>
    <div id="<?php echo $module['module_name'];?>_html">
    <?php echo $module['fields'];?>
    </div>
<div><span class=m_label> </span>
    <?php echo $module['buttons'];?>

</div>
</div>
