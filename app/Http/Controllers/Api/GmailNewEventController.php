<?php

namespace App\Http\Controllers\Api;

use App\Helpers\Api\Gmail;
use App\Http\Controllers\Controller;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;
use Throwable;

class GmailNewEventController extends Controller
{

    private string $appScriptForwardURL = 'https://script.google.com/macros/s/AKfycbxhEkN5cynANGWzp2Gy1m3UP49YQVOaYaKjbe-23xX33fb5EkWR-EMQODIlTv8us_QF/exec';
    private string $managerAlias = 'mail';
    private LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Log::build(['driver' => 'single', 'path' => storage_path('logs/debug.log')]);
    }

    /**
     * Get all new email notifications from 'mail@fluid-line.ru' Gmail account.
     * Implements logic from https://developers.google.com/gmail/api/guides/sync
     *
     * @throws Exception
     */
    public function __invoke( Request $request, Gmail $gmailAPI ): JsonResponse
    {
        $jsonData = $request->json()->all();
        $mailData = json_decode( base64_decode( $jsonData["message"]["data"] ) );
        $requestHistoryId = $mailData->historyId;

        $previousHistoryId = Cache::get('lastGmailHistoryId');
        if ( !$previousHistoryId ) {
            $previousHistoryId = $requestHistoryId;
        }

        $gmailAPI->setClient($this->managerAlias);
        $gmailAPI->setupService();

        $historyList = $gmailAPI->historyList( [
            'startHistoryId' => $previousHistoryId,
            'historyTypes' => ['messageAdded']
        ]);

        Cache::put('lastGmailHistoryId', $historyList->getHistoryId() );

        foreach ( $historyList->getHistory() as $historyItem ) {
            foreach ($historyItem->getMessagesAdded() as $addedMessage) {

                $lastHandledId = Cache::get('lastGmailHandledNewMessageId');
                $messageId = $addedMessage->getMessage()->id;

                if ( $lastHandledId === $messageId ) {
                    continue;
                }

                Cache::put('lastGmailHandledNewMessageId', $messageId);

                $message = $gmailAPI->messageById( $messageId );

                $payload = $message->getPayload();
                $headers = $payload->getHeaders();

                $to = $gmailAPI->getToAddress($headers);

                if ( !preg_match('#mail(?:\d+)[a-z]+@fluid-line\.ru#', $to) ){
                    continue;
                }

                preg_match('#mail(\d+)[a-z]+@fluid-line\.ru#', $to, $digitsInEmail);
                $digitsEmail =  'mail' . $digitsInEmail[1] . '@fluid-line.ru';

                preg_match('#mail(?:\d+)([a-z]+)@fluid-line\.ru#', $to, $endingLetters);

                $client = new Client();
                try {

                    DB::connection('gb_testfl')
                        ->insert('INSERT INTO roistat_emails (`gmail_id`, `to_address`, `date`) VALUES (?, ?, ?)',
                            [$messageId, $endingLetters[1], now()->toDateString()]);

                    $client->post($this->appScriptForwardURL, [ RequestOptions::JSON => ["messageId" => $messageId, "To" => $digitsEmail] ]);
                } catch ( Throwable $exception) {
                    $this->logger->error( $exception->getMessage() );
                }
            }
        }

        return response()->json();
    }

}
