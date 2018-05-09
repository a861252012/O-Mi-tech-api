<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\Site\SiteService;
use Illuminate\Console\Command;

class SiteConfig extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'site:config {action? : 操作} {--S|site=* : 站点ID，支持多个}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '站点配置缓存管理';

    public $actions = [
        'flush',
        'sync',
        'verify',
    ];
    /**
     * @var SiteService
     */
    private $siteService;

    /**
     * Create a new command instance.
     *
     * @param SiteService $siteService
     */
    public function __construct(SiteService $siteService)
    {
        parent::__construct();
        $this->siteService = $siteService;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $action = $this->argument('action');
        if (!$action) {
            $action = $this->anticipate('Please enter action: [' . join(', ', $this->actions) . ']', $this->actions);
        }
        $this->info($action);
        $siteIDs = $this->option('site');

        if (empty($siteIDs)) {
            $this->info('Site id not provided, assuming all sites');
            $sites = Site::all();
        } else {
            $sites = Site::whereIn('id', $siteIDs)->get();
        }
        $this->table(['id', 'name'], $sites->toArray());
        switch ($action) {
            case 'flush':
                $sites->each([$this, 'flushConfig']);
                break;
            case 'sync':
                $sites->each([$this, 'syncConfigToCache']);
                break;
            case 'verify':
                $sites->each([$this, 'verifyConfigCache']);
                break;
            default:

        }
    }

    public function syncConfigToCache(Site $site)
    {
        return $this->siteService->syncConfigForSite($site);
    }

    public function flushConfig(Site $site)
    {
        return $this->siteService->flushConfigCacheForSite($site);
    }

    public function verifyConfigCache(Site $site)
    {
        $this->info('Verifying site ' . $site->name);
        $configArray = $this->siteService->getDBConfigArrayForSite($site);
        $cache = $this->siteService->getConfigBySiteID($site->id)->all();
        $intersect = array_intersect_assoc($configArray, $cache);
        $a = array_diff_assoc($cache, $intersect);
        $b = array_diff_assoc($configArray, $intersect);
        $union = [];
        foreach ($a as $k => $v) {
            $union[] = [
                'name' => $k,
                'db' => '🗙',
                'cache' => '✓',
            ];
        }
        foreach ($b as $k => $v) {
            $union[] = [
                'name' => $k,
                'db' => '✓',
                'cache' => '🗙',
            ];
        }
        $this->table(['字段', '数据库', '缓存'], $union);
    }
}
