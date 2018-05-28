<div id=<?php echo $module['module_name'];?>  class="portlet light" jzdc-module="<?php echo $module['module_name'];?>" align=left >
    <script>
    $(document).ready(function(){
    });
    </script>
    

	<style>
    #statistics{ text-align:center; line-height:150px;}
    #user_money{ font-size:50px; font-weight:bold;}
    #user_money span{font-size:30px; font-weight:bold; padding:20px; display:inline-block;}
	
    </style>
    
    <div id=<?php echo $module['module_name'];?>_html>
    <div id=statistics>
        <span id=user_money><?php echo self::$language['user_money'];?>:<span><?php echo $module['user_money'];?></span></span>
    </div>
    
      <?php echo $module['list']?>
    </div>
</div>