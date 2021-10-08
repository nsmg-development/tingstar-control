<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ScrapAll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scrap:all';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
     * @return int
     */
    public function handle()
    {
        $commands = [
            'scrap:youtube:channel',
            'scrap:youtube:keyword',
            'scrap:blog:naver',
            'scrap:instagram:channel',
            'scrap:instagram:keyword',
        ];

        foreach ($commands as $command) {
            $call = Artisan::call($command);

            if (!$call) {
                echo $command . ' failed' . PHP_EOL;
                continue;
            }
        }

        return 0;
    }
}
