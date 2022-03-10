<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'direct' => [
        'DIRECT_API_KEY' => env('DIRECT_API_KEY'),
        'DIRECT_API_LOGIN' => env('DIRECT_API_LOGIN'),
    ],

    'metrika' => [
        'METRIKA_API_KEY' => env('METRIKA_API_KEY'),
    ],

    'comagic' => [
        'key' => env('COMAGIC_API_KEY')
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'mailganer' => [
        'key' => env('MAILGANER_API'),
        'sources' => explode(',', env('MAILGANER_SOURCE_IDS')),
    ],

    'roistat' => [
        'key' => env('ROISTAT_API_KEY')
    ]

];
