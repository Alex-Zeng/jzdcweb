window.onload = function (){
    //异步提交
    $('.submit').click(function(){
        var option = {  
            type:'post', 
            dataType:'json', 
            url:$('.formUp').attr('action'),
            success:function(data){
                layer.close(shade);
                returnData(data);
            },  
            error:function(XmlHttpRequest,textStatus,errorThrown){  
                // console.log(XmlHttpRequest);  
                // console.log(textStatus);  
                // console.log(errorThrown);  
            } 
            
        }
        if (typeof beforeSubmit == "function" && option.beforeSubmit == undefined) {
            option.beforeSubmit = beforeSubmit;
        }
        var shade = '';
        if($('.formUp').hasClass('shade')){
            shade = layer.load(1,{shade: [0.3,'#000']});
        }
        $('.formUp').ajaxSubmit(option);
        return false;  
    });
}

/**
 * [returnData 异步返回数据统一处理]
 * @param  {[type]} data [description]
 * @return {[type]}      [description]
 */
function returnData(data){
    var dt = data;
    if(dt.code > 0){
        layer.alert(dt.data.msg, {icon: 2});
        if($('#captcha').length > 0){
            var captcha_src =  $('#captcha').attr('src');
            $('#captcha').attr('src',captcha_src+'?'+Math.random());
        }
    }else if(dt.code == 0){
        if(dt.data.msg != ''){
            var open = layer.open({
                            icon:1,
                            content: dt.data.msg,
                            btn: ['确定'],
                            yes: function(index, layero){
                                layer.close(open);
                                switch(dt.data.url){
                                    case 'reload': //刷新本页面
                                        window.location.reload();
                                        break;
                                    case 'noSkip': //只关闭弹窗，不做其他操作
                                        break;
                                    case 'back': //返回上一页
                                        window.history.go(-1);
                                        break;
                                    default: //跳转页面
                                        window.location.href = dt.data.url;
                                        break;
                                }
                            },
                            cancel: function() {
                                layer.close(open);
                                window.location.href = dt.data.url;
                            }
                        });
        }else{
            window.location.href = dt.data.url;
        }
        
    }else if(dt.indexOf("html") >= 0){
        $('html').html(dt);
    }
}