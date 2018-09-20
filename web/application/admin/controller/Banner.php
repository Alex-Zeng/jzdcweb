<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/5/23
 * Time: 11:54
 */

namespace app\admin\controller;

use app\common\model\SliderImg;
use app\common\model\AmAdvertising;
use think\Request;

class Banner extends Base {

    /**
     * @desc 列表页
     * @return mixed
     */
    public function index(Request $request){
        $model = new SliderImg();
        $k = Request::instance()->get('k','');
        $model->where(['group_id'=>27]);
        if(isset($k) && $k){
            $model->where('name|url','like','%'.$k.'%');
        }
        $rows = $model->field(['id','name','url','path','target','sequence','status','type'])->paginate(20,false,['query'=>request()->param()]);
        foreach ($rows as &$row){
            $row['path'] = SliderImg::getFormatImg($row['path']);
        }

        $this->assign('list',$rows);
        $this->assign('page',$rows->render());
        $this->assign('k',$k);
        return $this->fetch();
    }

    /**
     * @desc 添加banner
     * @param Request $request
     * @return array
     */
    public function create(Request $request){
        $title = $request->post('title');
        $link = $request->post('link');
        $type = $request->post('type');
        $target = $request->post('target');
        $path = $request->post('path');
        $sequence = $request->post('sequence',0);
        $status = $request->post('status',1);
        //验证数据
        if(!$title){
            return ['status'=>1,'data'=>'','msg'=>'标题必能为空'];
        }
        if(!$path){
            return ['status'=>1,'data'=>'','msg'=>'图片必须上传'];
        }
        //保存数据
        $model = new SliderImg();
        $data = ['group_id'=>27,'name'=>$title,'url'=>$link,'type'=>$type,'target'=>$target,'sequence'=>$sequence,'status'=>$status,'path'=>$path];
        $result = $model->save($data);
        if($result !== false){
            return ['status'=>0,'msg'=>'添加成功'];
        }
        return ['status'=>1,'添加失败'];
    }

    /**
     * @desc 修改
     * @param Request $request
     * @return array
     */
    public function edit(Request $request,$id){
        $title = $request->post('title');
        $link = $request->post('link');
        $type = $request->post('type',1);
        $target = $request->post('target','_blank');
        $path = $request->post('path','');
        $sequence = $request->post('sequence',0);
        $status = $request->post('status',1);
        //验证数据
        if(!$title){
            return ['status'=>1,'data'=>'','msg'=>'标题必能为空'];
        }
        if(!$path){
            return ['status'=>1,'data'=>'','msg'=>'图片必须上传'];
        }


        //保存数据
        $model = new SliderImg();
        $data = ['name'=>$title,'url'=>$link,'type'=>$type,'target'=>$target,'sequence'=>$sequence,'status'=>$status,'path'=>$path];
        $result = $model->save($data,['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'msg'=>'修改成功'];
        }
        return ['status'=>1,'msg'=>'修改失败'];
    }

    /**
     * @desc 删除banner
     * @param Request $request
     * @return array
     */
    public function delete(Request $request,$id){
        $model = new SliderImg();
        $result = $model->where(['id'=>$id])->delete();
        if($result == true){
            return ['status'=>0,'msg'=>'删除成功'];
        }

        return ['status'=>1,'msg'=>'删除失败'];
    }


    /**
     * @desc 获取banner
     * @param $id
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function get($id){
        $model = new SliderImg();
        $row = $model->where(['id'=>$id])->field(['name','url','target','sequence','status','path','type'])->find();
        if($row){
            $data = [
                'title'=>$row->name,
                'link'=>$row->url,
                'status'=>$row->status,
                'target'=>$row->target,
                'sequence'=>$row->sequence,
                'status'=>$row->status,
                'path' => $row->path,
                'preview'=>SliderImg::getFormatImg($row->path) ,
                'type'=>$row->type
            ];
            return ['status'=>0,'data'=>$data,'msg'=>''];
        }
        return ['status'=>1,'data'=>[],'msg'=>'数据异常'];

    }

    /**
     * [adCreate 广告图列表]
     * @return [type] [description]
     */
    public function adCreate(){
        if(request()->isPost()){
            $AmAdvertising  = new AmAdvertising();
            $name               = input('post.name','','trim');
            $redirection        = input('post.redirection','','trim');
            $channels           = input('post.channels/a',[]);
            $show_time_start    = input('post.show_time_start','','trim');
            $show_time_end      = input('post.show_time_end','','trim');
            $advertising_img_url = input('post.advertising_img_url','','trim');

            if(empty($name) || mb_strlen($name,'utf8')>100){
                return $this->errorMsg('101400');//msg00=>'广告图名称长度需在1-100字符'
            }
            if(empty($show_time_start) || empty($show_time_end)){
                return $this->errorMsg('101401');//msg01=>'展示时间不能为空'
            }
            $show_time_start = strtotime($show_time_start);
            $show_time_end   = strtotime($show_time_end);
            if($show_time_start>=$show_time_end){
                return $this->errorMsg('101402');//msg02=>'展示开始时间不能大于或等于展示结束时间'
            }
            if(empty($channels)){
                return $this->errorMsg('101403');//msg03=>'请选择发布渠道'
            }
            if(empty($advertising_img_url)){
                return $this->errorMsg('101404');//msg04=>'图片不能为空'
            }
            if($redirection!=''){
                if(substr($redirection,0,7)!='http://' && substr($redirection,0,8)!='https://'){
                    return $this->errorMsg('101405');//msg05=>'请输入带http或https的请求地址'
                }
            }

            $data = [
                'name'=> $name,
                'redirection'=> $redirection,
                'show_time_start'=> $show_time_start,
                'show_time_end'=> $show_time_end,
                'advertising_img_url'=> $advertising_img_url
            ];
            if(isset($channels['ios']) && isset($channels['android'])){
                $data['channels'] = 3;
            }elseif(isset($channels['ios']) && !isset($channels['android'])){
                $data['channels'] = 1;
            }elseif(!isset($channels['ios']) && isset($channels['android'])){
                $data['channels'] = 2;
            }else{
                $data['channels'] = 0;
            }

            $data_result = $AmAdvertising->data(array_merge($AmAdvertising->filedDefaultValue('create'),$data))->save();
            if($data_result){
                return $this->successMsg('Banner/adIndex',['msg'=>'添加成功']);
            }else{
                return $this->errorMsg('101406');//msg06=>'添加失败'
            }

        }
        return view();
    }

    /**
     * [adIndex 广告图管理]
     * @return [type] [description]
     */
    public function adIndex(){
        $where = [];
        $name = input('param.name','','trim');
        $channels = input('param.channels',0,'intval');

        if(!empty($name)){
            $where['name'] = ['like','%'.$name.'%']; 
            $this->assign('name',$name);
        }else{
            $this->assign('name','');
        }
        if($channels>0){
            $where['channels'] = $channels;
            $this->assign('channels',$channels);
        }else{
            $this->assign('channels',0);
        }
        $AmAdvertising = new AmAdvertising();
        $list = $AmAdvertising->where(['is_deleted'=>0])->order('id desc')->where($where)->paginate(20,false,['query'=>request()->param()]);
        $time = time();
        foreach ($list as $key => $val) {
            $list[$key]['channelsName'] = $AmAdvertising->getChannelsName($val['channels']);
            if($time>$val['show_time_start'] && $time>$val['show_time_end']){
                $state = '已下架';
            }elseif($time>=$val['show_time_start'] && $time<=$val['show_time_end']){
                $state = '上架中';
            }else{
                $state = '待上架';
            }
            $list[$key]['stateName'] = $state;
            $list[$key]['redirectionName'] = $val['redirection']==''?'否':'是';
            $list[$key]['img_url'] = $AmAdvertising->getImg($val['advertising_img_url']);
        }

        $this->assign('page',$list->render());
        $this->assign('list',$list);
        return view();
    }

    public function adEdit(){
       
        if(request()->isPost()){
            $AmAdvertising  = new AmAdvertising();
            $id = input('post.id',0,'intval');
            $name               = input('post.name','','trim');
            $redirection        = input('post.redirection','','trim');
            $channels           = input('post.channels/a',[]);
            $show_time_start    = input('post.show_time_start','','trim');
            $show_time_end      = input('post.show_time_end','','trim');
            $advertising_img_url = input('post.advertising_img_url','','trim');

            $id_result = $AmAdvertising->where(['id'=>$id,'is_deleted'=>0])->count();
            if(empty($id_result)){
                return $this->errorMsg('101507');//msg07=>'没有搜索到你要修改的广告'
            }
            if(empty($name) || mb_strlen($name,'utf8')>100){
                return $this->errorMsg('101500');//msg00=>'广告图名称长度需在1-100字符'
            }
            if(empty($show_time_start) || empty($show_time_end)){
                return $this->errorMsg('101501');//msg01=>'展示时间不能为空'
            }
            $show_time_start = strtotime($show_time_start);
            $show_time_end   = strtotime($show_time_end);
            if($show_time_start>=$show_time_end){
                return $this->errorMsg('101502');//msg02=>'展示开始时间不能大于或等于展示结束时间'
            }
            if(empty($channels)){
                return $this->errorMsg('101503');//msg03=>'请选择发布渠道'
            }
            if(empty($advertising_img_url)){
                return $this->errorMsg('101504');//msg04=>'图片不能为空'
            }
            if($redirection!=''){
                if(substr($redirection,0,7)!='http://' && substr($redirection,0,8)!='https://'){
                    return $this->errorMsg('101505');//msg05=>'请输入带http或https的请求地址'
                }
            }

            $data = [
                'name'=> $name,
                'redirection'=> $redirection,
                'show_time_start'=> $show_time_start,
                'show_time_end'=> $show_time_end,
                'advertising_img_url'=> $advertising_img_url
            ];
            if(isset($channels['ios']) && isset($channels['android'])){
                $data['channels'] = 3;
            }elseif(isset($channels['ios']) && !isset($channels['android'])){
                $data['channels'] = 1;
            }elseif(!isset($channels['ios']) && isset($channels['android'])){
                $data['channels'] = 2;
            }else{
                $data['channels'] = 0;
            }

            $data_result = $AmAdvertising->where(['id'=>$id])->update(array_merge($AmAdvertising->filedDefaultValue('update'),$data));
            if($data_result){
                return $this->successMsg('Banner/adIndex',['msg'=>'修改成功']);
            }else{
                return $this->errorMsg('101506');//msg06=>'添加失败'
            }

        }
        $id = input('param.id',0,'intval');
        $AmAdvertising = new AmAdvertising();

        $row = $AmAdvertising->where(['id'=>$id,'is_deleted'=>0])->find()->toArray();
        $channels = ['ios'=>0,'android'=>0];
        switch ($row['channels']) {
            case 1:
                $channels['ios'] = 1;
                break;
            case 2:
                $channels['android'] = 1;
                break;
            case 3:
                $channels['ios'] = 1;
                $channels['android'] = 1;
                break;
        }
        $row['channels'] = $channels; 
        $row['img_url'] = $AmAdvertising->getImg($row['advertising_img_url']);

        $this->assign('row',$row);
        return view();
    }

}