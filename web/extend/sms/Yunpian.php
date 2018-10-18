<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/21
 * Time: 11:03
 */
namespace sms;

use think\Log;

class Yunpian{

/*
'apikey' => '11236d9bf0fe6a5bcc36adb74e47bf1c',
'validation_template' => '【集众电采】您的验证码是#code#。如非本人操作，请忽略本短信',
'validation_template_en' => '【集众电采】Your verification code is #code#',
'fail_email' => 'wushwu@qq.com',
'frequency_limit' => '1:1/3:2/5:3/10:6/30:10/60:15/1440:20/10080:30/43200:60',
'cert_suc' => '2274788',
'cert_submit' => '2274784',
'cert_fail' => '2274794',
'order_cancel'=> '2274878',
'order_pending_send'=> '2274796',
'order_send1'=> '2274856',
'order_send2'=> '2275804',
'order_supplier_confirm'=> '2274868',
'order_out_date'=> '2274858',
*/

    const TPL_CERT_SUC = '2274788';
    const TPL_CERT_SUBMIT = '2274784';
    const TPL_CERT_FAIL = '2274794';
    const TPL_ORDER_CANCEL = '2274878';
    const TPL_ORDER_PENDING_SEND = '2274796';
    const TPL_ORDER_SEND = '2274856';
    const TPL_ORDER_SEND2 = '2275804';
    const TPL_ORDER_SUPPLIER_CONFIRM = '2274868';
    const TPL_ORDER_OUT_DATE = '2274858';
    const TPL_ORGANIZATION_USER  = '2537412';
    const TPL_ORGANIZATION_ADMIN = '2537588';
    const CONTENT = '【集众电采】您的验证码是#code#。如非本人操作，请忽略本短信';
    private $apikey = '11236d9bf0fe6a5bcc36adb74e47bf1c';


    /**
     * @desc 发送短信验证码
     * @param $mobile
     * @param $param
     * @param $type
     * @return bool
     */
    public function send($mobile,$param,$type){

        $content = $type;
        //根据短信类型获取模板
        if($type != self::CONTENT){
            $content = $this->getTemplate($type);
        }else{
            $content =str_replace('#code#',$param['code'],self::CONTENT);
        }
        if(!$content){
            return false;
        }
        //如果是订单类型
        if(in_array($type,[self::TPL_ORDER_CANCEL,self::TPL_ORDER_PENDING_SEND,self::TPL_ORDER_SEND,self::TPL_ORDER_SEND2,self::TPL_ORDER_SUPPLIER_CONFIRM,self::TPL_ORDER_OUT_DATE,self::TPL_ORGANIZATION_USER,self::TPL_ORGANIZATION_ADMIN])){
            if(isset($param['order_id'])){
                $content=str_replace('#order_number#',$param['order_id'],$content);
            }
            if(isset($param['express_name']) && isset($param['express_code'])){
                $content=str_replace('#express_name#',$param['express_name'],$content);
                $content=str_replace('#express_code#',$param['express_code'],$content);
            }
            if(isset($param['supplier'])){
                $content=str_replace('#supplier#',$param['supplier'],$content);
            }
            if(isset($param['code'])){
                $content=str_replace('#code#',$param['code'],$content);
            }
            if(isset($param['member_phone'])){
                $content=str_replace('#member_phone#',$param['member_phone'],$content);
            }
        }

        //添加发送短信日志
        //调用发送接口
        return $this->sendSms($mobile,$content);
//        return true;
    }


    /**
     * @desc 返回短信模板内容
     * @param $templateId
     * @return bool
     */
    private function getTemplate($templateId){
        $ch = curl_init();
        $data = ['tpl_id'=>$templateId,'apikey'=>$this->apikey];
       // curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/tpl/get.json');
        curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/tpl/get.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $json_data = curl_exec($ch);
        $r = json_decode($json_data,true);
        if(isset($r['tpl_content'])){
            return $r['tpl_content'];
        }else{
            return false;
        }
    }

    /**
     * @desc 调用发送接口
     * @param $mobile
     * @param $context
     * @return bool
     */
    protected function sendSms($mobile,$content){
        $ch = curl_init();
        $data = ['text'=>$content,'apikey'=>$this->apikey,'mobile'=>$mobile];
        curl_setopt ($ch, CURLOPT_URL, 'https://sms.yunpian.com/v2/sms/single_send.json');
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $json_data = curl_exec($ch);
        Log::write("sms json data -------->".$json_data);
        $r = json_decode($json_data,true);
        if(isset($r['sid'])){
            return true;
        }else{
            //日志记录
            return false;
        }
    }

}