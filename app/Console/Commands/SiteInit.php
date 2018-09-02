<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SiteInit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'site:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '站点初始化';

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
        $this->call('flush:cache');
        $this->call('dir-chmod');
        $this->call('route:cache');
        $this->call('config:cache');
        $this->call('view:clear');
        $this->call('clear-compiled');
        $this->call('storage:link');
        $this->call('dir-chmod');
    }
}
