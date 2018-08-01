<?php
namespace app\admin\validate;

use think\validate;

class Version extends validate
{

    protected $rule=[
        'version_id'     => 'require|gt:0',
        'title'          => 'require|max:20',
        'app_name'       => 'require',
        'force_version'  => 'require',
        'content'       => 'require',
        'up_time'       => 'require',
        'up_time'       => 'date'
    ];
    protected $message = [
        'version_id.require'    => '版本标识不能为空',
        'version_id.gt'         => '版本标识必须大于0',
        'title.require'         => '标题不能为空',
        'title.max'             => '标题长度不能超过20个字符',
        'app_name.require'      => '版本名称不能为空',
        'force_version.require' => '强制版本号不能为空',
        'content.require'       => '版本修改内容不能为空',
        'up_time.require'       => '上线时间不能为空',
        'up_time.date'          => '上线格式不正确'
    ];
    protected $scene = [
        'add' =>['title','app_name','force_version','content','up_time'],
        'edit' =>['version_id','title','app_name','force_version','content','up_time']
    ];
}