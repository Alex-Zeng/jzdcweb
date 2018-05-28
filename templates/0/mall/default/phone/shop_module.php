<div id=<?php echo $module['module_name'];?> save_name="<?php echo $module['module_save_name'];?>"  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>"   goods_module="<?php echo $module['module_diy']?>" align=left >
    <script>
    $(document).ready(function(){
    });
    
    </script>
    <style>
	#<?php echo $module['module_name'];?>{ width:<?php echo $module['module_width'];?>; height:<?php echo $module['module_height'];?>;display:inline-block; vertical-align:top;vertical-align:top; overflow:hidden; padding:0px !important; margin-bottom:1rem;border:1px solid #e7e7e7;   border:0px; background:none;}
	#<?php echo $module['module_name'];?>_html {}
	#<?php echo $module['module_name'];?>_html .module_title { text-align:center;  line-height:40px; font-size:1.2rem; font-weight:bold;}
	#<?php echo $module['module_name'];?>_html .module_title .name{ margin:auto; display:inline-block;}
	#<?php echo $module['module_name'];?>_html .module_title .more{ display:none;}
	#<?php echo $module['module_name'];?>_html .list{ background-color:#fff;}
	#<?php echo $module['module_name'];?>_html .list a{ display:inline-block; vertical-align:top; width:31.4%; margin:0.8%; overflow:hidden; text-align:center;}
	#<?php echo $module['module_name'];?>_html .list a img{ width:90%; border:1px solid #F0F0F0; width:100px; height:100px;}
	#<?php echo $module['module_name'];?>_html .list a img:hover{ opacity:0.8;}
	#<?php echo $module['module_name'];?>_html .list a span{ display:block; line-height:30px; font-size:1.1rem;}
	#<?php echo $module['module_name'];?>_html .list{}
	
	
	
    </style>
	<div id="<?php echo $module['module_name'];?>_html"  jzdc-table=1>
    	<div class=module_title style=" display:<?php echo $module['title_show'];?>; "><a class=name href=<?php echo $module['title_link']?>  target="<?php echo $module['target'];?>"><?php echo $module['title'];?></a><a class=more href=<?php echo $module['title_link']?> target="<?php echo $module['target'];?>"><?php echo self::$language['more'];?></a></div>

    	<div class=list><?php echo $module['list']?></div>
        
    </div>

</div>