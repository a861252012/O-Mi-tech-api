<?php
/**
 * 使用者帳號map關聯 資源庫
 * @author Weine
 * @date 2019/11/13
 */

namespace App\Repositories;


use App\Entities\UserAcc;

class UserAccRepository
{
	protected $userAcc;

	public function __construct(UserAcc $userAcc)
	{
		$this->userAcc = $userAcc;
	}

	public function getByUid($uid)
	{
		return $this->userAcc->where('uid', $uid)->get();
	}

	public function insertAcc($data)
	{
		return $this->userAcc->insertGetId($data);
	}

	public function updateAcc($where, $data)
	{
		return $this->userAcc->where($where)->update($data);
	}
}