<?php

namespace Skn036\Google\Traits;

trait Configurable
{
    /**
     * Path to the credentials folder.
     *
     * @var string
     */
    protected $credentialsPathPrefix = storage_path('app/google/credentials');

    /**
     * Prefix for the credentials file.
     *
     * @var string
     */
    protected $credentialsFilePrefix = 'credentials';

    /**
     * Set the configuration of the from env file.
     *
     * @return array<string, mixed>
     *
     * @throws \Exception
     */
    private function prepareEnvConfig()
    {
        if (empty(app('config')['google'])) {
            throw new \Exception(
                'Google config not set. Run `php artisan vendor:publish --provider="Skn036\Google\GoogleClientServiceProvider"` to publish the config file.'
            );
        }
        $config = app('config')['google'];

        foreach (['project_id', 'client_id', 'client_secret', 'redirect_uri', 'scopes'] as $key) {
            if (! $this->isConfigValueAvailable($config, $key)) {
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

        return ! empty($config[$key]);
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
        if (empty($envConfig['credentials_per_user']) || ! $userId) {
            return $this->credentialsPathPrefix.'/'.$this->credentialsFilePrefix.'.json';
        }

        return $this->credentialsPathPrefix.
            '/'.
            $this->credentialsFilePrefix.
            '-'.
            $userId.
            '.json';
    }
}
