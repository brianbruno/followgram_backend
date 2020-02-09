<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\MakeLikes;
use App\Jobs\MakeFollowers;

class MakeMagic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:magic';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Magic!';

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
        //MakeLikes::dispatchNow();
        MakeFollowers::dispatchNow();
    }
}
