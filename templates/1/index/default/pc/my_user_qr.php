<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
	<script>
    $(document).ready(function(){
            
    });
    </script>
    

    <style>
    #<?php echo $module['module_name'];?>{ padding-top:2rem; text-align:center;}
    #<?php echo $module['module_name'];?> .bar_img_div{ text-align:center;}
    #<?php echo $module['module_name'];?> .bar_img_div img{ width:180px; height:60px;}
    #<?php echo $module['module_name'];?> .qr_img_div{ text-align:center;}
    #<?php echo $module['module_name'];?> .qr_img_div img{ width:250px;}
	#<?php echo $module['module_name'];?> .title{ font-size:1.4rem; font-weight:bold; text-align:center; line-height:3rem;}
    </style>
    <div id="<?php echo $module['module_name'];?>_html">
    	<div class=title><?php echo self::$language['pages']['index.my_user_qr']['name']?></div>
    	<div class=bar_img_div>
        	<img src='./plugin/barcode/buildcode.php?codebar=BCGcode128&text=<?php echo $module['qr_text'];?>' />
        </div>
        <div class=qr_img_div>
        	<img src='./plugin/qrcode/index.php?text=<?php echo $module['qr_text'];?>&logo=1' />
        </div>
        
    </div>
</div>