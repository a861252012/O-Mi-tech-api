<?php

namespace App\Console\Commands;

use App\Entities\UserHost;
use App\Models\Users;
use App\Services\GuardianService;
use App\Services\System\SystemService;
use Illuminate\Console\Command;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class GuardianCover extends Command
{
    protected $users;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'guardian:cover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '守護功能 - 主播海報資料建立';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $cursor = null;

        do {
            $userHostIds = Redis::hscan('hroom_ids', $cursor);
            $cursor = $userHostIds[0];

            foreach ($userHostIds[1] as $k => $v) {
                /* 取得用戶資料 */
                $user = Users::find($k);
                if (empty($user) || empty($user->cover)) {
                    $this->info('查無用戶資料，故略過');
                    continue;
                }

                /* 檔案上傳 */
                /* 因應zimg上傳方法需做resource處理 */
                $fo = fopen(Storage::path('uploads/s88888/anchor/' . $user->cover), 'r');
                if (empty($fo)) {
                    $this->info('用戶ID (' . $user->uid . ') 無法取得海報檔案，故略過');
                    continue;
                }

                /* 主播海報檔案處理並更新主播資料 */
                $imgData = resolve(SystemService::class)->upload($user, $fo);
                fclose($fo);

                if (empty($imgData['ret'])) {
                    $this->info('zimg上传失败');
                }

                /* 更新主播海報資訊 */
                UserHost::insert(['id' => $user->uid, 'cover' => $imgData['info']['md5']]);
            }

        } while ($cursor);
    }
}
