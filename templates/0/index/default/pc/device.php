<div id=<?php echo $module['module_name'];?> jzdc-module="<?php echo $module['module_name'];?>" >
<script>
$(document).ready(function(){
	$("#<?php echo $module['module_name'];?>_html #pc,#<?php echo $module['module_name'];?>_html #phone").click(function(){
		url=window.location.href;
		url=replace_get(url,'jzdc_device',$(this).attr('id'));
		window.location.href=url;		
		return false;		
	});	
	$("#<?php echo $module['module_name'];?>_html #"+getCookie('jzdc_device')).addClass('a_current');
	
	
        
    });
    </script>
    

	<style>
	.weixin_share{display:none;}
    #<?php echo $module['module_name'];?>_html{ line-height:2rem; text-align:center; }
	#<?php echo $module['module_name'];?>_html a{ }
    #<?php echo $module['module_name'];?>_html .a_current{ }
	#<?php echo $module['module_name'];?>_html .power_by{  display:inline-block; font-size:0.9rem;}
	#<?php echo $module['module_name'];?>_html .power_by a{ font-size:0.9rem;padding:0px;}
	#<?php echo $module['module_name'];?>_html a:hover{ font-weight:bold;}
    </style>
    <div id="<?php echo $module['module_name'];?>_html" class="container">
<!--<a href=./sitemap.php class=sitemap></a>
	<a href="#" id='phone'><?php echo self::$language['phone_device'];?></a>
	<a href="#" id='pc'><?php echo self::$language['pc_device'];?></a> -->
    <div class=power_by>Powered by <a href="http://www.jizhongdiancai.com" target="_blank">JZDC</a></div>
<style>#newBridge{ display:none !important;}</style>
<script>
var _hmt = _hmt || [];
(function() {
  var hm = document.createElement("script");
  hm.src = "https://hm.baidu.com/hm.js?1855a8b277d9adf2d47a33240152d9c9";
  var s = document.getElementsByTagName("script")[0]; 
  s.parentNode.insertBefore(hm, s);
})();
</script>
    </div>
</div>