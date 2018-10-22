<?php
namespace app\common\model;

use think\Model;
use app\common\model\IndexUser;

class Base extends Model{

	/**
	 * [filedDefaultValue 增删改公共使用的数据]
	 * @param  [type] $action [create/update/delete]
	 * @return [type]         [description]
	 */
	public function filedDefaultValue($action=''){
		$return = [];
		switch ($action) {
			case 'create':
				$return = [
					'created_user_id'		=> getUserId(),
					'created_user'   		=> getUserName(),
					'created_time'			=> time(),
					'last_modified_user_id'	=> 0,
					'last_modified_user'   	=> '',
					'last_modified_time'   	=> 0,
					'is_deleted'			=> 0,
					'deleted_user'			=> '',
					'deleted_time'			=> 0

				];
				break;
			case 'update':
				$return = [
					'last_modified_user_id'=> getUserId(),
					'last_modified_user'   => getUserName(),
					'last_modified_time'   => time()
				];
				break;
			case 'delete':
				$return = [
					'is_deleted'	=> 1,
					'deleted_user'	=> getUserName(),
					'deleted_time'	=> time()
				];
		}
		return $return;
	}

	/**
	 * [tableDefaultValue 增删改公共使用的数据]
	 * @param  [string] $action [ create/update/delete]
	 * @param  [int] 	$userId
	 * @return [array]
	 */
	public function tableDefaultValue($action='',$userId){
		$return = [];
		$IndexUser = new IndexUser();
		$userName = $IndexUser->where(['id'=>$userId])->value('username');
		switch ($action) {
			case 'create':
				$return = [
					'created_user_id'		=> $userId,
					'created_user'   		=> $userName,
					'created_time'			=> round(microtime(true),3),
					'last_modified_user_id'	=> 0,
					'last_modified_user'   	=> '',
					'last_modified_time'   	=> 0,
					'is_deleted'			=> 0,
					'deleted_user'			=> '',
					'deleted_time'			=> 0

				];
				break;
			case 'update':
				$return = [
					'last_modified_user_id'=> $userId,
					'last_modified_user'   => $userName,
					'last_modified_time'   => round(microtime(true),3)
				];
				break;
			case 'delete':
				$return = [
					'is_deleted'	=> 1,
					'deleted_user'	=> $userName,
					'deleted_time'	=> round(microtime(true),3)
				];
		}
		return $return;
	}
}