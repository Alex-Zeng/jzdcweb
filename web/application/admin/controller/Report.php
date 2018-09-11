<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/9/7
 * Time: 14:04
 */

namespace app\admin\controller;


use app\common\model\FormUserCert;
use app\common\model\IndexGroup;
use app\common\model\MallOrder;
use app\common\model\IndexUser;
use app\common\model\MallOrderGoods;
use app\common\model\SmProduct;
use app\common\model\SmProductSpec;
use app\common\model\MallOrderPay;
use think\Request;

class Report extends Base{

    public function __construct(Request $request = null)
    {
        parent::__construct($request);
        $groupId = getGroupId();
        if($groupId != IndexGroup::GROUP_OPERATION){
            $this->errorTips();
        }
    }

    /**
     * @desc 交易报表
     * @return mixed
     */
    public function trade(){
        $title = Request::instance()->get('title','','trim');
        $sku = Request::instance()->get('sku','','trim');
        $start = Request::instance()->get('start','');
        $end = Request::instance()->get('end','');

        $model = new MallOrderGoods();
        $where = ['c.state' => ['neq',MallOrder::STATE_CLOSED]];

        if(isset($sku) && $sku){
            $where['b.sku_code'] = ['like','%'.$sku.'%'];
        }
        if(isset($title) && $title){
            $where['a.title'] = ['like','%'.$title.'%'];
        }
        if(isset($start) && $start && isset($end) && $end){
            $where['c.add_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
        }elseif (isset($start) && $start){
            $where['c.add_time'] = ['gt',strtotime($start)];
        }elseif (isset($end) && $end){
            $where['c.add_time'] = ['lt',strtotime($end.' 23:59:59')];
        }

        $total = $model->alias('a')
            ->join(['sm_product_spec'=>'b'],'a.product_spec_id=b.id','left')
            ->join(['jzdc_mall_order'=>'c'],'a.order_id=c.id','left')
            ->where($where)
            ->field(['c.add_time','b.sku_code','a.title','a.s_info','FROM_UNIXTIME(c.add_time, \'%Y-%m-%d\') as order_date','SUM(a.price) as total_price','SUM(a.quantity) as amount'])
            ->group('order_date,a.product_spec_id')
            ->count();
        $rows = $model->alias('a')
            ->join(['sm_product_spec'=>'b'],'a.product_spec_id=b.id','left')
            ->join(['jzdc_mall_order'=>'c'],'a.order_id=c.id','left')
            ->where($where)
            ->field(['c.add_time','b.sku_code','a.title','a.s_info','FROM_UNIXTIME(c.add_time, \'%Y-%m-%d\') as order_date','SUM(a.price) as total_price','SUM(a.quantity) as amount'])
            ->group('order_date,a.product_spec_id')
            ->order('order_date desc,a.product_spec_id desc')
            ->paginate(20,$total,['query'=>request()->param()]);

        foreach ($rows as $row){
            $row['total_price'] = getFormatPrice($row->total_price);
            $row['amount'] = floatval($row->amount);
            $row['avgPrice'] = getFormatPrice(round($row->total_price/$row->amount,4));
        }

        $this->assign('title',$title);
        $this->assign('sku',$sku);
        $this->assign('start',$start);
        $this->assign('end',$end);
        $this->assign('list',$rows);
        $this->assign('stateList',MallOrder::getStateList());
        $this->assign('page',$rows->render());
        return $this->fetch();
    }

    /**
     * @desc 供应商
     * @return mixed
     */
    public function supplier(){
       return $this->roleReport(IndexGroup::GROUP_SUPPLIER);
    }

    /**
     * @desc 采购商报表
     * @return mixed
     */
    public function buyer(){
       return $this->roleReport(IndexGroup::GROUP_BUYER);
    }

    /**
     * @desc 订单报表
     * @return mixed
     */
    public function order(){
        $k = Request::instance()->get('k','','trim');
        $state = Request::instance()->get('state','-1');
        $start = Request::instance()->get('start','');
        $end = Request::instance()->get('end','');
        $model = new MallOrder();
        $where = [];
        if(isset($k) && $k){
            $where['out_id|buyer'] = ['like','%'.$k.'%'];
        }
        if(isset($state) && $state >= 0){
            if($state == 8){
                $where['service_type'] = ['in',[1,2,3]];
            }else{
                $where['state'] = $state;
            }
        }

        if(isset($start) && $start && isset($end) && $end){
            $where['add_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
        }elseif (isset($start) && $start){
            $where['add_time'] = ['gt',strtotime($start)];
        }elseif (isset($end) && $end){
            $where['add_time'] = ['lt',strtotime($end.' 23:59:59')];
        }

        //查询总价
        if($state < 0){
            $totalMoney = $model->where($where)->where(['state'=>['neq',4]])->field(['sum(`actual_money`) AS money'])->find();
        }else{
            $totalMoney = $model->where($where)->field(['sum(`actual_money`) AS money'])->find();
        }


        $rows = $model->where($where)->order('id','desc')->paginate(10,false,['query'=>request()->param()]);
        $userModel = new IndexUser();
        $goodsModel = new MallOrderGoods();
        foreach($rows as &$row){
            $goodsRows = $goodsModel->where(['order_id'=>$row->id])->order('time','desc')->select();
            $userInfo = $userModel->getInfoById($row->supplier);
            $buyerInfo = $userModel->getInfoById($row->buyer_id);

            $total = 0;
            foreach ($goodsRows as & $goodsRow){
                $productModel = new SmProduct();
                $specModel = new SmProductSpec();
                $productRow = $productModel->where(['id'=>$goodsRow->goods_id])->find();
                $specRow = $specModel->where(['id'=>$goodsRow->product_spec_id])->find();
                $goodsRow['icon'] = $specRow && $specRow->spec_img_url ? SmProductSpec::getFormatImg($specRow->spec_img_url) : SmProduct::getFormatImg($productRow->cover_img_url);
                $total += $goodsRow->price * $goodsRow->quantity;
            }
            $row['total'] = $total;
            $row['goods'] = $goodsRows;

            //支付信息
            $buyerPayInfo = $supplierPayInfo = [];
            $payModel = new MallOrderPay();
            $payRows = $payModel->where(['order_id'=>$row->id])->field(['pay_type','number','picture','pay_time','accept_time'])->select();
            foreach ($payRows as $payRow){
                $payRow['picture'] = MallOrderPay::getFormatPicture($payRow->picture);
                if($payRow->pay_type == 1 || $payRow->pay_type == 2){
                    $buyerPayInfo[] = $payRow;
                }elseif ($payRow->pay_type == 3 || $payRow->pay_type == 4){
                    $supplierPayInfo[] = $payRow;
                }
            }
            $row['supplierName'] = $userInfo ? $userInfo->real_name : '';
            $row['buyerName'] = $buyerInfo ? $buyerInfo->real_name : '';
            $row['buyerPayInfo'] = $buyerPayInfo;
            $row['supplierPayInfo'] = $supplierPayInfo;
        }

        $this->assign('list',$rows);
        $this->assign('state',$state);
        $this->assign('start',$start);
        $this->assign('end',$end);
        $this->assign('k',$k);
        $this->assign('stateList',MallOrder::getStateList());
        $this->assign('page',$rows->render());
        $this->assign('total',$totalMoney ? ($totalMoney->money ? $totalMoney->money : '0.00'  ): '0.00');
        return $this->fetch();
    }

    /**
     * @desc 企业认证报表
     * @return mixed
     */
    public function company(){
        $model = new FormUserCert();
        $k = Request::instance()->get('k','','trim');
        $start = Request::instance()->get('start','');
        $end = Request::instance()->get('end','');
        $where = [];
        if(isset($k) && $k){
            $where['b.phone'] = ['like','%'.$k.'%'];
        }

        if(isset($start) && $start && isset($end) && $end){
            $where['b.reg_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
        }elseif (isset($start) && $start){
            $where['b.reg_time'] = ['gt',strtotime($start)];
        }elseif (isset($end) && $end){
            $where['b.reg_time'] = ['lt',strtotime($end.' 23:59:59')];
        }


        $fields = ['a.*','b.username','b.phone','b.reg_time','b.contact'];
        $rows = $model->alias('a')->join(config('prefix').'index_user b','a.writer=b.id','left')->where($where)->field($fields)->order('a.write_time','desc')->paginate(20,false,['query'=>request()->param()]);

        $this->assign('k',$k);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        $this->assign('start',$start);
        $this->assign('end',$end);
        return $this->fetch();
    }

    /**
     * @desc 商品交易报表导出
     * @param string $start
     * @param string $title
     * @param string $end
     * @param string $sku
     * @throws \think\exception\DbException
     */
    public function trade_export($start = '',$title = '',$end = '',$sku = ''){
        $model = new MallOrderGoods();
        $where = ['c.state' => ['neq',MallOrder::STATE_CLOSED]];

        if(isset($sku) && $sku){
            $where['b.sku_code'] = ['like','%'.$sku.'%'];
        }
        if(isset($title) && $title){
            $where['a.title'] = ['like','%'.$title.'%'];
        }
        if(isset($start) && $start && isset($end) && $end){
            $where['c.add_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
        }elseif (isset($start) && $start){
            $where['c.add_time'] = ['gt',strtotime($start)];
        }elseif (isset($end) && $end){
            $where['c.add_time'] = ['lt',strtotime($end.' 23:59:59')];
        }

        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        //设置表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '序号')
            ->setCellValue('B1', '日期')
            ->setCellValue('C1',   'SKU编码')
            ->setCellValue('D1', '商品名称')
            ->setCellValue('E1', '商品规格')
            ->setCellValue('F1','交易数量')
            ->setCellValue('G1','交易金额')
            ->setCellValue('H1','平均交易单价');

        //查询数据
        $total = $model->alias('a')
            ->join(['sm_product_spec'=>'b'],'a.product_spec_id=b.id','left')
            ->join(['jzdc_mall_order'=>'c'],'a.order_id=c.id','left')
            ->where($where)
            ->field(['c.add_time','b.sku_code','a.title','a.s_info','FROM_UNIXTIME(c.add_time, \'%Y-%m-%d\') as order_date','SUM(a.price) as total_price','SUM(a.quantity) as amount'])
            ->group('order_date,a.product_spec_id')
            ->count();

        $pageSize = 100;
        $page = ceil($total / $pageSize);
        $counter = 2;

        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //设置宽度
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('A')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('G')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('H')->setWidth(20);

        for ($i = 0; $i < $page; $i++) {
            $start = $pageSize * $i;
        $rows = $model->alias('a')
            ->join(['sm_product_spec'=>'b'],'a.product_spec_id=b.id','left')
            ->join(['jzdc_mall_order'=>'c'],'a.order_id=c.id','left')
            ->where($where)
            ->field(['c.add_time','b.sku_code','a.title','a.s_info','FROM_UNIXTIME(c.add_time, \'%Y-%m-%d\') as order_date','SUM(a.price) as total_price','SUM(a.quantity) as amount'])
            ->group('order_date,a.product_spec_id')
            ->order('order_date desc,a.product_spec_id desc')
            ->limit($start, $pageSize)
            ->select();
            foreach ($rows as $row) {
                $avgPrice = getFormatPrice(round($row->total_price/$row->amount,4));
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $counter, $counter-1);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$counter,$row->order_date);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . $counter, $row->sku_code);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D' . $counter, $row->title);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . $counter, $row->s_info);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . $counter, floatval($row->amount));
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . $counter, getFormatPrice($row->total_price));
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . $counter, $avgPrice);
                $counter++;
                unset($goodsRows);
                unset($rows);
            }
        }
        $filename = '商品交易报表_' . date('YmdHi', time()) . '.xls';
        $title ='商品交易报表_信息';
        $objPHPExcel->getActiveSheet()->setTitle($title);
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $filename . '"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

    protected function roleReport($role = IndexGroup::GROUP_SUPPLIER){
        $k = Request::instance()->get('k','','trim');
        $start = Request::instance()->get('start','');
        $end = Request::instance()->get('end','');

        //查询数据
        $model = new MallOrder();
        $where = ['b.group'=> $role,'a.state'=>['neq',MallOrder::STATE_CLOSED]];
        if($k){
            $where['b.real_name'] = ['like','%'.$k.'%'];
        }

        if(isset($start) && $start && isset($end) && $end){
            $where['a.add_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
        }elseif (isset($start) && $start){
            $where['a.add_time'] = ['gt',strtotime($start)];
        }elseif (isset($end) && $end){
            $where['a.add_time'] = ['lt',strtotime($end.' 23:59:59')];
        }

        if($role == IndexGroup::GROUP_SUPPLIER){
            $total = $model->alias('a')->join(['jzdc_index_user'=>'b'],'a.supplier=b.id')
                ->field(['a.add_time','b.real_name','a.actual_money','a.supplier','count(*) as count','FROM_UNIXTIME(a.add_time, \'%Y-%m-%d\') as order_date','SUM(actual_money) as total_money'])
                ->where($where) ->group('order_date,a.supplier')->count();

            $rows = $model->alias('a')->join(['jzdc_index_user'=>'b'],'a.supplier=b.id')
                ->where($where) ->group('order_date,a.supplier')->order('order_date desc,supplier desc')
                ->field(['a.add_time','b.real_name','a.actual_money','a.supplier','count(*) as count','FROM_UNIXTIME(a.add_time, \'%Y-%m-%d\') as order_date','SUM(actual_money) as total_money'])
                ->paginate(20,$total,['query'=>request()->param()]);
        }
        if($role == IndexGroup::GROUP_BUYER){
            $total = $model->alias('a')->join(['jzdc_index_user'=>'b'],'a.buyer_id=b.id')
                ->field(['a.add_time','b.real_name','a.actual_money','a.buyer_id','count(*) as count','FROM_UNIXTIME(a.add_time, \'%Y-%m-%d\') as order_date','SUM(actual_money) as total_money'])
                ->where($where) ->group('order_date,a.buyer_id')->count();

            $rows = $model->alias('a')->join(['jzdc_index_user'=>'b'],'a.buyer_id=b.id')
                ->where($where) ->group('order_date,a.buyer_id')->order('order_date desc,buyer_id desc')
                ->field(['a.add_time','b.real_name','a.actual_money','a.buyer_id','count(*) as count','FROM_UNIXTIME(a.add_time, \'%Y-%m-%d\') as order_date','SUM(actual_money) as total_money'])
                ->paginate(20,$total,['query'=>request()->param()]);
        }

        foreach ($rows as &$row){
            $row['total_money'] = getFormatPrice($row->total_money);
        }

        $this->assign('k',$k);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());

        $this->assign('start',$start);
        $this->assign('end',$end);
        return $this->fetch();
    }

    /**
     * @desc 供应商交易报表导出
     * @param string $start
     * @param string $k
     * @param string $end
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function supplier_export($start = '',$k = '',$end = ''){
       $this->role_export(IndexGroup::GROUP_SUPPLIER,$start,$k,$end);
    }


    /**
     * @desc 采购商交易报表导出
     * @param string $start
     * @param string $k
     * @param string $end
     */
    public function buyer_export($start = '',$k = '', $end = ''){
       $this->role_export(IndexGroup::GROUP_BUYER,$start,$k,$end);
    }

    /**
     * @desc 企业认证数据导出
     */
    public function company_export($start = '',$k = '',$end = ''){
        $model = new FormUserCert();

        $where = [];
        if(isset($k) && $k){
            $where['b.phone'] = ['like','%'.$k.'%'];
        }

        if(isset($start) && $start && isset($end) && $end){
            $where['b.reg_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
        }elseif (isset($start) && $start){
            $where['b.reg_time'] = ['gt',strtotime($start)];
        }elseif (isset($end) && $end){
            $where['b.reg_time'] = ['lt',strtotime($end.' 23:59:59')];
        }


        $fields = ['a.*','b.username','b.phone','b.reg_time','b.contact'];

        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        //设置表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '序号')
            ->setCellValue('B1', '注册手机')
            ->setCellValue('C1', '注册用户名')
            ->setCellValue('D1', '企业名称')
            ->setCellValue('E1', '认证类型')
            ->setCellValue('F1', '注册时间')
            ->setCellValue('G1', '法人代表')
            ->setCellValue('H1', '联系人')
            ->setCellValue('I1', '联系电话')
            ->setCellValue('J1', '提交日期')
            ->setCellValue('K1', '审核状态')
            ->setCellValue('L1', '审核日期');


        //查询数据
        $total = $model->alias('a')->join(config('prefix').'index_user b','a.writer=b.id','left')->where($where)->count();
        $pageSize = 100;
        $page = ceil($total / $pageSize);

        $counter = 2;

        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //设置宽度
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('A')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('E')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('F')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('G')->setWidth(25);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('H')->setWidth(15);

        for ($i = 0; $i < $page; $i++) {
            $start = $pageSize * $i;
            $rows = $model->alias('a')->join(config('prefix').'index_user b','a.writer=b.id','left')->where($where)->limit($start, $pageSize)->order('a.write_time', 'desc')->field($fields)->select();
            foreach ($rows as $row) {
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $counter, $counter-1);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$counter,$row->phone);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . $counter, $row->username);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D' . $counter, $row->company_name);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . $counter, $row->reg_role);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('F' . $counter, date('Y-m-d',$row->reg_time));
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('G' . $counter, $row->legal_representative);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . $counter, $row->contact);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I' . $counter, $row->ent_phone);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J' . $counter, $row->edit_time >0 ? date('Y-m-d',$row->edit_time) : '');
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K' . $counter,    $row->status == 1 ? '待审核': ($row->status == 2 ? '已通过': '已拒绝'));
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L' . $counter,   $row->audit_time >0 ? date('Y-m-d',$row->audit_time) : '');
                $counter++;
                unset($goodsRows);
                unset($rows);
            }
        }
        $filename = '企业注册认证_' . date('YmdHi', time()) . '.xls';
        $objPHPExcel->getActiveSheet()->setTitle('企业注册认证信息');
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $filename . '"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }


    protected function role_export($role = IndexGroup::GROUP_SUPPLIER,$start,$k,$end){
        //查询数据
        $model = new MallOrder();
        $where = ['b.group'=>$role,'a.state'=>['neq',MallOrder::STATE_CLOSED]];
        if($k){
            $where['b.real_name'] = ['like','%'.$k.'%'];
        }

        if(isset($start) && $start && isset($end) && $end){
            $where['a.add_time'] = ['between',[strtotime($start),strtotime($end.' 23:59:59')]];
        }elseif (isset($start) && $start){
            $where['a.add_time'] = ['gt',strtotime($start)];
        }elseif (isset($end) && $end){
            $where['a.add_time'] = ['lt',strtotime($end.' 23:59:59')];
        }

        vendor('PHPExcel.PHPExcel');
        $objPHPExcel = new \PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0);
        //设置表头
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '序号')
            ->setCellValue('B1', '日期')
            ->setCellValue('C1',  $role == IndexGroup::GROUP_SUPPLIER ?'供应商名称' : '采购商名称')
            ->setCellValue('D1', '交易订单笔数')
            ->setCellValue('E1', '交易金额');

        //查询数据
        if($role == IndexGroup::GROUP_SUPPLIER){
            $total =$model->alias('a')->join(['jzdc_index_user'=>'b'],'a.supplier=b.id')
                ->field(['a.add_time','b.real_name','a.actual_money','a.supplier','count(*) as count','FROM_UNIXTIME(a.add_time, \'%Y-%m-%d\') as order_date','SUM(actual_money) as total_money'])
                ->where($where) ->group('order_date,a.supplier')->count();
        }
        if($role == IndexGroup::GROUP_BUYER){
            $total =$model->alias('a')->join(['jzdc_index_user'=>'b'],'a.buyer_id=b.id')
                ->field(['a.add_time','b.real_name','a.actual_money','a.buyer_id','count(*) as count','FROM_UNIXTIME(a.add_time, \'%Y-%m-%d\') as order_date','SUM(actual_money) as total_money'])
                ->where($where) ->group('order_date,a.buyer_id')->count();
        }


        $pageSize = 100;
        $page = ceil($total / $pageSize);

        $counter = 2;

        $objPHPExcel->getDefaultStyle()->getAlignment()->setVertical(\PHPExcel_Style_Alignment::VERTICAL_CENTER);
        //设置宽度
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('A')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('B')->setWidth(16);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('D')->setWidth(20);
        $objPHPExcel->getActiveSheet(0)->getColumnDimension('E')->setWidth(20);


        for ($i = 0; $i < $page; $i++) {
            $start = $pageSize * $i;
            if($role == IndexGroup::GROUP_SUPPLIER) {
                $rows = $model->alias('a')->join(['jzdc_index_user' => 'b'], 'a.supplier=b.id')
                    ->where($where)->group('order_date,a.supplier')->order('order_date desc,supplier desc')
                    ->limit($start, $pageSize)
                    ->field(['a.add_time', 'b.real_name', 'a.actual_money', 'a.supplier', 'count(*) as count', 'FROM_UNIXTIME(a.add_time, \'%Y-%m-%d\') as order_date', 'SUM(actual_money) as total_money'])
                    ->select();
            }

            if($role == IndexGroup::GROUP_BUYER) {
                $rows = $model->alias('a')->join(['jzdc_index_user' => 'b'], 'a.buyer_id=b.id')
                    ->where($where)->group('order_date,a.buyer_id')->order('order_date desc,buyer_id desc')
                    ->limit($start, $pageSize)
                    ->field(['a.add_time', 'b.real_name', 'a.actual_money', 'a.buyer_id', 'count(*) as count', 'FROM_UNIXTIME(a.add_time, \'%Y-%m-%d\') as order_date', 'SUM(actual_money) as total_money'])
                    ->select();
            }

            foreach ($rows as $row) {
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A' . $counter, $counter-1);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('B'.$counter,$row->order_date);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('C' . $counter, $row->real_name);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('D' . $counter, $row->count);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('E' . $counter, getFormatPrice($row->total_money));

                $counter++;
                unset($goodsRows);
                unset($rows);
            }
        }
        $filename = $role == IndexGroup::GROUP_SUPPLIER ? '供应商交易报表_' . date('YmdHi', time()) . '.xls' : '采购商交易报表_' . date('YmdHi', time()) . '.xls';
        $title = IndexGroup::GROUP_SUPPLIER ? '供应商交易报表信息' : '采购商交易报表信息';
        $objPHPExcel->getActiveSheet()->setTitle($title);
        header("Content-Type: application/force-download");
        header("Content-Type: application/octet-stream");
        header("Content-Type: application/download");
        header('Content-Disposition:inline;filename="' . $filename . '"');
        //生成excel文件
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }

}