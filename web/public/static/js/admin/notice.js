$(function () {
    var ckeditor = CKEDITOR.replace('add-content');
    var ckeditor2 = CKEDITOR.replace('edit-content');

    $('#add-form').bootstrapValidator({
        live: 'disabled',
        message: 'This value is not valid',
        fields: {
            title: {
                message: '标题不能为空',
                validators: {
                    notEmpty: {
                        message: '标题不能为空'
                    },
                    stringLength: {
                        min: 2,
                        max: 255,
                        message: '最大长度为255个字符'
                    },
                }
            },
            summary: {
                validators: {
                    notEmpty: {
                        message: '摘要不能为空'
                    }
                }
            },
            content:{
                validators: {
                    notEmpty: {
                        message: '正文内容不能为空'
                    }
                }
            },
        }
    });
    $('#edit-form').bootstrapValidator({
        live: 'disabled',
        message: 'This value is not valid',
        fields: {
            title: {
                message: '标题不能为空',
                validators: {
                    notEmpty: {
                        message: '标题不能为空'
                    },
                    stringLength: {
                        min: 2,
                        max: 255,
                        message: '最大长度为255个字符'
                    },
                }
            },
            summary: {
                validators: {
                    notEmpty: {
                        message: '摘要不能为空'
                    }
                }
            },
            content:{
                validators: {
                    notEmpty: {
                        message: '正文内容不能为空'
                    }
                }
            },
        }
    });
    $("#add-request").click(function () {
        //验证数据
        $('#add-form').bootstrapValidator('validate');
        ckeditor.updateElement();
        //数据提交
        if ($("#add-form").data('bootstrapValidator').isValid()) {
            $.ajax({
                type: 'post',
                url: '?s=admin/notice/create',
                data: $('#add-form').serialize(),
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
    $('#edit-modal').on('show.bs.modal', function (event) {
        var id = $(event.relatedTarget).data('id');
        $.ajax({
            url:'?s=admin/notice/get&id='+id,
            data:{},
            dataType:'json',
            success:function (data) {
                $("#edit-request").data('id',id);
                $("#edit-form").find('input[name="title"]').val(data.data.title);
                $("#edit-form").find('textarea[name="summary"]').val(data.data.summary);
                $("#edit-form").find('select[name="status"]').val(data.data.status);
              //  $("#edit-content").html(data.data.content);
                ckeditor2.setData(data.data.content);
            }
        });
    });
    $("#edit-request").click(function () {
        //验证数据
        $('#edit-form').bootstrapValidator('validate');
        var id = $(this).data('id');
        ckeditor2.updateElement();
        //数据提交
        if ($("#edit-form").data('bootstrapValidator').isValid()) {
            $.ajax({
                type: 'post',
                url: '?s=admin/notice/edit&id=' + id,
                data: $('#edit-form').serialize(),
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
    
    $('#info-modal').on('show.bs.modal',function (event) {
        var id = $(event.relatedTarget).data('id');
        $.ajax({
            url:'?s=admin/notice/get&id='+id,
            data:{},
            dataType:'json',
            success:function (data) {
                $('#info-modal').find('.modal-body').html(data.data.content);
            }
        });
    });
})