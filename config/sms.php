<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SMS Gateway
    |--------------------------------------------------------------------------
    |
    | For Dagu SMS:
    | SMS_BASE_URL=https://sms.adamacity.gov.et:4000
    | SMS_SEND_PATH=/api/send-sms
    |
    | If Dagu gives you another endpoint, only change SMS_SEND_PATH.
    |
    */

    'enabled' => env('SMS_ENABLED', true),

    'base_url' => env('SMS_BASE_URL', ''),

    'send_path' => env('SMS_SEND_PATH', '/api/send-sms'),

    'token' => env('SMS_TOKEN', env('SMS_API_KEY', '')),

    'method' => strtolower(env('SMS_METHOD', 'post')),

    'phone_field' => env('SMS_PHONE_FIELD', 'phone'),

    'message_field' => env('SMS_MESSAGE_FIELD', 'message'),

    'token_field' => env('SMS_TOKEN_FIELD', 'token'),

    'token_type' => env('SMS_TOKEN_TYPE', 'Bearer'),

    'token_location' => env('SMS_TOKEN_LOCATION', 'header'), // header|body|query

    'sender_field' => env('SMS_SENDER_FIELD', 'sender'),

    'sender' => env('SMS_SENDER', env('APP_NAME', 'MESOB')),
];
