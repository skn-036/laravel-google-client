<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, When user redirects to google
    | login page, this name will be displayed as the title of your application.
    |
    */
    'application_name' => env('GOOGLE_APPLICATION_NAME', null),

    /*
    |--------------------------------------------------------------------------
    | Client ID
    |--------------------------------------------------------------------------
    |
    | Client id from google developers console.
    |
    | https://console.cloud.google.com/apis/credentials/oauthclient
    |
    */
    'client_id' => env('GOOGLE_CLIENT_ID'),

    /*
    |--------------------------------------------------------------------------
    | Client Secret
    |--------------------------------------------------------------------------
    |
    | Client secret from google developers console.
    |
    | https://console.cloud.google.com/apis/credentials/oauthclient
    |
    */
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Redirect Uri
    |--------------------------------------------------------------------------
    |
    | After successful authentication from google, the redirect url in your app
    | where the user will be redirected.
    |
    | https://console.cloud.google.com/apis/credentials/oauthclient
    |
    */
    'redirect_uri' => env('GOOGLE_REDIRECT_URI'),

    /*
    |--------------------------------------------------------------------------
    | Authentication Scopes
    |--------------------------------------------------------------------------
    |
    | Scopes that user will give permission to his google account. If the scope 
    | has changed, user will need to login again to reflect the changed scopes.
    | The scopes should be space separated.
    |
    | Available scopes:
    | Gmail: https://developers.google.com/gmail/api/auth/scopes
    |
    */
    'scopes' => !empty(env('GOOGLE_SCOPES'))
        ? array_map(fn($scope) => trim($scope), explode(' ', env('GOOGLE_SCOPES')))
        : [],

    /*
    |--------------------------------------------------------------------------
    | Credentials Per User
    |--------------------------------------------------------------------------
    |
    | Configuration to save tokens for every user in your application.
    | If this configuration is set to true, every user in you application can sync their own google account.
    | Otherwise, only one account can be synced with google throughout your application.
    |
    */
    'credentials_per_user' => true,

    /*
    |--------------------------------------------------------------------------
    | Multiple Accounts Per User
    |--------------------------------------------------------------------------
    |
    | Configuration to sync multiple google accounts per user. 
    | For this configuration to work, credentials_per_user should be set to true.
    |
    */
    'multiple_accounts_per_user' => false,

    /*
    |--------------------------------------------------------------------------
    | Should Encrypt Credentials
    |--------------------------------------------------------------------------
    |
    | Configuration to encrypt the credentials before saving to the credentials file. 
    | In production, this configuration should be set to true.
    |
    */
    'should_encrypt_credentials' => true,

    /*
    |--------------------------------------------------------------------------
    | Pub Sub Topic
    |--------------------------------------------------------------------------
    |
    | Google uses Pub Sub to send realtime notifications to the application,
    | if anything changed to subscribed resources.
    | 
    | For details: https://cloud.google.com/pubsub/docs/overview
    | For enabling: https://console.cloud.google.com/cloudpubsub/topic/list
    |
    */
    'pub_sub_topic' => env('GOOGLE_PUB_SUB_TOPIC'),

    /*
    |--------------------------------------------------------------------------
    | skn036/laravel-gmail-api Configuration (ignore if not using)
    |--------------------------------------------------------------------------
    |
    */
    'gmail' => [
        /*
        |--------------------------------------------------------------------------
        | Attachment saving path
        |--------------------------------------------------------------------------
        |
        | This path should be relative to the root of the filesystem disk in the filesystems.php config file.
        |
        */
        'attachment_path' => 'gmail-attachments',
    ],
];
