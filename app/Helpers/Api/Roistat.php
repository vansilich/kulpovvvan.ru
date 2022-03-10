<?php

namespace App\Helpers\Api;

use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;

class Roistat
{

    private string $api_endpoint = 'https://cloud.roistat.com/api/v1';
    private string $api_key;
    private int $project_id = 142411;

    public function __construct()
    {
        $this->api_key = config('services.roistat.key');
    }

    public function callList()
    {
        $params = [
            'key' => $this->api_key,
            'project' => $this->project_id
        ];
        $url = $this->api_endpoint . '/project/calltracking/call/list?' . urldecode( http_build_query($params) );

        $client = new Client();

        $response = $client->post($url, [
            RequestOptions::JSON => [
                "extend" => ["visit", "order"],
                'limit' => 1000
            ]
        ]);


        $data = json_decode($response->getBody()->getContents())->data;
        foreach ($data as $item) {
            dump($item);
        }
        die();
    }

    public function emailList()
    {
        $params = [
            'key' => $this->api_key,
            'project' => $this->project_id
        ];
        $url = $this->api_endpoint . '/project/emailtracking/email/list?' . urldecode( http_build_query($params) );

        $client = new Client();

        $response = $client->post($url, [
            RequestOptions::JSON => [
                'limit' => 1000
            ]
        ]);

        $data = json_decode($response->getBody()->getContents())->data;
        dd($data);
        foreach ($data as $item) {
            if ($item->visit) {
            }
        }
        die();
    }

}
