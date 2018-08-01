$(function () {
    $('#refuse-form').bootstrapValidator({
        live: 'disabled',
        message: 'This value is not valid',
        fields: {
            reason: {
                message: 'The reason is not valid',
                validators: {
                    notEmpty: {
                        message: '拒绝原因不能为空'
                    },
                    stringLength: {
                        min: 2,
                        max: 200,
                        message: '最大长度为255个字符'
                    },
                }
            },
        }
    });

    $('#refuse-modal').on('show.bs.modal', function (event) {
        var id = $(event.relatedTarget).attr('data-id');
        //赋值
        $("#refuse-request").data('id',id);
    });

    $("#refuse-request").click(function () {
        //验证数据
        $('#refuse-form').bootstrapValidator('validate');
        var id = $(this).data('id');
        //数据提交
        if ($("#refuse-form").data('bootstrapValidator').isValid()) {
            $.ajax({
                type: 'post',
                url: '?s=admin/certification/refuse',
                data: {id:id,reason:$('textarea[name="reason"]').val()},
                dataType: 'json',
                success: function (data) {
                    if (data.status == 0) {
                        window.location.reload();
                    } else {
                        alert(data.msg);
                    }
                }
            });
        }
    });

    $('#confirm-modal').on('show.bs.modal', function (event) {
        var id = $(event.relatedTarget).data('id');
        //赋值
        $("#confirm-request").data('id',id);
    });
    $("#confirm-request").click(function () {
        //验证数据
        var id = $(this).data('id');
        if(id <= 0){
            return;
        }
        //数据提交
        $.ajax({
            type: 'post',
            url: '?s=admin/certification/confirm',
            data: {id:id},
            dataType: 'json',
            success: function (data) {
                if (data.status == 0) {
                    window.location.reload();
                } else {
                    alert(data.msg);
                }
            }
        });
    });
});