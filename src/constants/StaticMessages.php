<?php
namespace Skn036\Google\constants;
class StaticMessages
{
    const GOOGLE_CONFIG_NOT_SET = 'Google config not set. Run `php artisan vendor:publish --provider="Skn036\Google\GoogleClientServiceProvider"` to publish the config file.';
    const OAUTH_CODE_NOT_FOUND = 'Google auth redirect code is not present in the request.';
    const USER_NOT_AUTHENTICATED = 'Credentials per user is set on google config. But user is not authenticated in laravel app and user id is not given.';
}
