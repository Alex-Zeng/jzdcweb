<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/18
 * Time: 16:26
 */
namespace app\common\model;

use think\Model;

class MallType extends Model{

    /**
     * @desc 根据父类返回子类ID数组
     * @param int $parentId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTypeIds($parentId = 0){
        $where = [
           'parent' => $parentId,
           'visible' => 1,
        ];
        $rows = $this->where($where)->order('sequence','desc')->field(['id','name'])->select();
        $idArr = [];
        foreach ($rows as $row){
            $idArr[] = $row->id;
        }
        return $idArr;
    }

    /**
     * @desc 返回所有有效的物品类别ID数组
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getAllIds(){
        $where = [
            'visible'=> 1
        ];
        $rows = $this->where($where)->order('sequence','desc')->field(['id','name'])->select();
        $idArr = [];
        foreach ($rows as $row){
            $idArr[] = $row->id;
        }
        return $idArr;
    }

    /**
     * @desc返回图标
     * @param $icon
     * @return string
     */
    public static function getFormatIcon($icon){
        return config('jzdc_domain').'/web/public/uploads/type_icon/'.$icon;
    }

}