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
        $turnoverMonth = number_format($model->getTurnover('month'),2,',',' ');//本月成交额
        $turnoverAll = number_format($model->getTurnover('all'),2,',',' ');//累计成交额
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

    //版本下载
    public function downloadApp(){
        header("Content-type:text/html;charset=utf-8");
        //检查是来源客户端
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
        if(stripos($userAgent, 'iPhone') && stripos($userAgent, 'MicroMessenger')){
            echo '<div style="font-size:60px;">温馨提示：如果您是iPhone用户通过微信、QQ打开该页面无法下载，请点右上角由浏览器打开（iPhone用户选择Safari打开）</div>';
            exit;
        }elseif(stripos($userAgent, 'iPhone')) {
            echo '<div style="font-size:60px;text-align:center;margin-top:300px;">敬请期待IOS版本</div>';
            exit();
            // header('Location: https://itunes.apple.com/cn/app/dai-dai-wang/id1276128703');
            // exit();
        }elseif((stripos($userAgent, 'MicroMessenger') === false)&&strpos($userAgent, 'QQ') === false) { //浏览器下载或版本更新
             $version = db('version')->field('app_name')->where(['up_time'=>['elt',time()],'is_del'=>1])->order('version_id desc')->find();
            if(empty($version)){
                echo '<p style="font-size:60px;text-align:center;margin-top:300px;">敬请期待Android版本</p>';
                exit;
            }else{
                echo '<p style="font-size:60px;text-align:center;margin-top:300px;">敬请期待Android版本</p>';
                exit();
                // header('Location: '.request()->domain().'/version/'.$version['app_name']);
                // exit;
            }
        }else{ //微信下载
            echo '<div style="font-size:60px;text-align:center;margin-top:300px;">应用宝还没上线呢！请点右上角由浏览器打开进行下载</div>';
            exit();
            // header('Location: http://a.app.qq.com/o/simple.jsp?pkgname=com.zhongchuang.daidai');
            // exit;
        }
    }

}
