<?php
/**
 * Created by PhpStorm.
 * User: raby
 * Date: 2018/7/13
 * Time: 16:54
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;


class DirChmod extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'dir-chmod';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'chmod -R 777 *';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        exec("chmod -R 777 ".base_path());
        $this->info('The directory has  success.');
    }
}