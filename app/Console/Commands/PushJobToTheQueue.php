<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use ReflectionException;

class PushJobToTheQueue extends Command
{
    protected $signature = 'job:push {--sync} {jobClassName} {jobArguments*}';

    protected $description = 'Push selected job in jobs table for execution after 1 second';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * @throws ReflectionException
     */
    public function handle(): int
    {

        $jobClassName = "App\Jobs\\" . $this->argument('jobClassName');

        if ( !class_exists($jobClassName) ) {
            $this->error("Class $jobClassName doesn`t exist");
            return 0;
        }

        $jobConstructorArgs = [];

        foreach ( $this->arguments()['jobArguments'] as $passedArgument ) {
            $jobConstructorArgs[] = $passedArgument;
        };

        if ( !$this->option('sync') ) {
            $job = forward_static_call_array([$jobClassName, "dispatch"], $jobConstructorArgs);
            $job->delay( now()->addSecond() );

            echo "Job pushed to the jobs table";
            return 0;
        }

        forward_static_call_array([$jobClassName, "dispatchSync"], $jobConstructorArgs);

        return 0;
    }
}
