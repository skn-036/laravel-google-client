<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Gmail Configuration
    |--------------------------------------------------------------------------
    |
    | Available scopes:
    | Gmail: https://developers.google.com/gmail/api/auth/scopes
    |
    */

    'application_name' => env('GOOGLE_APPLICATION_NAME', null),
    'client_id' => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),
    'scopes' => ! empty(env('GOOGLE_SCOPES'))
        ? array_map(fn ($scope) => trim($scope), explode(',', env('GOOGLE_SCOPES')))
        : ['https://www.googleapis.com/auth/gmail.modify'],

    /**
     * Allow credentials per user
     */
    'credentials_per_user' => true,

    /**
     * Pub sub topic name
     */
    'pub_sub_topic' => env('GOOGLE_PUB_SUB_TOPIC'),
];
