<?php
namespace app\common\model;

use think\Model;

class SmProduct extends Base{

	// 设置当前模型对应的完整数据表名称-分类规格表
    protected $table = 'sm_product';

    const STATE_FORSALE = 1;   //上架
    const STATE_SOLDOUT = 2;  //下架

    const AUDIT_PENDING = 2;  //未审核
    const AUDIT_RELEASED = 5; //已发布
    const AUDIT_SAVED = 1;    //保存的
    const AUDIT_APPROVED = 3; //已审核通过的
    const AUDIT_NOTAPPROVED = 4;  //审核不通过的

    /**
     * @desc 返回格式化产品图片路径
     * @param $img
     * @return string
     */
    public static function getFormatImg($img){
        return $img ? config('jzdc_doc_path').'/goods_thumb/'.$img : '';
    }

    /**
     * @desc 返回详细图
     * @param $img
     * @return string
     */
    public static function getFormatMultiImg($img){
        return $img ? config('jzdc_domain').config('jzdc_doc_path').'/goods/'.$img : '';
    }


    /**
     * [getAuditState 获取商品状态描述]
     * @param  [type] $id [商品状态]
     * @return [type]     [description]
     */
    public function getAuditState($id){
        $auditState = [
            '1' => '草稿',
            '2' => '待审核',
            '3' => '审核通过',
            '4' => '审核不通过',
            '5' => '发布'
        ];
        return isset($auditState[$id])?$auditState[$id]:'';
    }
}  