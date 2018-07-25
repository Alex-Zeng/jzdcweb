<?php
namespace app\api\controller;

use app\common\model\IndexArea;
use app\common\model\MallOrder;
use think\Request;

class Index extends Base
{
    public function index(Request $request)
    {

//        $auth = $this->auth();
//        print_r($auth);

    }

    /**
     * @desc 返回区域数据
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getArea(Request $request){
        $model = new IndexArea();
        $list = [];
        //获取省级
        $rows = $model->getProvinceList();
        foreach ($rows as $row){
            $list[] = ['name'=>$row->name,'value'=>trim($row->id)];
            $cityRows = $model->getCityListByProvince($row->id);
            foreach($cityRows as $cityRow){
                $list[] = ['name'=>$cityRow->name,'value'=>trim($cityRow->id),'parent'=>trim($cityRow->upid)];
                $countyRows = $model->getCountyListByCity($cityRow->id);
                foreach ($countyRows as $countyRow){
                    $list[] = ['name'=>$countyRow->name,'value'=>trim($countyRow->id),'parent'=>trim($countyRow->upid)];
//                    $townRows = $model->getTownListByCounty($countyRow->id);
//                    foreach ($townRows as $townRow){
//                        $list[] = ['name'=>$townRow->name,'value'=>trim($townRow->id),'parent'=>trim($townRow->upid)];
//                    }
                }
            }

        }
        return ['status'=>0,'data'=>['list'=>$list],'msg'=>''];
    }

    /**
     * @desc 返回层级区域数据
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getLevelArea(Request $request){
        $model = new IndexArea();
        $provinceId = $request->post('provinceId', 0, 'intval');
        $cityId = $request->post('cityId', 0, 'intval');

        if($provinceId === 0 && $cityId === 0) {
            $list =  $model->getProvinceList();
            return ['status' => 0, 'data' => ['list' => $list], 'msg' => '返回成功'];
        }

        if ($cityId === 0) {
            $list = $model->getCityListByProvince($provinceId);
            return ['status' => 0, 'data' => ['list' => $list], 'msg' => '返回成功'];
        }else{
            $list = $model->getCountyListByCity($cityId);
            return ['status' => 0, 'data' => ['list' => $list], 'msg' => '返回成功'];
        }
    }

    /**
     * [turnover 成交额]
     * @return [json] [格式化后的本月成交额&累计成交额]
     */
    public function turnover(){
        $model = new MallOrder();
        $turnoverMonth = number_format($model->getTurnover('month'),0,',',',');//本月成交额
        $turnoverAll = number_format($model->getTurnover('all'),0,',',' ');//累计成交额
        return ['status'=>0,'data'=>['turnoverMonth'=>$turnoverMonth,'turnoverAll'=>$turnoverAll],'msg'=>'返回成功'];
    }


    //获取首页推荐分类及推荐商品
    public function getPushTypeAndGoods(){
        $mallType = model('mall_type');
        $mallGoods = model('mall_goods');

        //获取首级推荐分类
        $dataType = $mallType->field('id,name')->where(['push'=>['>',0],'parent'=>0])->order('sequence','desc')->select();

        foreach ($dataType as $key => $val) {
            //获取二级推荐分类
            $dataType[$key]['pushTypeList'] = $mallType->field('id,name')->where(['push'=>['>',0],'parent'=>$val['id']])->order('sequence','desc')->select();

            //获取该首级分类及其子类的所以商品推荐   
            $ids = $mallType->getChildIds($val['id'],true);
            $dataGoods = $mallGoods->field('id,icon,min_price,max_price,title')->where(['id'=>['in',$ids],'push'=>['>',0],'state'=>2])->order('push','desc')->select();
            foreach ($dataGoods as $k => $v) {
                $dataGoods[$k]['icon'] = $mallGoods::getFormatImg($v['icon']);
                $dataGoods[$k]['min_price'] = getFormatPrice($v['min_price']);
                $dataGoods[$k]['max_price'] = getFormatPrice($v['max_price']);
            }
            $dataType[$key]['pushGoodsList'] = $dataGoods;
        }

        return $dataType;
    }
}
