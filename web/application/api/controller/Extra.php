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
        $url = 'http://www.jizhongdiancai.com';
        $imgUrl = '';
        return ['status'=>0,'data'=>['type'=>1,'url'=>$url,'imgUrl' => $imgUrl]];
    }


}