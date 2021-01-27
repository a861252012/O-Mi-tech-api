<?php

namespace App\Console\Commands;

use App\Services\Game\RouletteService;
use App\Services\Safe\SafeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Redis;

class Test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test {act?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'CLI Test';

    const ROOM_AES_KEY = '29292f7aae467dac83be04761a9d8f38'; // md5('OmeyRoom!!')

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        set_time_limit(0);
        ini_set('assert.exception', 1);
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $act = $this->argument('act');
        if (method_exists($this, $act)) {
            $this->$act();
            exit;
        }
        echo 'Method not exists!';
    }

    private function aes()
    {
        $ss = resolve(SafeService::class);

        $testcases = [
            ['plain' => '123'],
            ['plain' => '{"A":1,"B":22}'],
            ['plain' => 'Hello world!'],
            ['plain' => '~!@#$%^&*()_+'],
        ];

        foreach ($testcases as $t) {
            $plain = $t['plain'];
            $enc = $ss->AESEncrypt($plain, self::ROOM_AES_KEY);

            $decoded = $ss->AESDecrypt($enc['base64_encrypted'], $enc['base64_iv'], self::ROOM_AES_KEY);
            assert($plain == $decoded, "'{$plain}'!='{$decoded}' <===");
        }
    }

    private function roulette()
    {
        $rs = resolve(RouletteService::class);

        $testcases = [
            ['cnt' => 100,   'batch' => 1],
            ['cnt' => 10000, 'batch' => 1],
            ['cnt' => 1000,  'batch' => 10],
        ];

        // batch test
        foreach ($testcases as $t) {
            $cnt = $t['cnt'];
            $batch = $t['batch'];
            echo "Play ${cnt}x$batch times...\n";
            $stats = [];
            $points = 0;
            for ($i = 0; $i < $cnt; ++$i) {
                $results = $rs->play($batch);
                foreach ($results as $result) {
                    $typeAmt = $result['type'].':'.$result['amount'];
                    if (isset($stats[$typeAmt])) {
                        $stats[$typeAmt] += 1;
                    } else {
                        $stats[$typeAmt] = 1;
                    }
                    if ($result['type'] == 1) {
                        $points += $result['amount'];
                    }
                }
            }
            print_r($stats);
            echo 'Total points: ', $points, "\n";
            echo 'Avg points:', $points / ($cnt * $batch), "\n\n";
        }
    }
}
