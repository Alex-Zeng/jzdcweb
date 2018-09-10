<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/9/10
 * Time: 11:12
 */
namespace app\api\controller;




use app\common\model\SmProduct;
use app\common\model\SmProductSpecAttrVal;
use think\Request;

class Material extends Base{


    /**
     * @desc 物料编号数量
     * @return array|void
     */
    public function getNumber(){
        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //查询物料规格数量
        $model = new \app\common\model\UserGoodsSpecifications();
        $where = [
            'a.user_id' => $this->userId,
            'c.state' => SmProduct::STATE_FORSALE,
            'c.audit_state' => SmProduct::AUDIT_RELEASED,
            'c.is_deleted' =>0,
            'b.is_deleted' => 0
        ];

        $count = $model->alias('a')
            ->join(['sm_product_spec' => 'b'],'a.product_spec_id = b.id','left')
            ->join(['sm_product' => 'c'],'b.product_id=c.id','left')
            ->where($where)->count();

        return ['status'=>0,'data'=>['total'=>$count],'msg'=>''];
    }

    /**
     * @desc 返回列表 APP,H5
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getList(Request $request){
        $pageSize = $request->post('pageSize',10,'intval');
        $pageNumber = $request->post('pageNumber',1,'intval');
        $keyword = $request->post('keyword','','trim');
        $start = ($pageNumber - 1)*$pageSize;

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        //查询物料规格数量
        $model = new \app\common\model\UserGoodsSpecifications();
        $where = [
            'a.user_id' => $this->userId,
            'c.state' => SmProduct::STATE_FORSALE,
            'c.audit_state' => SmProduct::AUDIT_RELEASED,
            'c.is_deleted' =>0,
            'b.is_deleted' => 0
        ];

        if($keyword){
            $where['a.specifications_no|a.specifications_name|c.title'] = ['like','%'.$keyword.'%'];
        }

        $field = ['a.id','a.specifications_no','a.specifications_name','a.product_spec_id','b.sku_code','b.spec_set','b.is_customized','b.min_order_qty','c.title'];
        $total = $model->alias('a')
            ->join(['sm_product_spec' => 'b'],'a.product_spec_id = b.id','left')
            ->join(['sm_product' => 'c'],'b.product_id=c.id','left')
            ->where($where)->count();

        $rows = $model->alias('a')
            ->join(['sm_product_spec' => 'b'],'a.product_spec_id = b.id','left')
            ->join(['sm_product' => 'c'],'b.product_id=c.id','left')
            ->where($where)->limit($start,$pageSize)->field($field)->select();

        $specAttrValModel = new SmProductSpecAttrVal();

        $list = [];
        foreach ($rows as $row){
            $specInfo = '';
            if($row->is_customized == 1){
                $specInfo = '定制';
            }else{
                //
                $specSetArr = explode(',',$row->spec_set);
                $specAttrValRows = $specAttrValModel->where(['id'=>['in',$specSetArr]])->select();
                foreach ($specAttrValRows as $specAttrValRow){
                    $specInfo .= $specAttrValRow->spec_attr_val.',';
                }
                $specInfo = $specInfo ? substr($specInfo,0,strlen($specInfo)-1) : $specInfo;
            }

            $list[] = [
              'materialId' => $row->id,
               'specId' => $row->product_spec_id,
              'materialCode' => $row->specifications_no,
              'materialSpec' => $row->specifications_name,
              'title' => $row->title,
              'minOrderQty' => $row->min_order_qty,
              'specInfo' => $specInfo
            ];

        }

        return ['status'=>0,'data'=>['total'=> $total,'list'=>$list],'msg'=>''];
    }

}