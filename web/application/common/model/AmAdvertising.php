<?php
namespace app\common\model;
use think\Model;

class AmAdvertising  extends Base
{
    protected $table = 'am_advertising';

    public function getChannelsName($id){
    	$val = [1=>'IOS',2=>'Android',3=>'IOS/Android'];
    	if(isset($val[$id])){
    		return $val[$id];
    	}else{
    		return '';
    	}
    }

    /**
     * [getImg 获取图片地址]
     * @param  [type] $img [图片短地址]
     * @return [type]      [完整的访问地址]
     */
    public static function getImg($img){
        return $img ? config('jzdc_doc_path').'/advertising/'.$img : '';
    }
}