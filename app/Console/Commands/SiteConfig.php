<?php

namespace App\Console\Commands;

use App\Models\Site;
use App\Services\Site\SiteService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

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

    /**
     * 配置文件过滤键值：一站，二站! 区分站点ID
     * @var array
     */
    public $filter = [
        //一站
        '1'=>['database','redis','mail','workman','debug','template','obs','redis_ip','redis_port','redis_password','page-size','database_password'],
        //二站
        '2'=>['database','redis','mail','workman','debug','template','obs','redis_ip','redis_port','redis_password','page-size','database_password'],
    ];
    public $actions = [
        'flush',
        'sync',
        'verify',
        'insert',
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
            case 'insert':
                $sites->each([$this, 'insert']);
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

    public function insert(Site $site){
        $site_id = $site->id;
        $insertData = [];
        $siteConfig = $this->getConfig($site_id);
        $bar = $this->output->createProgressBar(count($siteConfig));

        $hasConfig = $site->config()->get();
        $hasKeyValues =   array_combine($hasConfig->pluck('k')->toArray(),$hasConfig->pluck('v')->toArray());

        foreach ($siteConfig as $key =>$value){
            if(in_array($key,$this->filter[$site_id])) continue;
            if(array_key_exists($key,$hasKeyValues)){
                Storage::disk('local')->append($this->getRepeatFile(),"\n站点：$site_id \nconfig file $key : ".$this->conversion($value)."\ndb $key : ".$this->conversion($hasKeyValues[$key]));
                continue;
            }
            if($value===""){
                $this->info("site $site_id $key");
                continue;
            }
            $config = [
                'site_id'=>$site_id,
                'k'=>$key,
                'v'=>$this->conversion($value),
                'type'=>$this->getType($value),
                'client'=>0b1,
                'created'=>Carbon::now(),
            ];
            array_push($insertData,$config);
            $bar->advance();
        }
        //dd($insertData);
        \App\Models\SiteConfig::query()->insert($insertData);
        $bar->finish();

        $this->info('finish '.count($insertData));
    }
    public function conversion($value){
        return is_array($value) ? json_encode($value) : $value;
    }
    public function getType($value){
        return is_array($value) ? 'json' : (is_int($value) ? 'int' : (is_bool($value) ? 'bool' : 'string'));
    }

    public function getConfig($site_id):array {
        $configArr = $this->specifyConfigs($site_id);
        return $configArr;
    }

    /**
     * 配置文件差异化特殊化处理
     * @param $site_id
     * @return array|mixed
     */
    public function specifyConfigs($site_id){
        $admin = [];
        $front = [];
        switch ($site_id){
            case '1' :
                define('__ROOT_DIR__','');
                $front = include storage_path('app/config').DIRECTORY_SEPARATOR."php-conf-cache.php";
                include storage_path('app/config').DIRECTORY_SEPARATOR."ParamConf.php";
                $admin = @$config;
                break;
            case '2' :
                define('BASEDIR','');
                $_SERVER['HTTP_HOST'] = '';
                $front1 = include storage_path('app/config').DIRECTORY_SEPARATOR."v2_config.php";
                $front2 = include storage_path('app/config').DIRECTORY_SEPARATOR."config.php";
                $admin1 = include storage_path('app/config').DIRECTORY_SEPARATOR."v2a_config.php";
                $admin2 = include storage_path('app/config').DIRECTORY_SEPARATOR."v2_admin.php";        //重命名
                $front = array_merge($front2,$front1);
                $admin = array_merge($admin2,$admin1);
                break;
            default:;
        }
        $admin = array_change_key_case($admin,CASE_LOWER);
        $front = array_change_key_case($front,CASE_LOWER);
        $this->checkRepeat($front,$admin,$site_id);
        $temp = array_merge($admin,$front);
        return $temp;
    }
    public function checkRepeat($front,$admin,$site_id){
        foreach ($front as $key => $value){
            if(in_array($key,$this->filter[$site_id])) continue;
            if(array_key_exists($key,$admin)){
                Storage::disk('local')->append($this->getRepeatFile(),"\n站点：$site_id \nfront $key : ".$this->conversion($value)."\nadmin $key : ".$this->conversion($admin[$key]));
                $this->error("前后台存在重复键名 $key");
            }
        }
    }
    public function getRepeatFile(){
        return 'repeat.txt';
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
