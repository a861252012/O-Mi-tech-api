<?php
/**
 * 代理域名 資源庫
 * @author Weine
 * @date 2020-12-3
 */

namespace App\Repositories;


use App\Entities\Domain;

class DomainRepository
{
    protected $domain;

    public function __construct(Domain $domain)
    {
        $this->domain = $domain;
    }

    public function getDataById($id)
    {
        return $this->domain->where('id', $id)->where('status', 1)->get();
    }
}