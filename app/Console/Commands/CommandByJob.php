<?php

namespace App\Console\Commands;

use App\Models\CommandJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class CommandByJob extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:by:job';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '등록된 Job 커맨드 실행';

    protected CommandJob $commandJob;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(CommandJob $commandJob)
    {
        parent::__construct();

        $this->commandJob = $commandJob;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        // 등록된 커맨드 체크
        $jobs = $this->commandJob->get();

        foreach($jobs as $job) {
            $call = Artisan::call($job->command, ['keywordId' => $job->target_id, 'jobId' => $job->id]);

            if (!$call) {
                echo $job->command . ' failed' . PHP_EOL;
                continue;
            }
        }

        return 0;
    }
}
