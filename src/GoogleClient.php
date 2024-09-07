<?php

namespace Skn036\Google;

use Google\Service\Oauth2;
use Skn036\Google\Traits\Configurable;
use Skn036\Google\Traits\TokenStorage;
use Illuminate\Support\Facades\Request;
use Skn036\Google\constants\StaticMessages;

class GoogleClient extends \Google_Client
{
    use Configurable, TokenStorage;

    /**
     * id of the user. (Generally refers to the \App\Models\User id)
     *
     * @var string|int|null
     */
    public $userId;

    /**
     * configuration of the from env file.
     *
     * @var array<string, mixed>
     */
    protected $envConfig;

    /**
     * Google client configuration.
     *
     * @var array<string, mixed>
     */
    protected $googleConfig;

    /**
     * The path to the saved credentials file.
     *
     * @var string
     */
    protected $credentialsFilePath = '';

    /**
     * The mode of the credentials and auth token.
     * mode: 'single-user', single credentials file throughout the app.
     * mode: 'multiple-user', separate credentials file for each user.
     * mode: 'multiple-user-multiple-accounts', separate credentials file for each user and multiple accounts per user.
     *
     * @var string
     */
    protected $credentialsMode;

    /**
     * The parsed json data from the credentials file.
     *
     * @var array
     */
    protected $storedCredentials;

    /**
     * The active credentials set by $usingAccount or default account stored in the credentials file.
     *
     * @var array
     */
    protected $activeCredential;

    /**
     * should encrypt the credentials file.
     *
     * @var bool
     */
    protected $encrypt;

    /**
     * The email to use account when multiple accounts per user.
     * If not set, it will use default account from the stored credentials.
     *
     * @var string|null
     */
    public $usingAccount = null;

    /**
     * The email of the google user.
     *
     * @var string|null
     */
    public $email = null;

    /**
     * The profile of the google user.
     *
     * @var array
     */
    public $profile = [];

    /**
     * Google auth token
     *
     * @var array<string, mixed>|null
     */
    protected $token;

    /**
     * Create a new GoogleClient instance.
     *
     * @param string|int|null  $userId id of the user. (Generally refers to the \App\Models\User model)
     * @param string|null $usingAccount email of the account to be used, when multiple accounts per user.
     * @param array<string, mixed>|null $config pass this parameter equivalent to config('google') to override the env file configuration.
     * @return void
     *
     * @throws \Exception
     */
    public function __construct($userId = null, $usingAccount = null, $config = null)
    {
        $this->envConfig = $this->prepareEnvConfig($config);
        $this->userId = $this->setUserId($userId);
        $this->usingAccount = $usingAccount;

        if ($this->envConfig['credentials_per_user'] && !$this->userId) {
            throw new \Exception(StaticMessages::USER_NOT_AUTHENTICATED);
        }

        $this->googleConfig = $this->prepareGoogleConfig($this->envConfig);
        $this->credentialsFilePath = $this->setCredentialsFilePath($this->envConfig, $this->userId);

        $this->encrypt = $this->setEncrypt();
        $this->credentialsMode = $this->setCredentialsMode();

        parent::__construct($this->googleConfig);

        $this->storedTokenToCredentials();

        $this->refreshTokenIfNecessary();
    }

    /**
     * Set the user id. if not given, it will set the authenticated user id.
     *
     * @param  string|int|null  $userId
     * @return string|int|null
     */
    private function setUserId($userId)
    {
        if (empty($this->envConfig['credentials_per_user'])) {
            return null;
        }

        return $userId ?: auth()->id();
    }

    /**
     * set the encrypted credentials.
     * @return bool
     */
    private function setEncrypt()
    {
        return $this->envConfig && isset($this->envConfig['should_encrypt_credentials'])
            ? $this->envConfig['should_encrypt_credentials']
            : true;
    }

    /**
     * Sets the credentials mode.
     *
     * @return string
     */
    private function setCredentialsMode()
    {
        $credentialsPerUser = $this->envConfig['credentials_per_user'];
        if (!$credentialsPerUser) {
            return 'single-user';
        }
        if ($this->envConfig['multiple_accounts_per_user']) {
            return 'multiple-user-multiple-accounts';
        }
        return 'multiple-user';
    }

    /**
     * Sets the active credentials from the stored credentials.
     * Top priority is given to the $usingAccount, then default account from the stored credentials.
     *
     * @param  array $storedCredentials
     * @return array
     *
     * @throws \Exception
     */
    private function setActiveCredential($storedCredentials)
    {
        $accounts = $storedCredentials['accounts'];
        $defaultAccount = $storedCredentials['default_account'];

        if ($this->credentialsMode === 'multiple-user-multiple-accounts') {
            if ($this->usingAccount) {
                if (!array_key_exists($this->usingAccount, $accounts)) {
                    throw new \Exception(
                        "Account $this->usingAccount not found in the stored credentials."
                    );
                }
                return $accounts[$this->usingAccount];
            }

            if ($defaultAccount && array_key_exists($defaultAccount, $accounts)) {
                return $accounts[$defaultAccount];
            }

            return [];
        } else {
            if (!count($accounts)) {
                return [];
            }
            return $defaultAccount && array_key_exists($defaultAccount, $accounts)
                ? $accounts[$defaultAccount]
                : $accounts[array_key_first($accounts)];
        }
    }

    /**
     * set token, profile and email from the stored credentials.
     *
     * @return void
     */
    private function setAuthTokenEmailProfileFromActiveCredential()
    {
        $token = $this->activeCredential;
        if ($token) {
            if (array_key_exists('email', $token)) {
                $this->setEmail($token['email']);
                unset($token['email']);
            }
            if (array_key_exists('profile', $token)) {
                $this->setProfile($token['profile']);
                unset($token['profile']);
            }
            $this->setToken($token);
        }
    }

    /**
     * it will parse, set active credentials and set token, email, profile from the stored credentials.
     * this should be called whenever credentials file has been changed
     *
     * @return void
     */
    protected function storedTokenToCredentials()
    {
        $this->storedCredentials = $this->parseJsonDataFromFile(
            $this->credentialsFilePath,
            $this->encrypt
        );
        $this->activeCredential = $this->setActiveCredential($this->storedCredentials);

        $this->setAuthTokenEmailProfileFromActiveCredential();
    }

    /**
     * set access token to the google client.
     *
     * @param  array<string, mixed>|null  $token
     * @return void
     */
    protected function setToken($token)
    {
        $this->token = $token;
        if ($token) {
            $this->setAccessToken($token);
        }
    }

    /**
     * set google user email
     *
     * @param  string|null  $email
     * @return void
     */
    protected function setEmail($email)
    {
        $this->email = $email;
    }

    /**
     * set google user profile
     *
     * @param  array|null  $profile
     * @return void
     */
    protected function setProfile($profile)
    {
        $this->profile = $profile;
    }

    /**
     * whether the google account is authenticated
     *
     * @return bool
     */
    public function isAuthenticated()
    {
        return !empty($this->token['access_token']) && !$this->isAccessTokenExpired();
    }

    /**
     * refreshes the token if it is expired.
     *
     * @return void
     */
    public function refreshTokenIfNecessary()
    {
        if (!empty($this->token['access_token']) && $this->isAccessTokenExpired()) {
            $this->setToken($this->fetchAccessTokenWithRefreshToken());
            $this->writeTokenToStorage();
            $this->storedTokenToCredentials();
        }
    }

    /**
     * write the updated accounts information to the storage
     *
     * @return void
     */
    private function writeTokenToStorage()
    {
        $storedCredentials = $this->storedCredentials;
        if (
            empty($storedCredentials['accounts']) ||
            $this->credentialsMode !== 'multiple-user-multiple-accounts'
        ) {
            $storedCredentials['accounts'] = [];
        }
        $data = array_merge($this->token, ['email' => $this->email, 'profile' => $this->profile]);
        $storedCredentials['accounts'][$this->email] = $data;

        $this->saveJsonDataToFile(
            $this->credentialsFilePath ?: '',
            $storedCredentials,
            $this->encrypt
        );
    }

    /**
     * write the default account to storage
     *
     * @return void
     * @throws \Exception
     */
    private function writeDefaultAccountToStorage($email)
    {
        $storedCredentials = $this->storedCredentials;
        if (!array_key_exists($email, $storedCredentials['accounts'])) {
            throw new \Exception("Account $email not found in the stored credentials.");
        }

        $storedCredentials['default_account'] = $email;

        $this->saveJsonDataToFile(
            $this->credentialsFilePath ?: '',
            $storedCredentials,
            $this->encrypt
        );
    }

    /**
     * get the oauth url from google
     *
     * @return string
     */
    public function getAuthUrl()
    {
        return $this->createAuthUrl();
    }

    /**
     * redirect to the google auth url
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToAuthUrl()
    {
        return redirect($this->getAuthUrl());
    }

    /**
     * authenticate the user with the google auth code
     *
     * @param  string|null  $code
     * @return array
     *
     * @throws \Exception
     */
    public function authenticate($code = null)
    {
        if (!$code) {
            $request = Request::capture();
            $code = $request->input('code');
        }

        if (!$code) {
            throw new \Exception(StaticMessages::OAUTH_CODE_NOT_FOUND);
        }

        $token = $this->fetchAccessTokenWithAuthCode($code);
        $this->setToken($token);

        $profile = $this->getUserProfile();

        $this->setProfile(json_decode(json_encode($profile), true));
        if ($profile && $profile->email) {
            $this->setEmail($profile->email);
        }

        $this->writeTokenToStorage();
        $this->storedTokenToCredentials();

        if (
            $this->credentialsMode !== 'multiple-user-multiple-accounts' ||
            !$this->storedCredentials['default_account']
        ) {
            $this->writeDefaultAccountToStorage($this->email);
            $this->storedTokenToCredentials();
        }

        return $token;
    }

    /**
     * get user profile from google
     *
     * @return \Google\Service\Oauth2\Userinfo|null
     */
    private function getUserProfile()
    {
        return (new Oauth2($this))->userinfo->get();
    }

    /**
     * revoke the token from google and logout from system
     *
     * @return void
     */
    public function logout()
    {
        $this->revokeToken();

        $storedCredentials = $this->storedCredentials;
        if (array_key_exists($this->email, $storedCredentials['accounts'])) {
            unset($storedCredentials['accounts'][$this->email]);
        }
        if (
            $this->credentialsMode === 'multiple-user-multiple-accounts' &&
            $this->email === $storedCredentials['default_account']
        ) {
            if (count($storedCredentials['accounts'])) {
                $storedCredentials['default_account'] = array_key_first(
                    $storedCredentials['accounts']
                );
            } else {
                $storedCredentials['default_account'] = null;
            }
        } else {
            $storedCredentials['default_account'] = null;
        }

        $this->saveJsonDataToFile(
            $this->credentialsFilePath ?: '',
            $storedCredentials,
            $this->encrypt
        );

        $this->setToken(null);
        $this->setEmail(null);
        $this->setProfile([]);
    }

    public function getSyncedAccounts()
    {
        $accounts = $this->storedCredentials['accounts'] ?? [];
        return array_map(function ($account) {
            return ['email' => $account['email'], 'profile' => $account['profile']];
        }, $accounts);
    }

    /**
     * set the default account to use when multiple accounts per user.
     *
     * @param  string  $email
     * @return $this
     */
    public function setDefaultAccount($email)
    {
        $this->writeDefaultAccountToStorage($email);
        $this->storedTokenToCredentials();

        $this->refreshTokenIfNecessary();

        return $this;
    }
}
