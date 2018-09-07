<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/9/7
 * Time: 14:04
 */

namespace app\admin\controller;


use app\common\model\FormUserCert;
use think\Request;

class Report extends Base{

    /**
     * @desc 交易报表
     * @return mixed
     */
    public function trade(){
        $this->assign('list',[]);
        $this->assign('page',[]);
        return $this->fetch();
    }

    /**
     * @desc 供应商
     * @return mixed
     */
    public function supplier(){
        $this->assign('list',[]);
        $this->assign('page',[]);
        return $this->fetch();
    }

    /**
     * @desc 采购商报表
     * @return mixed
     */
    public function buyer(){
        $this->assign('list',[]);
        $this->assign('page',[]);
        return $this->fetch();
    }

    public function order(){
        $this->assign('list',[]);
        $this->assign('page',[]);
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


        $fields = ['a.*','b.username','b.phone','b.reg_time'];
        $rows = $model->alias('a')->join(config('prefix').'index_user b','a.writer=b.id','left')->where($where)->field($fields)->order('a.write_time','desc')->paginate(20,false,['query'=>request()->param()]);

        $this->assign('k',$k);
        $this->assign('list',$rows);
        $this->assign('page',$rows->render());

        $this->assign('start','');
        $this->assign('end','');

        return $this->fetch();
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


        $fields = ['a.*','b.username','b.phone','b.reg_time'];

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
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('H' . $counter, $row->contact_point);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('I' . $counter, $row->ent_phone);
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('J' . $counter,  date('Y-m-d',$row->edit_time));
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('K' . $counter,    $row->status == 1 ? '待审核': ($row->status == 2 ? '已通过': '已拒绝'));
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('L' . $counter, date('Y-m-d',$row->edit_time));
                $counter++;
                unset($goodsRows);
                unset($rows);
            }
        }
        $filename = 'company_' . date('YmdHi', time()) . '.xls';
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


}