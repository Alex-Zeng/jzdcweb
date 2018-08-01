<?php
namespace app\admin\controller;

use think\Controller;

class Index extends Base
{
    public function index()
    {	
    	$this->redirect('goods/index');
        return $this->fetch();
    }

    //版本发布
    public function version(){
    	$postType = input('post.postType','','trim');
    	switch ($postType) {
    		case 'add':
    			$data['title'] 			=input('post.title','','trim');
				$data['app_name'] 		=input('post.app_name','','trim');
				$data['force_version'] 	=input('post.force_version','','trim');
				$data['content'] 		=input('post.content','','trim');
				$data['up_time'] 		=input('post.up_time','','trim');
				$result = $this->validate($data,'Version.add');
				if(true !== $result){
				    return $this->errorMsg('100800',['replace'=>['__REPLACE__'=>$result]]);
				}

				$data['up_time']=strtotime($data['up_time']);
				$data['is_del']=1;
            	$data['add_time']=time();

            	if(db('version')->insert($data)!=1){
	                return $this->errorMsg('100801');
	            }

	            return $this->successMsg('reload',['msg'=>'发布新版成功']);
    			break;
    		case 'get':
    			$versionId 	=input('post.versionId','','intval');
    			$data = db('version')->field('version_id,title,app_name,force_version,content,up_time')->where(['version_id'=>$versionId])->find();

    			if($data){
    				$data['up_time'] = date('Y-m-d H:i:s',$data['up_time']);
    				return $this->successMsg('',['msg'=>'','versionList'=>$data]);
    			}else{
    				return $this->errorMsg('100802');
    			}
    			break;
    		case 'edit':
    			$data['version_id'] 	=input('post.version_id',0,'intval');
    			$data['title'] 			=input('post.title','','trim');
				$data['app_name'] 		=input('post.app_name','','trim');
				$data['force_version'] 	=input('post.force_version','','trim');
				$data['content'] 		=input('post.content','','trim');
				$data['up_time'] 		=input('post.up_time','','trim');
				$result = $this->validate($data,'Version.edit');
				if(true !== $result){
				    return $this->errorMsg('100803',['replace'=>['__REPLACE__'=>$result]]);
				}
    			
    			$dataVersion = db('version')->field('version_id')->where(['version_id'=>$data['version_id']])->find();
    			if(!$dataVersion){
    				return $this->errorMsg('100804');
    			}
    			
    			$data['up_time'] = strtotime($data['up_time']);
    			if(db('version')->update($data)){
    				return $this->successMsg('reload',['msg'=>'修改成功','versionList'=>$data]);
    			}else{
    				return $this->errorMsg('100805');
    			}
    			break;
    		default:
		    	$paramArr = input('');
		        $where    = [];

		        $aRes=db('version')
		                    ->field('version_id,title,app_name,force_version,content,up_time,add_time')
		                    ->where('is_del=1')
		                    ->order('version_id desc')
		                    ->paginate(50,false,['query'=>$paramArr]);

		        return view('',['data'=>$aRes]);
    			break;
    	}
    }
}
