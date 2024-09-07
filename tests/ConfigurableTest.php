<?php

namespace Skn036\Google\Tests;

use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase;
use Skn036\Google\constants\StaticMessages;
use Skn036\Google\Traits\Configurable;

class ConfigurableTest extends TestCase
{
    use WithFaker;
    use Configurable; // Utilize the trait directly for testing.

    protected function getEnvironmentSetUp($app)
    {
        // Configure environment variables or config settings if needed.
        $app['config']->set('google', [
            'project_id' => 'test-project-id',
            'client_id' => 'test-client-id',
            'client_secret' => 'test-client-secret',
            'redirect_uri' => 'http://localhost/callback',
            'scopes' => ['https://www.googleapis.com/auth/drive'],
            'application_name' => 'Test Application',
            'credentials_per_user' => true,
        ]);
    }

    /** @test */
    public function it_throws_exception_if_google_config_is_not_set()
    {
        // Clear the google config to simulate missing config scenario
        config()->set('google', []);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(StaticMessages::GOOGLE_CONFIG_NOT_SET);

        $this->prepareEnvConfig();
    }

    /** @test */
    public function it_throws_exception_if_required_keys_are_missing_in_env_config()
    {
        // Remove a required key to simulate missing config key
        config()->set('google.client_id', null);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Google client_id is not set on the env file.');

        $this->prepareEnvConfig();
    }

    /** @test */
    public function it_prepares_env_config_successfully()
    {
        $config = $this->prepareEnvConfig();

        $this->assertEquals('test-client-id', $config['client_id']);
        $this->assertEquals('test-client-secret', $config['client_secret']);
        $this->assertEquals('http://localhost/callback', $config['redirect_uri']);
        $this->assertEquals(
            array_merge($this->defaultScopes, ['https://www.googleapis.com/auth/drive']),
            $config['scopes']
        );
    }

    /** @test */
    public function it_prepares_google_config_with_application_name()
    {
        $envConfig = $this->prepareEnvConfig();
        $config = $this->prepareGoogleConfig($envConfig);

        $this->assertEquals('test-client-id', $config['client_id']);
        $this->assertEquals('test-client-secret', $config['client_secret']);
        $this->assertEquals('http://localhost/callback', $config['redirect_uri']);
        $this->assertEquals(
            array_merge($this->defaultScopes, ['https://www.googleapis.com/auth/drive']),
            $config['scopes']
        );
        $this->assertEquals('Test Application', $config['application_name']);
    }

    /** @test */
    public function it_prepares_google_config_without_application_name()
    {
        config()->set('google.application_name', null);

        $envConfig = $this->prepareEnvConfig();
        $config = $this->prepareGoogleConfig($envConfig);

        $this->assertArrayNotHasKey('application_name', $config);
    }

    /** @test */
    public function it_returns_correct_credentials_file_path_without_user_id()
    {
        $envConfig = $this->prepareEnvConfig();
        $path = $this->setCredentialsFilePath($envConfig, null);

        $this->assertEquals('google/credentials/credentials.json', $path);
    }

    /** @test */
    public function it_returns_correct_credentials_file_path_with_user_id()
    {
        $envConfig = $this->prepareEnvConfig();
        $path = $this->setCredentialsFilePath($envConfig, 123);

        $this->assertEquals('google/credentials/credentials-123.json', $path);
    }

    /** @test */
    public function it_returns_true_if_config_value_is_available()
    {
        $config = [
            'client_id' => 'test-client-id',
        ];

        $this->assertTrue($this->isConfigValueAvailable($config, 'client_id'));
    }

    /** @test */
    public function it_returns_false_if_config_value_is_not_available()
    {
        $config = [
            'client_id' => '',
        ];

        $this->assertFalse($this->isConfigValueAvailable($config, 'client_id'));
    }
}
