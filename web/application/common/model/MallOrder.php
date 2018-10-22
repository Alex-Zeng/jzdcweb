<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/6/2
 * Time: 15:25
 */
namespace app\common\model;

use think\Model;

class MallOrder extends Model{
    const  STATE_PRICING = 0;  //核价
    const  STATE_SIGN =1; //签约
    const  STATE_REMITTANCE = 2; //打款  remittance
    const  STATE_DELIVER = 3; //发货
    const  STATE_CLOSED = 4; //关闭
    const  STATE_RECEIVE = 6; //收货
    const  STATE_QUALITY_CHECK = 7;//质检
    const  STATE_PROBLEM_CONFIRM = 8; //售后处理
    const  STATE_ACCOUNT_PERIOD = 9; //账期中
    const  STATE_OVERDUE = 10; //逾期中
    const  STATE_REMITTANCE_SUPPLIER = 11; //打款供应商
    const  STATE_FINISH = 13; //交易完成

    const ROLE_BUYER = 1;    //买家
    const ROLE_SELLER = 2;   //卖家


    public static function getStateList(){
        return  [
            self::STATE_PRICING => '待核价',
            self::STATE_SIGN => '待签约',
            self::STATE_REMITTANCE => '待采购商打款',
            self::STATE_DELIVER => '待发货',
            self::STATE_CLOSED => '订单关闭',
            self::STATE_RECEIVE => '待收货',
            self::STATE_QUALITY_CHECK => '待质检',
            self::STATE_PROBLEM_CONFIRM =>'售后处理',
            self::STATE_ACCOUNT_PERIOD => '账期中',
            self::STATE_OVERDUE => '逾期中',
            self::STATE_REMITTANCE_SUPPLIER =>'待打款至供应商',
            self::STATE_FINISH => '交易完成'
        ];
    }

    /**
     * [getTurnover 获取累计成交额]
     * @param  [string] $type [获取的类型：all所有month本月]
     * @return [string]       [格式化后成交额]
     */
    public function getTurnover($type){
        $where = [];
        switch ($type) {
            case 'all':
                $where['confirm_delivery_time'] = ['>',0];
                break;
            case 'month':
                $monthStart = mktime(0,0,0,date('m'),1,date('Y'));
                $monthEnd = mktime(23,59,59,date('m'),date('t'),date('Y'));
                $where['confirm_delivery_time'] = ['between',[$monthStart,$monthEnd]];
                break;
            default:
                return '0.00';
                break;
        }
        return $this->where($where)->sum('actual_money');//number_format(
    }


    /**
     * @desc 获取买家 买家订单
     * @param $merchantType  商家类型
     * @param $orderType  订单类型   1=近期成交  2=待发货  3=待售后
     * @param $companyId  企业ID
     * @param $userId 用户ID
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getDeskList($merchantType,$orderType,$companyId,$userId){
        $where = [];
        if($merchantType == 1){
            $where['supplier'] = $companyId;
        }elseif ($merchantType == 2){
            $where['buyer_id'] = $companyId;
            if($userId > 0){   //查询当前用户
                //  $where['']
            }
        }
        switch ($orderType){
            case 1:  //近期成交
                break;
            case 2: //待发货
                $where['state'] = 3;
                break;
            case 3: //待售后
                $where['service_type'] = 1;
                break;
            default:
        }

        $field = ['id','add_time','out_id','supplier','goods_count','actual_money','state','service_type'];
        $rows = $this->where($where)->order('add_time','desc')->field($field)->select();
        $supplierIds = [];
        foreach ($rows as $row){
            $supplierIds[] = $row->supplier;
        }

        $companyModel = new EntCompany();
        $supplierInfos = $companyModel->where(['id'=>['in',$supplierIds]])->field(['id','company_name'])->select();

        $supplierMap = [];
        foreach ($supplierInfos as $supplierInfo){
            $supplierMap[$supplierInfo->id] = $supplierInfo->company_name;
        }

        $data = [];
        foreach ($rows as $row){
            $data[] = [
                'orderNo'=> $row->out_id,
                'supplierName' => isset($supplierMap[$row->supplier]) ? $supplierMap[$row->supplier] : '',
                'orderTime' => date('Y-m-d H:i',$row->add_time),
                'goodsNumber' => intval($row->goods_count),
                'totalMoney' => $row->actual_money,
                'stateInfo'=> getOrderStatusInfo($row->state,$row->service_type)
            ];
        }
        return $data;
    }


}