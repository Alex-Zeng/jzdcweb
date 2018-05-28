<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
	<script>
    $(document).ready(function(){
            
    });
    </script>
    

    <style>
    #<?php echo $module['module_name'];?>{ padding-top:2rem;}
    #<?php echo $module['module_name'];?> .qr_img_div{ text-align:center;}
    #<?php echo $module['module_name'];?> .qr_img_div img{ width:250px;}
    #<?php echo $module['module_name'];?> .url_div{ margin-top:2rem; margin-bottom:2rem; text-align:center;}
    #<?php echo $module['module_name'];?> .url_div a:hover{ color:#F60;}
    
    </style>
    <div id="<?php echo $module['module_name'];?>_html">
        <div class=qr_img_div>
        	<div><?php echo self::$language['wechat_scan'];?></div>
        	<img src='./plugin/qrcode/index.php?text=<?php echo $module['qr_text'];?>&logo=1' />
        </div>
        <div class=url_div><?php echo self::$language['reg_user'];?><?php echo self::$language['url']?>: <a href=<?php echo $module['reg_url'];?> target=_blank><?php echo $module['reg_url'];?></a>
        <br /> <br />
        <a href=./index.php?jzdc=index.my_new_user class=submit><?php echo self::$language['view']?><?php echo self::$language['pages']['index.my_new_user']['name']?></a>
        </div>
        
    </div>
</div>