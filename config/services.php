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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sms' => [
        'endpoint' => env('SMS_ENDPOINT'),
        'api_key'  => env('SMS_API_KEY'),
    ],

    'orange_money' => [
        'url'             => env('ORANGE_MONEY_URL'),
        'client_id'       => env('ORANGE_MONEY_CLIENT_ID'),
        'client_secret'   => env('ORANGE_MONEY_CLIENT_SECRET'),
        'merchant_key'    => env('ORANGE_MONEY_MERCHANT_KEY'),
    ],

    'moov_money' => [
        'url'     => env('MOOV_MONEY_URL'),
        'api_key' => env('MOOV_MONEY_API_KEY'),
    ],

];
