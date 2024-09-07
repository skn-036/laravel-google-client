<?php

namespace Skn036\Google\Traits;

use Skn036\Google\constants\StaticMessages;

trait Configurable
{
    /**
     * Path to the credentials folder.
     *
     * @var string
     */
    protected $credentialsStoragePath = 'google/credentials';

    /**
     * Prefix for the credentials file.
     *
     * @var string
     */
    protected $credentialsFilePrefix = 'credentials';

    /**
     * Default google scopes
     *
     * @var array<string>
     */
    protected $defaultScopes = [
        'https://www.googleapis.com/auth/userinfo.profile',
        'https://www.googleapis.com/auth/userinfo.email',
    ];

    /**
     * Set the configuration of the from env file.
     *
     * @param  array<string, mixed>|null  $config
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    private function prepareEnvConfig($config = null)
    {
        if (!$config) {
            if (empty(config()->get('google'))) {
                throw new \Exception(StaticMessages::GOOGLE_CONFIG_NOT_SET);
            }
            $config = config()->get('google');
        }
        $config['scopes'] = array_merge($this->defaultScopes, $config['scopes'] ?? []);

        foreach (['client_id', 'client_secret', 'redirect_uri', 'scopes'] as $key) {
            if (!$this->isConfigValueAvailable($config, $key)) {
                throw new \Exception("Google $key is not set on the env file.");
            }
        }

        return $config;
    }

    /**
     * Set the configuration of the from env file.
     *
     * @param  array<string, mixed>  $envConfig
     * @return array<string, mixed>
     */
    protected function prepareGoogleConfig($envConfig)
    {
        $config = [
            'client_id' => $envConfig['client_id'],
            'client_secret' => $envConfig['client_secret'],
            'redirect_uri' => $envConfig['redirect_uri'],
            'scopes' => $envConfig['scopes'],
            'access_type' => 'offline',
        ];

        if ($envConfig['application_name']) {
            $config['application_name'] = $envConfig['application_name'];
        }

        return $config;
    }

    /**
     * Check if a configuration key exists.
     *
     * @param  array<string, mixed>  $config
     * @param  string|callable  $key
     * @return bool
     */
    private function isConfigValueAvailable($config, $key)
    {
        if (is_callable($key)) {
            return $key($config);
        }

        return !empty($config[$key]);
    }

    /**
     * Set the credentials file path.
     *
     * @param  array<string, mixed>  $envConfig
     * @param  string|int|null  $userId
     * @return string
     */
    private function setCredentialsFilePath($envConfig, $userId)
    {
        if (empty($envConfig['credentials_per_user']) || !$userId) {
            return $this->credentialsStoragePath . '/' . $this->credentialsFilePrefix . '.json';
        }

        return $this->credentialsStoragePath .
            '/' .
            $this->credentialsFilePrefix .
            '-' .
            $userId .
            '.json';
    }
}
