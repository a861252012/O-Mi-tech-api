<?php

namespace App\Console\Commands;

use App\Services\Game\RouletteService;
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
        $act = $this->argument('act');
        if (method_exists($this, $act)) {
            $this->$act();
            exit;
        }
        echo 'Method not exists!';
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
