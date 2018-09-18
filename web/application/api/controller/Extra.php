<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/9/17
 * Time: 10:49
 */

namespace app\api\controller;


class Extra
{

    /**
     * @desc IOS针对审核、非审核提取数据
     * @return array
     */
    public function factoring(){
        $audit = checkCurrentVersion();

        if($audit){  //待审状态
            $url = 'http://www.jizhongdiancai.com';
            $imgUrl = config('jzdc_doc_path').'/banner/h5.png';
        }else{
            $url = 'http://h5.jizhongdiancai.com/static/jzdc-services/finance.html';
            $imgUrl = config('jzdc_domain').'/web/public/static/img/financial_service.png';
        }

        return ['status'=>0,'data'=>['type'=> $audit ? 1 : 0,'url'=>$url,'imgUrl' => $imgUrl]];
    }


}