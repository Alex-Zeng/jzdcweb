<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/12
 * Time: 14:27
 */
namespace app\common\model;

use think\Model;

class IndexArea extends Model{

    public function getAreaInfo($areaId = 0,$list = []){
        $result = $this->where(['id'=>$areaId])->field(['name','level','upid'])->find();
        if($result){
            $list[]  = $result['name'];
            return $this->getAreaInfo($result['upid'],$list);
        }
        return $list;
    }


}