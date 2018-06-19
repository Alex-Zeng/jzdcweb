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

    /**
     * @desc 返回省份列表
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getProvinceList(){
        $rows = $this->where(['upid' => 45067,'level'=>2])->field(['id','name','level'])->select();
        return $rows;
    }

    /**
     * @desc 返回省城市列表
     * @param $provinceId
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCityListByProvince($provinceId){
        $rows = $this->where(['upid' => $provinceId,'level'=>3])->field(['id','name','level'])->select();
        return $rows;
    }

    /**
     * @desc 返回区县
     * @param int $cityId
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCountyListByCity($cityId = 0){
        $rows = $this->where(['upid' => $cityId,'level'=>4])->field(['id','name','level'])->select();
        return $rows;
    }

    /**
     * @desc 返回镇
     * @param int $countyId
     * @return false|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getTownListByCounty($countyId = 0){
        $rows = $this->where(['upid' => $countyId,'level'=>5])->field(['id','name','level'])->select();
        return $rows;
    }


}