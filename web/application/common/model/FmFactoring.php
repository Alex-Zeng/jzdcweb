<?php
namespace app\common\model;

use think\Model;

class FmFactoring extends Base{

	protected $table = 'jzdc_factoring';

	/**
     * [getStateName 获取状态描述]
     * @param  [type] $id [商品状态]
     * @return [type]     [description]
     */
    public function getStateName($id){
        $state = [
            '1' => '待审核',
            '2' => '审核未通过',
            '3' => '待放款',
            '4' => '已放款'
        ];
        return isset($state[$id])?$state[$id]:'';
    }
}