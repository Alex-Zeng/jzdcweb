<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 16:41
 */

namespace app\api\controller;

use app\common\model\EmailCode;
use app\common\model\EntCompany;
use app\common\model\EntCompanyAudit;
use app\common\model\FormUserCert;
use app\common\model\IndexArea;
use app\common\model\IndexUser;
use app\common\model\MallFavorite;
use app\common\model\MallOrder;
use app\common\model\MallOrderGoods;
use app\common\model\MallReceiver;
use app\common\model\Notice;
use app\common\model\OrderMsg;
use app\common\model\MallReceiverTag;
use app\common\model\SmProduct;
use sms\Yunpian;
use think\Request;
use think\View;

class User extends Base
{


    /**
     * @desc 返回用户所在组
     * @return array
     */
    public function getGroup()
    {
        $this->noauth();
        return ['status' => 0, 'data' => ['groupId' => intval($this->groupId)], 'msg' => ''];
    }

    /**
     * @desc 返回用户身份角色
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getRole(){
        $this->noauth();
        $userModel = new IndexUser();
        $companyModel = new EntCompany();
        //获取用户ID
        $userId = $this->userId;
        $roleId = 0;   //未加入公司
        if($userId > 0){
            $userInfo = $userModel->getInfoById($userId);
            if($userInfo->company_id > 0){
                $companyInfo = $companyModel->where(['id'=>$userInfo->company_id,'is_deleted'=>0])->find();
                if($companyInfo){
                    if($userId == $companyInfo->responsible_user_id){
                        $roleId = 1; //公司管理员
                    }else{
                        $roleId = 2; //公司非管理员
                    }
                }
            }
        }
        return ['status' => 0, 'data' => ['roleId' => intval($roleId)], 'msg' => ''];
    }


    /**
     * @desc 添加收货人地址
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function addAddress(Request $request)
    {
        $areaId = $request->post('areaId', 0, 'intval');
        $detail = $request->post('detail', '');
        $postCode = $request->post('postCode', '');
        $name = $request->post('name', '','htmlspecialchars');
        $phone = $request->post('phone', '');
        $tag = $request->post('tag', '');
        $default = $request->post('is_default', 0, 'intval');
        if (!checkPhone($phone)) {
            return ['status' => 1, 'data' => [], 'msg' => '您输入的联系方式无效，请输入有效的手机号码！'];
        }

        if (!$detail) {
            return ['status' => 1, 'data' => [], 'msg' => '详细地址不能为空'];
        }

        if (!$name) {
            return ['status' => 1, 'data' => [], 'msg' => '收货人不能为空'];
        }

        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $model = new MallReceiver();

        $userId = $this->userId;
        $count = $model->where(['user_id' => $userId])->count();
        if ($count >= 20) {
            return ['status' => 1, 'data' => [], 'msg' => '最多添加20个收货人地址'];
        }

        $userRow = (new IndexUser())->getInfoById($userId);
        $userName = $userRow ? $userRow['username'] : '';

        $data = [
            'username' => $userName,
            'time' => time(),
            'area_id' => $areaId,
            'detail' => $detail,
            'post_code' => $postCode,
            'name' => $name,
            'phone' => $phone,
            'tag' => $tag,
            'user_id' => $userId,
            'is_default' => $default
        ];
        $result = $model->save($data);
        if ($result == true) {
            //更新
            if ($default == 1) {
                (new MallReceiver())->save(['is_default' => 0],['user_id'=>$this->userId,'id'=>['not in',$model->id]]);
            }

            return ['status' => 0, 'data' => [], 'msg' => '添加成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '添加失败'];
    }

    /**
     * @desc 设置默认地址
     * @return array
     */
    public function setDefaultAddress(Request $request)
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        $id = $request->post('id', '0', 'intval');
        if (!$id) {
            return ['status' => 1, 'data' => [], 'msg' => '请正确选择默认地址'];
        }
        $model = new MallReceiver();
        $model->save(['is_default' => 0], ['user_id' => $this->userId]);
        $result = $model->save(['is_default' => 1], ['id' => $id, 'user_id' => $this->userId]);

        if ($result !== false) {
            return ['status' => 0, 'data' => [], 'msg' => '设置默认地址成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '设置默认地址失败'];
    }

    /**
     * @desc 修改收货人地址
     * @param Request $request
     * @return array
     */
    public function editAddress(Request $request)
    {
        $id = $request->post('id', 0, 'intval');
        $areaId = $request->post('areaId', 0, 'intval');
        $detail = $request->post('detail', '');
        $postCode = $request->post('postCode', '');
        $name = $request->post('name', '');
        $phone = $request->post('phone', '');
        $tag = $request->post('tag', '');
        $default = $request->post('is_default', 0, 'intval');

        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        if (!checkPhone($phone)) {
            return ['status' => 1, 'data' => [], 'msg' => '手机号格式不正确'];
        }

        if (!$detail) {
            return ['status' => 1, 'data' => [], 'msg' => '详细地址不能为空'];
        }

        if (!$name) {
            return ['status' => 1, 'data' => [], 'msg' => '收货人不能为空'];
        }

        $model = new MallReceiver();
        $data = [
            'time' => time(),
            'area_id' => $areaId,
            'detail' => $detail,
            'post_code' => $postCode,
            'name' => $name,
            'phone' => $phone,
            'tag' => $tag,
        ];
        if(isset($_POST['is_default'])){
            $data['is_default'] = $default;
        }

        $result = $model->save($data, ['id' => $id]);
        if ($result !== false) {
            //更新
            if ($default == 1) {
                (new MallReceiver())->save(['is_default' => 0],['user_id'=>$this->userId,'id'=>['not in',$id]]);
            }
            return ['status' => 0, 'data' => [], 'msg' => '修改成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '修改失败'];
    }

    /**
     * @desc 删除收货人地址
     * @param Request $request
     * @return array
     */
    public function removeAddress(Request $request)
    {
        $id = $request->post('id', 0, 'intval');
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $model = new MallReceiver();
        $result = $model->where(['id' => $id])->delete();
        if ($result == true) {
            return ['status' => 0, 'data' => [], 'msg' => '删除成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '删除失败'];
    }

    /**
     * @desc 返回用户收藏商品数量
     * @return array
     */
    public function getFavoriteNumber()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $model = new MallFavorite();
        $count = $model->where(['user_id' => $this->userId])->count();

        return ['status' => 0, 'data' => ['number' => $count], 'msg' => ''];
    }

    /**
     * @desc 返回收藏列表
     * @param Request $request
     * @return array|void
     */
    public function getFavoriteList(Request $request)
    {
        $pageSize = $request->post('pageSize', 10, 'intval');
        $pageNumber = $request->post('pageNumber', 1, 'intval');
        $field = $request->post('field', 'time', 'trim');
        $sort = $request->post('sort', 'desc', 'trim');
        if (!in_array($field, ['time', 'price'])) {
            return ['status' => 1, 'data' => [], 'msg' => '数据错误'];
        }
        if (!in_array($sort, ['asc', 'desc'])) {
            return ['status' => 1, 'data' => [], 'msg' => '数据错误'];
        }

        if ($pageSize > 10) {
            $pageSize = 10;
        };
        $offset = ($pageNumber - 1) * $pageSize;

        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        $model = new MallFavorite();
        $field == 'time' ? ($field = 'a.' . $field) : ($field = 'b.min_price');

        //数据获取
        $total = $model->alias('a')
            ->join(['sm_product'=>'b'],'a.goods_id=b.id','left')
            ->where(['a.user_id'=>$this->userId])
            ->count();
        $rows = $model->alias('a')
            ->join(['sm_product' => 'b'],'a.goods_id=b.id','left')
            ->where(['a.user_id'=>$this->userId])
            ->order([$field => $sort])
            ->limit($offset,$pageSize)
            ->field(['b.id','b.title','b.cover_img_url','b.min_price','b.max_price','b.is_price_neg_at_phone'])
            ->select();

        $list = [];
        foreach ($rows as $row){
            $list[] = [
                'id' => $row->id,
                'title' => $row->title,
                'icon' => SmProduct::getFormatImg($row->cover_img_url),
                'min_price' => getFormatPrice($row->min_price),
                'max_price' => getFormatPrice($row->max_price),
                'isDiscussPrice' => getBinDecimal($row->is_price_neg_at_phone),
                'showPrice' => getShowPrice(getBinDecimal($row->is_price_neg_at_phone),$row->min_price,$row->max_price)
            ];
        }

        return ['status' => 0, 'data' => ['total' => $total, 'list' => $list], 'msg' => ''];
    }

    /**
     * @desc 返回收货地址列表
     * @param Request $request
     * @return array|void
     */
    public function getAddressList(Request $request)
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $field = ['id', 'area_id', 'detail', 'post_code', 'name', 'phone', 'tag', 'time', 'is_default'];
        $model = new MallReceiver();
        $areaModel = new IndexArea();
        $rows = $model->where(['user_id' => $this->userId])->order(['is_default'=>'desc','id'=>'desc'])->field($field)->select();
        foreach ($rows as &$row) {
            $areaList = $areaModel->getAreaInfo($row->area_id);
            $areaIds = $areaModel->getAreaIds($row->area_id);
            if ($areaList) {
                array_pop($areaList);
            }
            $row['areaName'] = $areaList ? implode('-', array_reverse($areaList)) : '';
            $arrIds = [];
            if ($areaIds) {
                array_pop($areaIds);
            }
            $areaIds = $areaIds ? array_reverse($areaIds) : [];
            for ($i = 0; $i < count($areaIds); $i++) {
                $arrIds[] = trim($areaIds[$i]);
            }
            $row['areaIds'] = $arrIds;
            $row['is_default'] = $row->is_default ? true : false;
        }

        return ['status' => 0, 'data' => ['list' => $rows], 'msg' => ''];
    }

    /**
     * @desc
     * @return array|void
     */
    public function getSupplierOrderInfo()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        //权限验证
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];

        //
        $model = new MallOrder();

        $startTime = strtotime(date("Y-m-d 00:00:00", strtotime("-1 day")));
        $endTime = strtotime(date('Y-m-d 23:59:59', strtotime("-1 day")));

        //
        $yesterdayCount = $model->where(['supplier' => $companyId])->where('add_time', '>', $startTime)->where('add_time', '<', $endTime)->count();
        $total = $model->where(['supplier' => $companyId])->count();
        $pendingNumber = $model->where(['supplier' => $companyId, 'state' => MallOrder::STATE_DELIVER])->count();
        $serviceNumber = $model->where(['supplier'=> $companyId,'state'=>MallOrder::STATE_RECEIVE,'service_type'=>1])->order(['supplier'=> $this->userId,'state'=>MallOrder::STATE_FINISH,'service_type'=>1])->count();
        //在售商品总数
        $productModel = new SmProduct();
        //交易金额   $where['confirm_delivery_time'] = ['>',0];
        $moneyInfo = $model->where(['supplier'=>$companyId,'confirm_delivery_time'=>['gt',0]])->field(['sum(`actual_money`) as money'])->find();

        //在售商品访问量
        $goodsInfo = $productModel->where(['state'=>SmProduct::STATE_FORSALE,'audit_state'=>SmProduct::AUDIT_RELEASED,'is_deleted'=>0,'supplier_id'=>$companyId])->field(['count(*) as count','sum(page_view) as visit'])->find();
        //
        return [
            'status' => 0,
            'data' => [
                'yesterday' => $yesterdayCount,
                'total' => $total,
                'pending' => $pendingNumber,
                'service'=>$serviceNumber,
                'goodsNumber'=>$goodsInfo->count,
                'visit'=>$goodsInfo->visit ? $goodsInfo->visit : 0,
                'money' => $moneyInfo  && $moneyInfo->money ? number_format($moneyInfo->money) : 0
            ],
            'msg' => ''
        ];
    }

    /**
     * @desc
     * @return array|void
     */
    public function getBuyerOrderInfo()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        //权限验证
        $pResult = $this->checkCompanyPermission();
        if($pResult['status'] == 1){
            return $pResult;
        }
        $companyId = $pResult['data']['companyId'];
        $companyModel = new EntCompany();
        $userId = 0;
        $companyInfo = $companyModel->getInfoById($companyId);
        if($companyInfo->responsible_user_id != $this->userId){
            $userId = $this->userId;
        }

        $where = [];
        $where['buyer_id'] = $companyId;
        if($userId > 0){
            $where['created_user_id'] = $userId;
        }

        $model = new MallOrder();
        $condition = [MallOrder::STATE_REMITTANCE,MallOrder::STATE_ACCOUNT_PERIOD,MallOrder::STATE_OVERDUE];
        $payCount = $model->where($where)->whereIn('state',$condition)->count();
        $recieveNumber = $model->where($where)->where(['state' => MallOrder::STATE_RECEIVE])->count();
        $pendingNumber = $model->where($where)->where(['state' => MallOrder::STATE_DELIVER])->count();
        $serviceNumber = $model->where($where)->where(['service_type'=>1])->count();
        $moneyInfo = $model->where($where)->where(['confirm_delivery_time'=>['gt',0]])->field(['sum(`actual_money`) as money'])->find();
        return [
            'status' => 0,
            'data' => [
                'pay' => $payCount,
                'recieve' => $recieveNumber,
                'deliver' => $pendingNumber,
                'service'=>$serviceNumber,
                'money'=>$moneyInfo && $moneyInfo->money ? number_format($moneyInfo->money) : 0
            ],
            'msg' => ''
        ];
    }


    /**
     * @desc 消息列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getMessageList(Request $request)
    {
        $pageSize = $request->post('pageSize', 10, 'intval');
        $pageNumber = $request->post('pageNumber', 1, 'intval');
        $pageSize = $pageSize > 20 ? 20 : $pageSize;

        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $start = ($pageNumber - 1) * $pageSize;
        $model = new OrderMsg();
        $total = $model->where(['user_id' => $this->userId, 'is_delete' => 0])->order('create_time', 'desc')->count();
        $rows = $model->where(['user_id' => $this->userId, 'is_delete' => 0])->order('create_time', 'desc')->field(['id', 'title', 'content', 'order_no','order_id', 'create_time'])->limit($start, $pageSize)->select();
        $data = [];
        foreach ($rows as &$row) {
            $orderModel = new MallOrder();
            $orderRow = $orderModel->where(['out_id' => $row->order_no])->field(['id','goods_names'])->find();

            $orderGoodsModel = new MallOrderGoods();
            $orderGoodsRow = $orderGoodsModel->alias('a')->join(['sm_product' =>'b'], 'a.goods_id=b.id', 'left')->where(['order_id' => $row->order_id])->field(['b.cover_img_url'])->find();
            $icon = SmProduct::getFormatImg($orderGoodsRow ? $orderGoodsRow->cover_img_url : '');
            $time = strtotime($row['create_time']);

            $data[] = [
                'id' => $row->id,
                'title' => $row->title,
                'content' => $row->content,
                'orderNo' => $row->order_no,
                'goodsName'=>$orderRow->goods_names,
                'release_time' => date('Y', $time) . '年' . date('m', $time) . '月' . date('d', $time) . '日 ' . date('H:i', $time),
                'icon' => $icon
            ];

        }

        //更新未读
        $userModel = new IndexUser();
        $userModel->save(['unread' => 0], ['id' => $this->userId]);

        return ['status' => 0, 'data' => ['list' => $data, 'total' => $total], 'msg' => ''];
    }

    /**
     * @desc 通知列表
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNoticeList(Request $request)
    {
        $pageSize = $request->post('pageSize', 10, 'intval');
        $pageNumber = $request->post('pageNumber', 1, 'intval');
        $pageSize = $pageSize > 10 ? 10 : $pageSize;

        $start = ($pageNumber - 1) * $pageSize;
        $model = new Notice();
        $total = $model->where(['status' => 1])->count();
        $rows = $model->where(['status' => 1])->order('release_time', 'desc')->field(['id', 'title', 'summary', 'release_time'])->limit($start, $pageSize)->select();

        foreach ($rows as &$row) {
            $row['release_time'] = date('Y', $row->release_time) . '年' . date('m', $row->release_time) . '月' . date('d', $row->release_time) . '日 ' . date('H:i', $row->release_time);
            $row['contentUrl'] = config('jzdc_domain').url('api/user/noticeDetail',['id'=>$row->id]);
        }
        return ['status' => 0, 'data' => ['list' => $rows, 'total' => $total], 'msg' => ''];
    }

    /**
     * @param Request $request
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getNoticeInfo(Request $request, $id)
    {
        $model = new Notice();
        $row = $model->where(['status' => 1, 'id' => $id])->order('release_time', 'desc')->field(['id', 'title', 'summary', 'content', 'release_time'])->find();

        if (!$row) {
            return ['status' => 1, 'data' => [], 'msg' => '公告不存在'];
        }

        $row['release_time'] = date('Y', $row->release_time) . '年' . date('m', $row->release_time) . '月' . date('d', $row->release_time) . '日 ' . date('H:i', $row->release_time);
        return ['status' => 0, 'data' => $row, 'msg' => ''];
    }

    /**
     * @desc 提交认证
     * @param Request $request
     * @return array
     */
    public function certification(Request $request)
    {
        $type = $request->post('type', 1, 'intval');
        $agent = $request->post('agent', 0, 'intval');
        $companyName = $request->post('companyName', '','htmlspecialchars'); //公司名称
        $representative = $request->post('representative', '','htmlspecialchars'); //代表人
        $property = $request->post('property', 0, 'intval'); //企业性质
        $capital = $request->post('capital', ''); //资金
        $detailAddress = $request->post('address', '','htmlspecialchars'); //住址

        $businessPath = $request->post('business', '');  //营业执照
        $permitsAccounts = $request->post('permitsAccount'); //用户许可
        $legalIdentityCard = $request->post('legalIdentityCard', ''); //法人身份证
        $agentIdentityCard = $request->post('agentIdentityCard', '');//代理人身份证

        $orgStructureCodePermits = $request->post('orgStructureCode', ''); //组织机构代码
        $taxRegistrationCert = $request->post('taxRegistrationCert', ''); //税务登记
        $powerOfAttorney = $request->post('attorney', ''); //代办人授权委托书

        if (!in_array($type, [1, 2])) {
            return ['status' => 1, 'data' => [], 'msg' => '注册类型错误'];
        }
        if (!$companyName) {
            return ['status' => 1, 'data' => [], 'msg' => '企业名称不能为空'];
        }
        if (!$representative) {
            return ['status' => 1, 'data' => [], 'msg' => '法人代表不能为空'];
        }
        if (!$businessPath) {
            return ['status' => 1, 'data' => [], 'msg' => '营业执照必须上传'];
        }
        if (!$permitsAccounts) {
            return ['status' => 1, 'data' => [], 'msg' => '用户许可必须上传'];
        }
        if (!$legalIdentityCard) {
            return ['status' => 1, 'data' => [], 'msg' => '法人身份证必须上传'];
        }

        if ($agent == 1) {
            if (!$agentIdentityCard) {
                return ['status' => 1, 'data' => [], 'msg' => '代理人身份证必须上传'];
            }
            if (!$powerOfAttorney) {
                return ['status' => 1, 'data' => [], 'msg' => '代办人授权委托书必须上传'];
            }
        }

        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $model = new FormUserCert();
        $row = $model->where(['writer' => $this->userId])->order('id', 'desc')->find();

        if($row && $row->status == 1) {
            return ['status' => 0, 'data' => [], 'msg' => '已提交审核，请勿重复提交...'];
        }

        //保存数据
        $data = [
            'edit_time' => time(),
            'writer' => $this->userId,
            'editor' => $this->userId,
            'company_name' => $companyName,
            'business_license' => $businessPath,
            'status' => 1,
            'reg_role' => $type == 2 ? '供应商' : '采购商',
            'ent_property' => getCompanyProperty($property),
            'reg_capital' => $capital,
            'legal_representative' => $representative,
            'legal_identity_card' => $legalIdentityCard,
            'agent_identity_card' => $agentIdentityCard,
            'permits_accounts' => $permitsAccounts,
            'org_structure_code_permits' => $orgStructureCodePermits,
            'tax_registration_cert' => $taxRegistrationCert,
            'detail_address' => $detailAddress,
            'power_attorney' => $powerOfAttorney
        ];

        if ($row) { //再次提交审核
            $result = $model->save($data, ['id' => $row->id]);
        } else { //
            $data['write_time'] = time();
            $result = $model->save($data);
        }

        if ($result !== false) {
            //发送短信通知
            $userModel = new IndexUser();
            $userInfo = $userModel->getInfoById($this->userId);
            if ($userInfo && $userInfo->phone) {
                $yunPian = new Yunpian();
                $yunPian->send($userInfo->phone, [], Yunpian::TPL_CERT_SUBMIT);
            }

            //发送邮件通知
            $emailStr = config('JZDC_OP_EMAIL');
            $subject='集众电采平台系统认证通知';
            $content='现有用户提交企业认证申请，请及时跟进，谢谢。';
            SendMail($emailStr,$subject,$content);

            ////////////////////////////////////写入新数据表//////////////////////////////////////////////
            $companyAuditModel = new EntCompanyAudit();
            if($userInfo->company_id > 0){  //更新
                $data = [
                    'company_name' => $companyName,
                    'business_licence_uri' => $businessPath,
                    'state' => EntCompanyAudit::STATE_PENDING,
                    'enterprise_type' => $property,
                    'reg_capital' => $capital,
                    'legal_representative' => $representative,
                    'organization_code_uri' => $orgStructureCodePermits,
                    'agent_id_card_uri' => $agentIdentityCard,
                    'tax_registration_uri' => $taxRegistrationCert,
                    'address' => $detailAddress,
                    'power_attorney_uri' => $powerOfAttorney,
                    'last_modified_user_id' => $this->userId,
                    'last_modified_user' => $userInfo->username,
                    'last_modified_time' => microtime(true)*1000,
                ];
                $companyAuditModel->save($data,['company_id'=>$userInfo->company_id]);
            }else{ //插入
                $data = [
                    'company_name' => $companyName,
                    'business_licence_uri' => $businessPath,
                    'state' => EntCompanyAudit::STATE_PENDING,
                    'enterprise_type' => $property,
                    'reg_capital' => $capital,
                    'legal_representative' => $representative,
                    'organization_code_uri' => $orgStructureCodePermits,
                    'agent_id_card_uri' => $agentIdentityCard,
                    'tax_registration_uri' => $taxRegistrationCert,
                    'address' => $detailAddress,
                    'power_attorney_uri' => $powerOfAttorney,
                    'last_modified_user_id' => $this->userId,
                    'last_modified_user' => $userInfo->username,
                    'last_modified_time' => microtime(true)*1000,
                    'created_user_id' => $this->userId,
                    'created_user' => $userInfo->username,
                    'created_time' => microtime(true)*1000
                ];
                $companyAuditModel->save($data);
            }

            return ['status' => 0, 'data' => [], 'msg' => '已提交认证信息,等待审核...'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '提交审核失败'];
    }

    /**
     * @desc 获取认证
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function getCertification()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $model = new FormUserCert();
        $row = $model->where(['writer' => $this->userId])->order('id', 'desc')->find();
        if (!$row) {
            return ['status' => 0, 'data' => ['status'=>0], 'msg' => '用户未提交认证'];
        }
        $data = [
            'status'=>1,
            'companyName' => $row->company_name,
            'representative' => $row->legal_representative,
            'capital' => $row->reg_capital,
            'address' => $row->detail_address,  //detail_address
            'property' => $row->ent_property,
            'role' => $row->reg_role,
            'status' => $row->status,
            'isAgent' => $row->agent_identity_card ? 1 : 0,
            'business' => $row->business_license ? $row->business_license : '',
            'permitsAccount' => $row->permits_accounts ? $row->permits_accounts : '',
            'legalIdentityCard' => $row->legal_identity_card ? $row->legal_identity_card : '',
            'agentIdentityCard' => $row->legal_identity_card ? $row->agent_identity_card : '',
            'orgStructureCode' => $row->org_structure_code_permits ? $row->org_structure_code_permits : '',
            'taxRegistrationCert' => $row->tax_registration_cert ? $row->tax_registration_cert : '',
            'attorney' => $row->power_attorney ? $row->power_attorney : '',
            'businessPath' => $row->business_license ? FormUserCert::getFormatImg($row->business_license) : '',
            'permitsAccountPath' => $row->permits_accounts ? FormUserCert::getFormatImg($row->permits_accounts) : '',
            'legalIdentityCardPath' => $row->legal_identity_card ? FormUserCert::getFormatImg($row->legal_identity_card) : '',
            'agentIdentityCardPath' => $row->legal_identity_card ? FormUserCert::getFormatImg($row->agent_identity_card) : '',
            'orgStructureCodePath' => $row->org_structure_code_permits ? FormUserCert::getFormatImg($row->org_structure_code_permits) : '',
            'taxRegistrationCertPath' => $row->tax_registration_cert ? FormUserCert::getFormatImg($row->tax_registration_cert) : '',
            'attorneyPath' => $row->power_attorney ? FormUserCert::getFormatImg($row->power_attorney) : '',
            'refuseReason' => $row->refuse_reason
        ];

        return ['status' => 0, 'data' => $data, 'msg' => ''];
    }

    //
    public function getCertificationExt(){
        $url = config('jzdc_domain').'/web/public/static/doc/template_attorney.docx';
        return ['status'=>0,'data'=>['attorney'=>$url],'msg'=>''];
    }

    /**
     * @desc 添加收货地址标签
     * @return array|void
     */
    public function addAddressTag(Request $request)
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $userId = $this->userId;
        $tag = $request->post('tag');

        if (!$tag) {
            return ['status' => 1, 'data' => [], 'msg' => '标签不允许为空'];
        }

        $model = new MallReceiverTag();
        $count = $model->where(['user_id' => $userId])->count();
        if ($count >= 10) {
            return ['status' => 1, 'data' => [], 'msg' => '最多添加10个标签'];
        }

        $same = $model->where(['user_id' => $userId, 'tag' => $tag])->find();
        if ($same) {
            return ['status' => 1, 'data' => [], 'msg' => '已经有相同的标签了'];
        }

        $data = [
            'time' => time(),
            'tag' => $tag,
            'user_id' => $userId,
        ];

        $result = $model->save($data);
        if ($result == true) {
            return ['status' => 0, 'data' => [], 'msg' => '添加成功'];
        }

        return ['status' => 1, 'data' => [], 'msg' => '添加失败'];
    }

    /**
     * @desc 删除收货地址标签
     * @return array|void
     */
    public function removeAddressTag(Request $request)
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $userId = $this->userId;
        $tagId = $request->post('id');

        if (!$tagId) {
            return ['status' => 1, 'data' => [], 'msg' => '标签不允许为空'];
        }

        $model = new MallReceiverTag();
        $result = $model->where(['user_id' => $userId, 'id' => $tagId])->delete();


        if ($result == true) {
            return ['status' => 0, 'data' => [], 'msg' => '删除成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '删除失败'];
    }

    /**
     * @desc 获取收货地址标签
     * @return array|void
     */
    public function getAddressTag()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $model = new MallReceiverTag();
        //$row = $model->where(['user_id' => $this->userId])->field(['tag', 'id'])->order('id', 'desc')->select();
        $row = $model->where(['user_id' => $this->userId])->whereOr(['is_default'=>1])->order('is_default','desc')->field(['tag', 'id'])->order('id', 'desc')->select();


        return ['status' => 0, 'data' => $row, 'msg' => ''];
    }

//解绑手机号
    public function bind(Request $request)
    {
        $phone = $request->post('phone', '');
        $id = $request->post('id', '');
        $code = $request->post('code', '');
        $oldCode = $request->post('oldCode', '');
        $newCode = $request->post('newCode', '');

        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        //验证
        if (!$phone) {
            return ['status' => 1, 'data' => [], 'msg' => '手机号不能为空'];
        }
        if (!$code) {
            return ['status' => 1, 'data' => [], 'msg' => '图片验证码不能为空'];
        }
        if (!$oldCode || !$newCode) {
            return ['status' => 1, 'data' => [], 'msg' => '短信验证码不能为空'];
        }
        if (!checkPhone($phone)) {
            return ['status' => 1, 'data' => [], 'msg' => '手机号错误'];
        }
        if (!captchaDb_check($code, $id)) {
            return ['status' => 1, 'data' => [], 'msg' => '图片验证码有误'];
        }

        //查询用户手机号
        $userModel = new IndexUser();
        $userInfo = $userModel->getUserByPhone($phone);
        if ($userInfo) {
            return ['status' => 1, 'data' => [], 'msg' => '改号码已被其他用户绑定'];
        }

        $oldInfo = $userModel->getInfoById($this->userId);
        if (!$oldInfo || !$oldInfo->phone) {
            return ['status' => 1, 'data' => [], 'msg' => '数据错误'];
        }

        //验证短信
        $codeModel = new \app\common\model\Code();
        $codeRow = $codeModel->where(['phone' => $oldInfo->phone, 'type' => \app\common\model\Code::TYPE_PHONE_BIND_OLD])->order('id', 'desc')->find();
        if (!$codeRow || $codeRow['code'] != $oldCode) {
            return ['status' => 1, 'data' => [], 'msg' => '短信验证码错误'];
        }
        if ($codeRow['expire_time'] < time()) {
            return ['status' => 1, 'data' => [], 'msg' => '短信验证已过期'];
        }

        $codeRow = $codeModel->where(['phone' => $phone, 'type' => \app\common\model\Code::TYPE_PHONE_BIND_NEW])->order('id', 'desc')->find();
        if (!$codeRow || $codeRow['code'] != $newCode) {
            return ['status' => 1, 'data' => [], 'msg' => '短信验证码错误'];
        }
        if ($codeRow['expire_time'] < time()) {
            return ['status' => 1, 'data' => [], 'msg' => '短信验证已过期'];
        }

        //更新数据
        $result = $userModel->save(['phone' => $phone], ['id' => $this->userId]);
        if ($result !== false) {
            return ['status' => 0, 'data' => [], 'msg' => '解绑成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '解绑失败'];
    }

//是否设置密码
    public function getPasswordStatus()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        //
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($this->userId);
        if (!$userInfo->password) {
            return ['status' => 0, 'data' => ['password' => 0], 'msg' => ''];
        }
        return ['status' => 0, 'data' => ['password' => 1], 'msg' => ''];
    }

    //初始化密码
    public function initPassword(Request $request)
    {
        $password = $request->post('password', '');
        $confirmPassword = $request->post('confirmPassword', '');
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        if (!$password || !$confirmPassword) {
            return ['status' => 1, 'data' => [], 'msg' => '密码不能设置为空'];
        }

        if (!checkPassword($password) || !checkPassword($confirmPassword)){
            return ['status'=>1,'data'=>[],'msg'=>'密码必须为6-20位的数字和字母组合'];
        }

        if ($password != $confirmPassword) {
            return ['status' => 1, 'data' => [], 'msg' => '两次密码不一致'];
        }




        $model = new IndexUser();

        $result = $model->save(['password' => md5($password)], ['id' => $this->userId]);
        if ($result !== false) {
            return ['status' => 0, 'data' => [], 'msg' => '修改成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '修改失败'];
    }

    //更新密码
    public function updatePassword(Request $request)
    {
        $password = $request->post('oldPassword', '');
        $newPassword = $request->post('newPassword', '');

        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        if (!$password || !$newPassword) {
            return ['status' => 1, 'data' => [], 'msg' => '密码不能设置为空'];
        }

        if (!checkPassword($newPassword)){
            return ['status'=>1,'data'=>[],'msg'=>'密码必须为4-20位的数字和字母组合'];
        }

        $model = new IndexUser();
        $userInfo = $model->getInfoById($this->userId);
        if ($userInfo->password != md5($password)) {
            return ['status' => 1, 'data' => [], 'msg' => '原密码不正确'];
        }

        $result = $model->save(['password' => md5($newPassword)], ['id' => $this->userId]);
        if ($result !== false) {
            return ['status' => 0, 'data' => [], 'msg' => '修改成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '修改失败'];
    }

    //检查邮箱状态
    public function getEmailStatus()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        //
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($this->userId);
        if (!$userInfo->email) {
            return ['status' => 0, 'data' => ['email' => 0], 'msg' => ''];
        }
        return ['status' => 0, 'data' => ['email' => 1], 'msg' => ''];
    }

    //初始化邮箱
    public function initEmail(Request $request)
    {
        $email = $request->post('email', '');
        $code = $request->post('code', '');
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        if (!$email) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱不能为空'];
        }
        if (!checkEmail($email)) {
            return ['status' => 1, 'data' => [], 'msg' => '无效的邮箱'];
        }
        //验证邮箱
        $codeModel = new EmailCode();
        $codeRow = $codeModel->where(['email' => $email, 'type' => EmailCode::TYPE_EMAIL_INIT])->order('id', 'desc')->find();
        if (!$codeRow || $codeRow['code'] != $code) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱验证码错误'];
        }
        if ($codeRow['expire_time'] < time()) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱验证已过期'];
        }

        $model = new IndexUser();
        $result = $model->save(['email' => $email], ['id' => $this->userId]);
        if ($result !== false) {
            return ['status' => 0, 'data' => [], 'msg' => '修改成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '修改失败'];
    }


    //更新邮箱
    public function updateEmail(Request $request)
    {
        $email = $request->post('email', '');
        $code = $request->post('code', '');
        $newCode = $request->post('newCode', '');

        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        if (!$email) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱不能设置为空'];
        }
        if (!$code || !$newCode) {
            return ['status' => 1, 'data' => [], 'msg' => '验证码不能为空'];
        }
        if (!checkEmail($email)) {
            return ['status' => 1, 'data' => [], 'msg' => '无效的邮箱'];
        }

        $model = new IndexUser();
        $userInfo = $model->getInfoById($this->userId);
        if (!$userInfo) {
            return ['status' => 1, 'data' => [], 'msg' => '数据异常'];
        }

        //验证邮箱
        $codeModel = new EmailCode();
        $codeRow = $codeModel->where(['email' => $userInfo->email, 'type' => EmailCode::TYPE_EMAIL_OLD])->order('id', 'desc')->find();
        if (!$codeRow || $codeRow['code'] != $code) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱验证码错误'];
        }
        if ($codeRow['expire_time'] < time()) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱验证已过期'];
        }

        $codeRow = $codeModel->where(['email' => $email, 'type' => EmailCode::TYPE_EMAIL_NEW])->order('id', 'desc')->find();
        if (!$codeRow || $codeRow['code'] != $code) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱验证码错误'];
        }
        if ($codeRow['expire_time'] < time()) {
            return ['status' => 1, 'data' => [], 'msg' => '邮箱验证已过期'];
        }


        $result = $model->save(['email' => $email], ['id' => $this->userId]);
        if ($result !== false) {
            return ['status' => 0, 'data' => [], 'msg' => '修改成功'];
        }
        return ['status' => 1, 'data' => [], 'msg' => '修改失败'];
    }


    /**
     * @desc 获取消息未读总数
     * @return array|void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function messageNumber()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }

        $model = new IndexUser();
        $userInfo = $model->getInfoById($this->userId);
        $total = $userInfo ? $userInfo->unread : 0;
        return ['status' => 0, 'data' => ['total' => $total], 'msg' => ''];
    }

    //删除消息
    public function removeMessage(Request $request){
        $msgId = $request->post('id');

        $auth = $this->auth();
        if($auth){
            return $auth;
        }

        $model = new OrderMsg();

        $result =$model->save(['is_delete'=>1],['user_id'=>$this->userId,'id'=>$msgId]);

        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'修改失败'];

    }

    //用户设置
    public function profile(Request $request)
    {
        $field = $request->post('field', '');
        $value = $request->post('value', '');

        if (!in_array($field, ['contact', 'icon', 'tel']) || !$value) {
            return ['status' => 1, 'data' => [], 'msg' => '参数错误'];
        }
        //
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        if(in_array($field, ['contact', 'tel'])){
            $model = new IndexUser();
            $result = $model->save([strtr($field,['contact'=>'nickname']) => $value], ['id' => $this->userId]);
            if ($result !== false) {
                return ['status' => 0, 'data' => [], 'msg' => '修改成功'];
            }
        }
        if(in_array($field, ['icon'])){
            //验证是否为企业管理员
            $companyId = $this->checkCompanyPermissionReturnCompanyId();
            if($companyId>0){
                $EntCompany = new EntCompany();
                $result = $EntCompany->save(['logo_uri' => $value], ['id' => $companyId]);
                if ($result !== false) {
                    return ['status' => 0, 'data' => [], 'msg' => '修改成功'];
                }
            }
        }

        return ['status' => 1, 'data' => [], 'msg' => '修改失败'];
    }

    //获取信息
    public function getProfile()
    {
        $auth = $this->auth();
        if ($auth) {
            return $auth;
        }
        $model = new IndexUser();
        $row = $model->getInfoById($this->userId);

        //1.0.3用户认证可通过企业邀请，引申出企业身份一对多人，所以企业信息需要分离
        $EntCompany = new EntCompany();
        $logo = $EntCompany->where(['id'=>$row->company_id])->value('logo_uri');
        $return = [
            'contact' => $row->nickname,
            'tel' => $row->tel ? $row->tel : '',
            'icon' => $logo ? $logo : '',
            'path' => $logo ? EntCompany::getFormatLogo($logo) : '',
            'phone' => $row->phone,
            'email' => $row->email,
            'username' => $row->username,
            'companyName' => $row->real_name
        ];

        return ['status' => 0, 'data' => $return, 'msg' => ''];
    }


    public function noticeDetail($id){
        $model = new Notice();
        $row = $model->find(['id'=>$id]);

        $view = new View();
        echo $view->fetch('index/notice_detail',['detail'=>$row ? getImgUrl($row->content) : '']);
    }
}