<?php

namespace App\Console\Commands;

use App\Helpers\Api\Gmail;
use Exception;
use Google_Client;
use Google_Service_Gmail;
use Illuminate\Console\Command;

class AuthGmailAccount extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'authorize-account:gmail {name}';

    /**
     * The console command description.
     */
    protected $description = 'That command authorize your gmail account for this app and save credentials as {name}.json in /credentials/google/gmail';

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle(): void
    {
        $clientName = $this->argument('name');

        $client = (new Gmail())->setupAuth();
        $tokenPath = base_path() . "/credentials/google/gmail/managers/$clientName.json";

        if ( file_exists($tokenPath) ) {
            $accessToken = json_decode(file_get_contents($tokenPath), true);
            $client->setAccessToken($accessToken);
        }

        // If there is no previous token, or it's expired.
        if ($client->isAccessTokenExpired()) {
            // Refresh the token if possible
            if ($client->getRefreshToken()) {
                $client->fetchAccessTokenWithRefreshToken($client->getRefreshToken());
            }
            //else fetch a new one.
            else {
                // Request authorization from the user.
                $authUrl = $client->createAuthUrl();
                printf("Откройте ссылку в браузере:\n%s\n\n", $authUrl);
                print 'Введите код верификации: ';

                $authCode = trim(fgets(STDIN));

                // Exchange authorization code for an access token.
                $accessToken = $client->fetchAccessTokenWithAuthCode($authCode);
                $client->setAccessToken($accessToken);

                // Check to see if there was an error.
                if (array_key_exists('error', $accessToken)) {
                    throw new Exception(join(', ', $accessToken));
                }
            }
            // Save the token to a file.
            if (!file_exists(dirname($tokenPath))) {
                mkdir(dirname($tokenPath), 0700, true);
            }
            file_put_contents( $tokenPath, json_encode($client->getAccessToken()) );
        }

        echo 'Успешно авторизован';
    }
}
