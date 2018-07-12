<?php

namespace App\Console\Commands\Schedule;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use App\Services\Site\SiteService;
class AnchorSearch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'anchor_search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $siteService;
    /**
     * Create a new command instance.
     *
     * @return void
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
        $this->siteService->getIDs()
            ->each([$this, 'handleForSite']);
    }
    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handleForSite($id)
    {
        $this->siteService->fromID($id);
        if (!$this->siteService->isValid()){
            $this->info('invalid site config for id '.$id);
            Log::error('invalid site config  ',['id'=>$id]);
            return;
        }
        //
        $_redisInstance = Redis::resolve();

        $flashVer = $this->siteService->config('publish_version');
        !$flashVer && $flashVer = 'v201504092044';
        $this->info('publish_version:' . $flashVer);
//home_all_,home_rec_,home_ord_,home_gen_,home_vip_
        $conf_arr = array(
            'home_all_' => array('所有主播', 'all'),
            'home_rec_' => array('小编推荐', 'rec'),
            'home_ord_' => array('一对一房间', 'ord'),
            'home_gen_' => array('才艺主播', 'gen'),
            //'home_vip_' => array('会员专区', 'vip'),
        );
//$json = '{';
        foreach ($conf_arr as $key => $item) {
            $data =  Redis::get($key . $flashVer.':'. $this->siteService->siteId());

            if ($key = 'home_all_') {
                $data = str_replace(array('cb(', ');'), array('', ''), $data);
                $myfav = json_decode($data, true);
                break;
            }
        }

        if ($myfav) {
            $myfav_arr = array();
            foreach ($myfav['rooms'] as $item) {
                $myfav_arr[$item['username']] = $item;
            }
        }

        
        if(isset($myfav_arr) && is_array($myfav_arr)){
            usort($myfav_arr, $this->make_comparer(['live_status', SORT_DESC], ['attens', SORT_DESC], ['lv_exp', SORT_DESC]));
            //todo 考虑改成redis存储
            Storage::put('cache/anchor-search-data.php','<?php ' . PHP_EOL . 'return ' . preg_replace('/[\s' . PHP_EOL . ']+/m', '', var_export($myfav_arr, true)) . ';');
        }

    }

    public function make_comparer()
    {
        // Normalize criteria up front so that the comparer finds everything tidy
        $criteria = func_get_args();
        foreach ($criteria as $index => $criterion) {
            $criteria[$index] = is_array($criterion)
                ? array_pad($criterion, 3, null)
                : array($criterion, SORT_ASC, null);
        }

        return function ($first, $second) use (&$criteria) {
            foreach ($criteria as $criterion) {
                // How will we compare this round?
                list($column, $sortOrder, $projection) = $criterion;
                $sortOrder = $sortOrder === SORT_DESC ? -1 : 1;

                // If a projection was defined project the values now
                if ($projection) {
                    $lhs = call_user_func($projection, $first[$column]);
                    $rhs = call_user_func($projection, $second[$column]);
                } else {
                    $lhs = $first[$column];
                    $rhs = $second[$column];
                }

                // Do the actual comparison; do not return if equal
                if ($lhs < $rhs) {
                    return -1 * $sortOrder;
                } else if ($lhs > $rhs) {
                    return 1 * $sortOrder;
                }
            }

            return 0; // tiebreakers exhausted, so $first == $second
        };
    }
}
