<?php
namespace app\api\validate;

use think\validate;

class Factoring extends validate
{

    protected $rule=[
        'order_id'=>'require|gt:0',
        'contact_username'=>'require',
        'contact_phone'=>'require',
        'need_account'=>'require|max:99999999.99',
        'bank_corporate'=>'require|length:1,40',
        'bank_corporate_confirm'=>'require|confirm:bank_corporate',
        'bank_address'=>'require|length:1,50'
    ];
    protected $message = [
        'order_id.require'   => '订单必选',
        'order_id.gt'   => '订单选择有误',
        'contact_username.require'     => '联系人必填',
        'contact_phone.require'     => '联系电话必填',
        'need_account.require'     => '融资信息必填',
        'need_account.max'     => '融资金额超限，需要一亿以内',
        'bank_corporate.require'     => '对公账号必填',
        'bank_corporate.length'     => '对公账号长度最多40个字符',
        'bank_corporate_confirm.require'     => '再次输入对公账号必填',
        'bank_corporate_confirm.confirm'     => '两次输入对公账号不一致',
        'bank_address.require'     => '开户支行必填',
        'bank_address.length'     => '开户支行最多50个字符'
    ];
    protected $scene = [
        // 'add' =>[]
    ];

}