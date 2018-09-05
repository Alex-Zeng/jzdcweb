<?php
namespace app\common\model;

use think\Model;

class SmProductSpecPrice extends Model{

	// 设置当前模型对应的完整数据表名称-分类规格表
    protected $table = 'sm_product_spec_price';


    /**
     * @desc 返回当前规格的价格列表
     * @param int $specId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getPriceDetail($specId = 0){
        $rows = $this->where(['spec_id'=>$specId,'is_deleted'=>0])->select();
        $return = [];
        foreach($rows as $row){
            $return[] = ["specPriceId"=>$row->id,"minOrderQty" => $row->min_order_qty,"maxOrderQty"=>$row->max_order_qty,"price" => getFormatPrice($row->price)];
        }
        return $return;
    }

}  