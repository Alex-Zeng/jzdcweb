<?php
namespace app\common\model;

use think\Model;

class EntCompany extends Base{

	protected $table = 'ent_company';

    const STATE_PENDING = 1;  //待审核
    const STATE_REFUSED = 2; //拒绝
    const STATE_PASS = 3;  //通过
}