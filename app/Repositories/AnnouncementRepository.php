<?php
/**
 * 登入公告 資源庫
 * @author Weine
 * @date 2020/1/2
 */

namespace App\Repositories;


use App\Entities\Announcement;

class AnnouncementRepository
{
    protected $announcement;

    public function __construct(Announcement $announcement)
    {
        $this->announcement = $announcement;
    }

    public function getListForActive($siteId)
    {
        return $this->announcement->where('active', 1)
                                    ->where('site_id', $siteId)
                                    ->orderBy('id', 'desc')->get();
    }
}