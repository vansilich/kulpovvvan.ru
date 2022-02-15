<?php

namespace App\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class ParseManagersGmailsJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private array $domains;

    /**
     * Create a new job instance.
     *
     * @param array
     * @return void
     */
    public function __construct( array $domains )
    {
        $this->domains = $domains;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws Exception
     */
    public function handle(): void
    {
        $email_regexps = $this->setRegexps();

        ParseGmailsChunksJob::dispatch(
            $email_regexps,
        )->delay( now()->addSecond() );
    }

    /**
     * Set to every given domain regexp for searching it and itself of 1 level up in mail text
     */
    private function setRegexps(): array
    {
        $email_regexps = [];
        foreach ($this->domains as $domain) {
            $email_regexps[ $domain ] = "#(([a-zA-Z0-9_-]+\.)*[a-zA-Z0-9_-]+@([a-zA-Z0-9_-]+\.)?" . preg_quote($domain) . ")+#um";
        }

        return $email_regexps;
    }

}
