<?php

namespace App\Console\Commands;

use App\Repositories\GameMapRepository;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class HqtGameId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hqt:game-id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '取得HQT遊戲id資料';
    
    protected $gameMapRepository;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(GameMapRepository $gameMapRepository)
    {
        parent::__construct();
        
        $this->gameMapRepository = $gameMapRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $settings = $this->hqtSetting();
        if (empty($settings)) {
            return '無設定值';
        }
        
        $param = [
            'action' => 'getgamenames',
            'channel'   => $settings->channel_id,
        ];
        
        $data = $param;

        /* 簽名 */
        ksort($data);
        $data['privatekey'] = $settings->private_key;

        $signArr = [];
        foreach ($data as $k => $v) {
            $signArr[] = "{$k}=>{$v}";
        }

        $signStr = implode('&', $signArr);
        $param['sign'] = md5($signStr);

        $apiUrl = $settings->host . "/eas/api/rest?" . http_build_query($param);
//        dd($apiUrl);
        
        $client = new Client();

        $apiResponse = json_decode($client->get($apiUrl)->getBody()->getContents());
        
        $insertData = collect($apiResponse->data)->map(function ($item, $key) {
            $item->game_id = $item->id;
            $item->gp_id = 'GPHQT';
            unset($item->id);
            return $item;
        });

        collect($insertData)->each(function ($item, $key) {
            $data = collect($item);
            $this->gameMapRepository->updateOrCreate(
                $data->only(['gp_id', 'game_id'])->all(),
                $data->only(['name'])->all()
            );
        });
        
        info('資料處理完成');
    }
    
    private function hqtSetting()
    {
        return json_decode(Redis::get('sc:hqt_game_setting:1'));
    }
}
