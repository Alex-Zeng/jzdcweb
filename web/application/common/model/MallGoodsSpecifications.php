<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/11
 * Time: 14:24
 */

namespace app\common\model;

use think\Model;

class MallGoodsSpecifications extends Model{


    /**
     * @desc
     * @param $goodsId
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getInfosByGoodsId($goodsId){
        $rows = $this->where(['goods_id'=>$goodsId])->select();
        return $rows;
    }

    public static function getFormatPath($path){
        return    config('jzdc_domain').config('jzdc_static_path').'/uploads/goods_color/'.$path;
    }


}
