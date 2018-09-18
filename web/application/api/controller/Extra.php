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
     * @desc
     * @return array
     */
    public function factoring(){
        $audit = checkCurrentVersion();
        $url = 'http://www.jizhongdiancai.com';
        $imgUrl = config('jzdc_doc_path').'/banner/h5.png';
        return ['status'=>0,'data'=>['type'=> $audit ? 1 : 0,'url'=>$url,'imgUrl' => $imgUrl]];
    }


}