<?php
namespace app\api\controller;

use app\common\model\IndexArea;
use app\common\model\MallOrder;
use app\common\model\SmProduct;
use app\common\model\SmProductCategory;
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
     * @desc 根据版本号获取资源
     * @param Request $request
     * @return array
     */
    public function getJsonArea(Request $request){
        $url = config('jzdc_domain').'/web/public/static/doc/area.json';
        return ['status'=>0,'data'=>['url'=>$url,'version'=>config('VERSION_AREA')],'msg'=>''];
    }

    /**
     * [turnover 成交额]
     * @return [json] [格式化后的本月成交额&累计成交额]
     */
    public function turnover(){
        $model = new MallOrder();
        $turnoverMonth = number_format($model->getTurnover('month'),0,',',',');//本月成交额
        $turnoverAll = number_format($model->getTurnover('all'),0,',',',');//累计成交额
        return ['status'=>0,'data'=>['turnoverMonth'=>$turnoverMonth,'turnoverAll'=>$turnoverAll],'msg'=>'返回成功'];
    }


    //获取首页推荐分类及推荐商品
    public function getPushTypeAndGoods(){
        $productModel = new SmProduct();
        $productCategoryModel = new SmProductCategory();
        //获取推荐分类的ID
        $condition = ['a.is_recommended'=>1,'b.is_display'=>1,'a.state'=>SmProduct::STATE_FORSALE,'a.audit_state'=>SmProduct::AUDIT_RELEASED,'a.is_deleted'=>0];

        $pathRows = $productModel->alias('a')->join(['sm_product_category'=>'b'],'a.category_id=b.id','left')
            ->where($condition)
            ->field(['b.id','SUBSTRING_INDEX(b.depth_path, \'/\', 2) AS `path`'])
            ->group('path')
            ->select();
        //获取所有path的数据
        $pathIds = [];
        foreach ($pathRows as $pathRow){
            if(!$pathRow->path){
                continue;
            }
            $cateId = substr($pathRow->path,1,strlen($pathRow->path)-1);
            $pathIds[] = intval($cateId);
        }

        //排序取出前8条
        $categoryRows = $productCategoryModel->where(['id' => ['in',$pathIds]])->order('ordering desc')->limit(8)->field(['id'])->select(
        $dataType = [];

        //查询子类
        foreach ($categoryRows as $categoryRow){
            $categoryRow = $productCategoryModel->where(['id'=>$categoryRow->id])->find();
            //获取子集ID
            $cateAllId =  $productCategoryModel->getChildIds($categoryRow->id,true);

            $productResult =  $typeLists = [];
            $dataProducts = $productModel->where(['category_id'=>['in',$cateAllId],'is_recommended'=>1,'is_deleted'=>0])->order('created_time desc')->limit(8)->field(['id','min_price','max_price','title','is_price_neg_at_phone','cover_img_url'])->select();

            foreach($dataProducts as $dataProduct){
                $productResult[] =[
                    'id' =>(string) $dataProduct->id,
                    'title' => $dataProduct->title,
                    'min_price' => getFormatPrice($dataProduct->min_price),
                    'max_price' => getFormatPrice($dataProduct->max_price),
                    'icon' =>  SmProduct::getFormatImg($dataProduct->cover_img_url),
                    'isDiscussPrice' => getBinDecimal($dataProduct->is_price_neg_at_phone),
                    'showPrice' => getShowPrice(getBinDecimal($dataProduct->is_price_neg_at_phone),$dataProduct->min_price,$dataProduct->max_price)
                ];
            }

            $typeLists =  $productCategoryModel->where(['parent_id'=>$cateId,'is_display'=>1,'is_deleted'=>0])->order('ordering desc')->field(['id','name'])->select();
            foreach ($typeLists as &$typeList){
                $typeList->id = (string)$typeList->id;
            }

            $dataType[] = [
                'id' => $cateId,
                'name' => $categoryRow->name,
                'pushTypeList' =>$typeLists,
                'pushGoodsList' => $productResult
            ];
        }

        return ['status' => 0, 'data' => ['dataType' => $dataType], 'msg' => '返回成功'];
    }

    //版本更新
    public function versionUpdate(){
        //app版本号以后用 X.Y.Z 这种格式，大改版则改X，功能迭加则改Y，bug修复则改Z
        $version=input('post.version','','trim');  //用户版本
        $now=time();
        //获取最新app版本信息
        $fileArr=db('version')
                        ->field('app_name,force_version')
                        ->where("up_time<={$now} and is_del=1")
                        ->order('version_id desc')
                        ->find();
        if(!$fileArr){
            return ['status'=>0,'data'=>['url'=>'','forced'=>0,'tips'=>'不需要版本更新'],'msg'=>'请求成功'];
        }

        //数据库版本
        $explodeNowVersion = explode('.', strtr($fileArr['app_name'],['jzdc_'=>'','jizhongdiancai_'=>'','.apk'=>'']));
        foreach ($explodeNowVersion as $key => $value) {
            if($key>0){
                $explodeNowVersion[$key] = str_pad($value, 2, "0", STR_PAD_LEFT);
            }
        }
        $nowVersion = implode('', $explodeNowVersion);
       
        //用户所用版本
        $explodeVersion = explode('.', $version);
        foreach ($explodeVersion as $key => $value) {
            if($key>0){
                $explodeVersion[$key] = str_pad($value, 2, "0", STR_PAD_LEFT);
            }
        }
        $version = implode('', $explodeVersion);

        //强制更新版本
        $explodeForceVersion = explode('.', $fileArr['force_version']);
        foreach ($explodeForceVersion as $key => $value) {
            if($key>0){
                $explodeForceVersion[$key] = str_pad($value, 2, "0", STR_PAD_LEFT);
            }
        }
        $forceVersion = implode('', $explodeForceVersion);
        
        // dump($version);dump($nowVersion);dump($forceVersion);exit();
        if($version < $nowVersion){
            if($version<$forceVersion){  //是否强制更新
                $forced=1;//强制
            }else{
                $forced=2;//需要更新,不强制
            }
            return ['status'=>0,'data'=>['url'=>'http://download.jizhogndiancai.com','forced'=>$forced,'tips'=>'检测到新版本，是否更新'],'msg'=>'请求成功'];
        }else{
            return ['status'=>0,'data'=>['url'=>'','forced'=>0,'tips'=>'不需要版本更新'],'msg'=>'请求成功'];
        }
    }
}
