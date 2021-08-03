<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class Facebook extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:facebook';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '페이스북 크롤링';

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
     */
    public function handle()
    {

    }
}
