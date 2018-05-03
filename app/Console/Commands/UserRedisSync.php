<?php

namespace App\Console\Commands;

use App\Models\Users;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\Console\Helper\ProgressBar;

class UserRedisSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'userRedisSync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';
    protected $chunkSize = 20000;

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
        $minUid = 0;
        $bar = $this->output->createProgressBar($numRows = $this->getMaxRows());
        $bar->setRedrawFrequency($this->chunkSize);
        $bar->setOverwrite(true);
        $bar->start();
        while (true) {
            $chunk = Users::orderBy('uid')
                ->where('uid', '>', $minUid)
                ->take($this->chunkSize)
                ->get(['uid', 'nickname', 'username', 'site_id']);
            if (!$chunk->count()) break;
            $this->syncChunk($chunk);
            $minUid=$chunk->last()->uid;
            $bar->advance($chunk->count());
        }
        $bar->finish();

    }

    private function syncChunk(Collection $chunk)
    {
        Redis::pipeline(function ($pipe) use ($chunk) {
            $chunk->each(function ($user) use ($pipe) {
                $pipe->hset('husername_to_id:' . $user->site_id, $user->username, $user->uid);
                $pipe->hset('hnickname_to_id:' . $user->site_id, $user->nickname, $user->uid);
            });
        });
    }

    private function getMaxRows()
    {
        return Users::count();
    }
}
