<?php
/**
 * Created by PhpStorm.
 * User: alvin
 * Date: 2018/8/28
 * Time: 16:14
 */

namespace app\admin\controller;

use app\common\model\IndexUser;
use app\common\model\SmCategorySpecAttrKey;
use app\common\model\SmCategorySpecAttrOptions;
use app\common\model\SmProductCategory;
use app\common\model\SmProductSpec;
use app\common\model\SmSpecCategory;
use app\common\model\SmSpecCategoryDetails;
use think\Request;

class SpecCategory extends Base{

    /**
     * @desc 规格列表
     * @return mixed
     */
    public function index($id){
        $model = new SmProductCategory();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            //跳转错误页面
            $this->errorTips();
        }

        //获取路径
        $depthPath = $row->depth_path;
        //分隔字符串
        $depthArr = explode('/',$row->depth_path);
        $depthArr = array_filter($depthArr);
        $option = [];
        foreach ($depthArr as  $kVal){
            $cate = $model->where(['id'=> $kVal])->find();
            $option[] = ['name'=>$cate->name];
        }

        $specKeyModel = new SmCategorySpecAttrKey();
        $specOptionModel = new SmCategorySpecAttrOptions();
        $specKeyRows = $specKeyModel->where(['category_id'=>$id,'is_deleted'=>0])->select();

        foreach ($specKeyRows as &$specKeyRow){
            $specOptionRows = $specOptionModel->where(['category_spec_attr_key_id'=>$specKeyRow->id,'is_deleted'=>0])->select();
            $optionInfo = '';
            $optionArr = [];
            foreach ($specOptionRows as $specOptionRow){
                $optionInfo .= '【'.$specOptionRow->spec_option_text .'】';
                $optionArr[] = ['id'=>$specOptionRow->id,'text'=>$specOptionRow->spec_option_text];
            }
            $specKeyRow['optionInfo'] = $optionInfo;
            $specKeyRow['optionJson'] = json_encode($optionArr,true);
        }

        $this->assign('list',$specKeyRows);
        $this->assign('categoryId',$id);
        $this->assign('position',$option);
        return $this->fetch();
    }

    /**
     * @desc 删除
     * @param Request $request
     * @param $id
     * @return array
     */
    public function delete(Request $request,$id){
        $model = new SmCategorySpecAttrKey();
        $row = $model->where(['id'=>$id])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'数据不存在'];
        }

        $userId = getUserId();
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($userId);


        $result = $model->save(['is_deleted'=>1,'deleted_user'=>$userInfo ? $userInfo->username : '','deleted_time'=>time()],['id'=>$id]);
        if($result !== false){
            return ['status'=>0,'data'=>[],'msg'=>'删除成功'];
        }
        return ['status'=>1,'data'=>[],'msg'=>'删除失败'];
    }



    /**
     * @desc 修改分类规格
     * @param Request $request
     * @param $id
     */
    public function edit(Request $request,$categoryId){
        $specKeyName = $request->post('specKeyName','trim');
        $specKeyId = $request->post('specKeyId',0,'intval');
        $showText = $request->post('showText/a');  //数组
        $specValIds = $request->post('specValIds/a'); //数组
        $model = new SmProductCategory();
        $row = $model->where(['id'=>$categoryId])->find();
        if(!$row){
            return ['status'=>1,'data'=>[],'msg'=>'参数错误'];
        }

        $specKeyModel = new SmCategorySpecAttrKey();
        $specOptionModel = new SmCategorySpecAttrOptions();

        //验证规格名称是否存在
        $specKeyRows = $specKeyModel->where(['category_id'=>$categoryId,'is_deleted'=>0,'spec_attr_key'=>$specKeyName,'id'=>['not in',[$specKeyId]]])->find();
        if($specKeyRows){
            return ['status'=>1,'data'=>[],'msg'=>'该分类规格名称已经存在'];
        }

        $userId = getUserId();
        $userModel = new IndexUser();
        $userInfo = $userModel->getInfoById($userId);

        if($specKeyId > 0){   //修改
            //更新名称
            $result = $specKeyModel->save(['spec_attr_key'=>$specKeyName,'last_modified_user_id'=>$userId,'last_modified_user'=>$userInfo->username,'last_modified_time'=>time()],['id'=>$specKeyId]);
            if($result !== false){
                //
                $newSpecValTexts = $existSpecValIds = $existSpecValMap= $updateSpecValIds = $delSpecValIds = $oldSpecValIds = [];
                for($j =0; $j < count($specValIds); $j++){
                    if($specValIds[$j] > 0){
                        $existSpecValIds[] = $specValIds[$j];
                        $existSpecValMap[$specValIds[$j]] = $showText[$j];
                    }
                    if($specValIds[$j] == 0){
                        $newSpecValTexts[] =['text' => $showText[$j]];
                    }
                }
                //查询原数据
                $oldSpecVals = $specOptionModel->where(['category_spec_attr_key_id'=>$specKeyId,'is_deleted'=>0])->select();
                foreach ($oldSpecVals as $oldSpecVal){
                  $oldSpecValIds[] = $oldSpecVal->id;
                }
                //获取删除的id
                $delSpecValIds = array_diff($oldSpecValIds,$existSpecValIds);
                $updateSpecValIds = array_intersect($oldSpecValIds,$existSpecValIds);

                //添加新元素
                if($newSpecValTexts){
                    for($i = 0; $i < count($newSpecValTexts); $i++) {
                        $insertT[] = [
                            'category_spec_attr_key_id' => $specKeyId,
                            'spec_option_text' => $newSpecValTexts[$i]['text'],
                            'created_user_id' => $userId,
                            'created_user' => $userInfo->username,
                            'created_time' => time()
                        ];
                    }
                    $specOptionModel->insertAll($insertT);
                }
                //删除原数据
                if($delSpecValIds){
                    $specOptionModel->save(['is_deleted'=>1,'deleted_user'=>$userInfo->username,'deleted_time'=>time()],['id'=>['in',$delSpecValIds]]);
                }
                //修改
                if($updateSpecValIds){
                    for ($n =0; $n < count($updateSpecValIds); $n++){
                        $specOptionModel->save(['spec_option_text'=>$existSpecValMap[$updateSpecValIds[$n]],'last_modified_user_id'=>$userId,'last_modified_time'=>time()],['id'=>$existSpecValIds[$n]]);
                    }
                }


/*
                //删除原数据
                $specOptionModel->where(['category_spec_attr_key_id'=>$specKeyId,'is_deleted'=>0])->delete();
                if($showText){
                    $deleteT = [];
                    for($i = 0; $i <count($showText); $i++){
                        $deleteT[] = [
                            'category_spec_attr_key_id' => $specKeyId,
                            'spec_option_text' => $showText[$i],
                            'created_user_id' => $userId,
                            'created_user'=>$userInfo->username,
                            'created_time'=>time()
                        ];
                    }
                    $specOptionModel->insertAll($deleteT);
                }
*/
                return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
            }

            return ['status'=>0,'data'=>[],'msg'=>'修改失败'];
        }else{ //新增
            $result = $specKeyModel->save(['category_id'=>$categoryId,'spec_attr_key'=>$specKeyName,'is_standard'=>0,'created_user_id'=>$userId,'created_user'=>$userInfo->username,'created_time'=>time()]);
            if($result){
                $insertT = [];
                for($i = 0; $i < count($showText); $i++){
                    $insertT[] = [
                        'category_spec_attr_key_id' => $specKeyModel->id,
                        'spec_option_text' => $showText[$i],
                        'created_user_id' => $userId,
                        'created_user'=>$userInfo->username,
                        'created_time'=>time()
                    ];
                }

                $specOptionModel->insertAll($insertT);
                return ['status'=>0,'data'=>[],'msg'=>'修改成功'];
            }

            return ['status'=>0,'data'=>[],'msg'=>'修改失败'];
        }

    }


}