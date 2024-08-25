<?php
namespace Skn036\Google;

use Skn036\Google\Traits\Configurable;
use Skn036\Google\Traits\TokenStorage;
use Illuminate\Support\Facades\Request;

class GoogleClient extends \Google_Client
{
    use Configurable, TokenStorage;

    /**
     * id of the user. (Generally refers to the \App\Models\User model)
     *
     * @var string|int|null
     */
    protected $userId;

    /**
     * configuration of the from env file.
     *
     * @var array<string, mixed>
     */
    protected $envConfig;

    /**
     * configuration of the from env file.
     *
     * @var array<string, mixed>
     */
    protected $googleConfig;

    /**
     * The path to the credentials file.
     *
     * @var string
     */
    protected $credentialsFilePath;

    /**
     * The email of the google user.
     *
     * @var string|null
     */
    public $email = null;

    /**
     * Google auth token
     *
     * @var array<string, mixed>|null
     */
    protected $token;

    /**
     * Create a new GoogleClient instance.
     *
     * @param string|int|null $userId
     *
     * @return void
     *
     */
    public function __construct($userId = null)
    {
        $this->userId = $this->setUserId($userId);

        $this->envConfig = $this->prepareEnvConfig();
        $this->googleConfig = $this->prepareGoogleConfig($this->envConfig);

        $this->credentialsFilePath = $this->setCredentialsFilePath($this->envConfig, $this->userId);

        parent::__construct($this->googleConfig);

        $this->setAuthTokenAndEmailFromStorage();
        $this->refreshTokenIfNecessary();
    }

    /**
     * Set the user id. if not given
     *
     * @param string|int|null $userId
     *
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
     * set access token to the google client.
     *
     * @param array<string, mixed>|null $token
     *
     * @return void
     */
    protected function setToken($token)
    {
        $this->token = $token;
        $this->setAccessToken($token);
    }

    /**
     * set google user email
     *
     * @param string|null $email
     *
     * @return void
     */
    protected function setEmail($email)
    {
        $this->email = $email;
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
        }
    }

    /**
     * write the token to the storage from $token and $email instance.
     *
     * @return void
     */
    private function writeTokenToStorage()
    {
        $data = array_merge($this->token, ['email' => $this->email]);
        $this->saveJsonDataToFile($this->credentialsFilePath, $data);
    }

    /**
     * set token and email from the storage.
     *
     * @return void
     */
    private function setAuthTokenAndEmailFromStorage()
    {
        $token = $this->parseJsonDataFromFile($this->credentialsFilePath);
        if ($token) {
            if (array_key_exists('email', $token)) {
                $this->setEmail($token['email']);
                unset($token['email']);
            }
            $this->setToken($token);
        }
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
     * @param string|null $code
     *
     * @return void
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
            throw new \Exception('Google auth redirect code is not present in the request.');
        }

        $token = $this->fetchAccessTokenWithAuthCode($code);
        $this->setToken($token);

        $profile = $this->getUserProfile();
        if ($profile && property_exists($profile, 'emailAddress')) {
            $this->setEmail($profile->emailAddress);
        }

        $this->writeTokenToStorage();
    }

    /**
     * get user profile from google
     *
     * @return \Google_Service_Gmail_Profile|null
     */
    private function getUserProfile()
    {
        try {
            $service = new \Google_Service_Gmail($this);
            return $service->users->getProfile('me');
        } catch (\Exception $error) {
            return null;
        }
    }

    /**
     * revoke the token from google and logout from system
     *
     * @return void
     */
    public function logout()
    {
        $this->revokeToken();
        $this->deleteFile($this->credentialsFilePath);
    }
}
