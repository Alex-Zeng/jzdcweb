$(function () {
    $('#add-form').bootstrapValidator({
        live: 'disabled',
        message: 'This value is not valid',
        fields: {
            title: {
                message: 'The username is not valid',
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
            link:{
                message: '链接无效',
                validators: {
                    notEmpty: {
                        message: '链接不能为空'
                    },
                    url: {
                        message: '格式不正确'
                    },
                }
            },
            file:{
                message: '请上传图片',
                validators: {
                    notEmpty: {
                        message: '请上传图片'
                    },
                }
            },
        }
    });

    $('#delete-modal').on('show.bs.modal', function (event) {
        var id = $(event.relatedTarget).data('id');
        //赋值
        $("#delete-request").data('id',id);
    });
    $("#add-file").fileinput({
        language: 'zh', //设置语言
        uploadUrl: '?s=admin/file/upload',  //上传地址
        showUpload: true, //是否显示上传按钮
        showRemove:true,
        dropZoneEnabled: false,
        showCaption: true,//是否显示标题
        allowedPreviewTypes: ['image'],
        allowedFileTypes: ['image'],
        allowedFileExtensions:  ['jpg', 'png','jpeg'],
        maxFileSize : 5120,
        maxFileCount: 1,
        uploadExtraData: function(previewId, index) {   //额外参数的关键点
            var obj = {};
            obj.type = 'banner';
            return obj;
        }
    });
    $('#add-file').on("fileuploaded", function(event, data) {
        if(data.response)
        {
            alert(data.response.data.filename);
            $('#add-upload-file').val(data.response.data.filename);
        }
    });

    $("#add-form").bootstrapValidator({
        live: 'disabled',
        message: 'This value is not valid',
        fields: {
            title: {
                message: 'The username is not valid',
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
            link:{
                message: '链接无效',
                validators: {
                    notEmpty: {
                        message: '链接不能为空'
                    },
                    url: {
                        message: '格式不正确'
                    },
                }
            },
            path:{
                message: '请上传图片',
                validators: {
                    notEmpty: {
                        message: '请上传图片'
                    },
                }
            },
        }
    });

    $("#add-request").click(function () {
        //验证数据
        $('#add-form').bootstrapValidator('validate');
        //获取数据
        if ($("#add-form").data('bootstrapValidator').isValid()) {
            //数据提交
            $.ajax({
                type: 'post',
                url: '?s=admin/banner/create',
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
            url:'?s=admin/banner/get&id='+id,
            data:{},
            dataType:'json',
            success:function (data) {
                $("#edit-request").data('id',id);
                $("#edit-form").find('input[name="title"]').val(data.data.title);
                $("#edit-form").find('input[name="link"]').val(data.data.link);
                $("#edit-form").find('select[name="type"]').val(data.data.type);
                $("#edit-form").find('select[name="target"]').val(data.data.target);
                $("#edit-form").find('select[name="status"]').val(data.data.status);
                $("#edit-form").find('input[name="sequence"]').val(data.data.sequence);
                $("#edit-upload-file").val(data.data.path);
                var imgTag = "<img src='"+data.data.preview+"' class='file-preview-image' alt='' title=''>";
                //初始化图片
                $("#edit-file").fileinput({
                    language: 'zh', //设置语言
                    uploadUrl: '?s=admin/file/upload',  //上传地址
                    showUpload: true, //是否显示上传按钮
                    showRemove:true,
                    dropZoneEnabled: false,
                    showCaption: true,//是否显示标题
                    allowedPreviewTypes: ['image'],
                    allowedFileTypes: ['image'],
                    allowedFileExtensions:  ['jpg', 'png','jpeg'],
                    maxFileSize : 5120,
                    maxFileCount: 1,
                    initialPreview: [imgTag],
                    previewSettings: {
                        image: {width: "100px", height: "100px"},
                    },
                    // maxImageWidth: '1920px',
                    // maxImageHeight: '520px',
                    uploadExtraData: function(previewId, index) {   //额外参数的关键点
                        var obj = {};
                        obj.type = 'banner';
                        return obj;
                    }
                });
                $('#edit-file').on("fileuploaded", function(event, data) {
                    if(data.response)
                    {
                        alert(data.response.data.filename);
                        $('#edit-upload-file').val(data.response.data.filename);
                    }
                });
            }
        });
    });

    $('#edit-form').bootstrapValidator({
        live: 'disabled',
        message: 'This value is not valid',
        fields: {
            title: {
                message: 'The username is not valid',
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
            link:{
                message: '链接无效',
                validators: {
                    notEmpty: {
                        message: '链接不能为空'
                    },
                    url: {
                        message: '格式不正确'
                    },
                }
            },
            path:{
                message: '请上传图片',
                validators: {
                    notEmpty: {
                        message: '请上传图片'
                    },
                }
            },
        }
    });

    $("#edit-request").click(function () {
        //验证数据
        $('#edit-form').bootstrapValidator('validate');
        var id = $(this).data('id');
        //获取数据
        //数据提交
        if ($("#edit-form").data('bootstrapValidator').isValid()) {
            $.ajax({
                type: 'post',
                url: '?s=admin/banner/edit&id=' + id,
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

    $('#delete-modal').on('show.bs.modal', function (event) {
        var id = $(event.relatedTarget).data('id');
        $("#delete-request").data('id',id);
    });

    $("#delete-request").click(function () {
        var id = $(this).data('id');
        if(id < 0){
            return;
        }
        $.ajax({
            type:'post',
            url:'?s=admin/banner/delete&id='+id,
            data:{},
            dataType:'json',
            success:function (data) {
                if(data.status == 0){
                    window.location.reload();
                }else{
                    alert(data.msg);
                }
            }
        })
    });



});