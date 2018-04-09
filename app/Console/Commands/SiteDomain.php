<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\Site\SiteService;
use Illuminate\Console\Command;

class SiteDomain extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'site:domain {action? : 操作} {--S|site=* : 站点ID，支持多个}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '站点域名缓存管理';

    public $actions = [
        'flush',
        'sync',
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
                $sites->each([$this, 'flushDomain']);
                break;
            case 'sync':
                $sites->each([$this, 'syncDomainToCache']);
                break;
            default:

        }
    }

    public function syncDomainToCache(Site $site)
    {
        return $this->siteService->syncDomainForSite($site);
    }

    public function flushDomain(Site $site)
    {
        return $this->siteService->flushDomainCacheForSite($site);
    }
}
