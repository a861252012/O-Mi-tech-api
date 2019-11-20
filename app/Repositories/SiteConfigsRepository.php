<?php
/**
 * 網站設定 資源庫
 * @author Weine
 * @date 2019/11/20
 */

namespace App\Repositories;


use App\Entities\SiteConfigs;

class SiteConfigsRepository
{
	protected $siteConfigs;

	public function __construct(SiteConfigs $siteConfigs)
	{
		$this->siteConfigs = $siteConfigs;
	}

	public function all()
	{
		return $this->siteConfigs->all();
	}

	public function getByCondition($where)
	{
		return $this->siteConfigs->where($where)->first();
	}
}