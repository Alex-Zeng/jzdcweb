<?php
namespace app\admin\validate;

use think\validate;

class IndexUser extends validate
{

    protected $rule=[
        'username'=>'require',
        'password'=>'require'
    ];
    protected $message = [
        'username.require'   => '账号必填',
        'password.require'     => '密码必填'
    ];
    protected $scene = [
        'login' =>['username','password']
    ];

}