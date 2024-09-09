# Wrapper Around Google Client For Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/skn-036/laravel-google-client.svg?style=flat-square)](https://packagist.org/packages/skn-036/laravel-google-client)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/skn-036/laravel-google-client/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/skn-036/laravel-google-client/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/skn-036/laravel-google-client.svg?style=flat-square)](https://packagist.org/packages/skn-036/laravel-google-client)

<!-- [![Monthly Downloads](https://poser.pugx.org/skn-036/laravel-google-client/d/monthly)](https://packagist.org/packages/skn-036/laravel-google-client) -->

This package only handles the authentication layer, securely saving, retrieving and revoking tokens around [google api client](https://github.com/googleapis/google-api-php-client). So it could be a good choice as authentication layer while using any api service from google.

For some common api services from google please check:

Gmail Api: [skn036/laravel-gmail-api (coming soon)](#)<br>
Calendar Api: [skn036/laravel-google-calendar (coming soon)](#)

For extending your own api service please follow the guidelines [below](#extending-this-package-to-google-api-services).

## Installation

First install the package via composer

```bash
composer require skn036/laravel-google-client
```

Then publish the config file. It will publishes google.php file in the config directory.

```bash
php artisan vendor:publish --provider="Skn036\Google\GoogleClientServiceProvider"
```

## Configuration

Please look into config/google.php for detailed information about the configuration. First you need to enable the required api services and generate credentials in your google developers account. Please [see here](https://console.cloud.google.com/apis/credentials/oauthclient) from detailed instructions. Available .env variables for configuration are:

```env
GOOGLE_APPLICATION_NAME=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
GOOGLE_SCOPES=
GOOGLE_PUB_SUB_TOPIC=
```

#### Authentication Modes:

There are three authentication modes:<br>

-   Single account in whole application ( set `credentials_per_user` to `false` in config file ).
-   Single account per user ( set `credentials_per_user` to `true` & `multiple_accounts_per_user` to `false` in config file ).
-   Multiple accounts per user ( set `credentials_per_user` to `true` & `multiple_accounts_per_user` to `true` in config file ).

**Note:** You need to set the scopes on .env file which google services you intended to use.

## Getting Started

```php
use Illuminate\Http\Request;
use Skn036\Google\GoogleClient;

// pass the google client to the view
Route::get('/google-dashboard', function (Request $request) {
    $googleClient = new GoogleClient();
    return view('google-dashboard', compact('googleClient'));
})->name('google-dashboard');

// redirect go google oauth2 login page
Route::post('/google-login', function (Request $request) {
    return (new GoogleClient())->redirectToAuthUrl();
})->name('google-login');

// logout from google account
Route::post('/google-logout', function (Request $request) {
    (new GoogleClient())->logout();
    return redirect()->route('google-dashboard');
})->name('google-logout');

// authenticate the account from google oauth2 login success
// this route must match the redirect uri in google console and .env file
Route::get('/google-auth-callback', function (Request $request) {
    (new GoogleClient())->authenticate();
    return redirect()->route('google-dashboard');
});
```

**google-dashboard.blade.php**:

```html
<!-- login or logout  -->
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 flex flex-row items-center gap-4">
    @if ($googleClient->isAuthenticated())
    <form method="POST" action="{{ route('google-logout') }}">
        @csrf
        <x-primary-button> {{ __("Logout") }} </x-primary-button>
    </form>
    @else
    <form method="POST" action="{{ route('google-login') }}">
        @csrf
        <x-primary-button> {{ __("Login with Google") }} </x-primary-button>
    </form>
    @endif
</div>

<!-- status -->
<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
    {{ $googleClient->isAuthenticated() ? "Authenticated user:" . $googleClient->email : 'Not
    authenticated' }}
</div>
```

If you are using SPA frontend like `React` or `Vue` and entirely handle the Oauth redirect in the frontend, you should call the getAuthUrl method to get the google Oauth url.

```php
$url = $googleClient->getAuthUrl();
return response()->json($url);
```

After successful login from google, it should add `code` route query param on redirect uri set on the google console. You need to pass this code the `authenticate` method or if you pass the code as route query, it will automatically capture on authentication request;

```php
$token = $googleClient->authenticate($request->code);
```

### Single Account Per User Mode:

By default, authenticated user is passed to `GoogleClient` instance using laravel's `auth()->id()`. If user is not authenticated it will throw a `\Exception` in that case. If you want to pass a user manually to client instance:

```php
$googleClient = new GoogleClient(1); // pass the user's id
```

### Multiple Account Per User Mode:

When using multiple accounts, first account synced will be set as default account. If you want to change the default account, first get the available accounts:

```php
$accounts = $googleClient->getSyncedAccounts();
```

It will give a array of synced accounts like:

```php
[
    ['email' => 'john@gmail.com', 'profile' => ['givenName' => 'John', 'familyName' => 'Doe', ...] ],
    ['email' => 'foo@gmail.com', 'profile' => ['givenName' => 'Foo', 'familyName' => 'Bar', ...] ],
];
```

Then you call `setDefaultAccount` method to change the default account.

```php
$googleClient = $googleClient->setDefaultAccount('foo@gmail.com');
```

Normally in this mode, it will use the default account to interact with the services. If you want to use other account rather than the default, you should pass the email of the intended account as the second argument of the client constructor.

```php
$googleClient = new GoogleClient(1, 'foo@gmail.com');
```

## Extending This Package To Google Api Services

First you should make a api service class extending the GoogleClient class. Then bind it to the api services to interact with the api.

For example we will extend to the `Gmail api` service:

```php
use Skn036\Google\GoogleClient;
use YourNamespace\Gmail\GmailMessage;

class Gmail extends GoogleClient
{
    public function __construct($userId = null, $usingAccount = null, $config = null)
    {
        parent::__construct($userId, $usingAccount, $config);
    }

    // access the messages on gmail api
    public function messages()
    {
        return new GmailMessage($this);
    }
}
```

On the `GmailMessage.php`:

```php
namespace YourNamespace\Gmail;

class GmailMessage
{
    protected $service;
    protected $client;
    protected $params = [];

    public function __construct($googleClient)
    {
        $this->client = $googleClient;
        $this->service = new \Google_Service_Gmail($googleClient); // initiate the gmail api service
    }

    public function get($params = [])
    {
        $params = array_merge($this->params, $params);

        return $this->service->users_messages->listUsersMessages('me', $params);
    }
}
```

Now you can get the gmail messages by

```php
$mails = (new Gmail())->messages()->get();
```

## Tree Shaking Unused Google Services

There are more than 200 api services shipped together on google api. For tree shaking the unnecessary services, please follow instructions on [google api client](https://github.com/googleapis/google-api-php-client).

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

<!-- ## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Muhammad Sajedul Karim](https://github.com/skn-036)
-   [All Contributors](../../contributors) -->

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
