<?php

namespace Skn036\Google\Tests;

use Mockery;
use Skn036\Google\GoogleClient;
use Orchestra\Testbench\TestCase;
use Illuminate\Http\RedirectResponse;
use Skn036\Google\constants\StaticMessages;

class GoogleClientTest extends TestCase
{
    const USER_ID = 123;
    const CONFIG = [
        'client_id' => 'test-client-id',
        'client_secret' => 'test-client-secret',
        'redirect_uri' => 'http://localhost/callback',
        'scopes' => ['https://www.googleapis.com/auth/drive'],
        'application_name' => 'Test Application',
        'credentials_per_user' => true,
        'multiple_accounts_per_user' => false,
        'should_encrypt_credentials' => true,
    ];

    const EMPTY_STORED_CREDENTIALS = [
        'accounts' => [],
        'default_account' => null,
    ];

    const STORED_CREDENTIALS = [
        'accounts' => [
            'a@a.com' => [
                'access_token' => 'test_access_token',
                'email' => 'a@a.com',
                'profile' => [
                    'email' => 'a@a.com',
                    'familyName' => 'John',
                    'givenName' => 'Doe',
                    'verifiedEmail' => true,
                ],
            ],
            'b@b.com' => [
                'access_token' => 'test_access_token_2',
                'email' => 'b@b.com',
                'profile' => [
                    'email' => 'b@b.com',
                    'familyName' => 'Foo',
                    'givenName' => 'Bar',
                    'verifiedEmail' => true,
                ],
            ],
        ],
        'default_account' => 'a@a.com',
    ];

    protected $mock;
    protected $reflection;

    // protected $config =

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock = Mockery::mock(GoogleClient::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $this->reflection = new \ReflectionClass($this->mock);
    }

    protected function getPackageProviders($app)
    {
        return [
                // Include any necessary service providers
            ];
    }

    protected function getEnvironmentSetUp($app)
    {
        config()->set('google', static::CONFIG);
    }

    protected function addStoredCredentialsToMock($credentials = [])
    {
        $property = $this->reflection->getProperty('storedCredentials');
        $property->setAccessible(true);
        $property->setValue($this->mock, $credentials);
    }

    /** @test */
    public function it_initializes_google_client_correctly()
    {
        $client = new GoogleClient(1, null, config()->get('google'));

        $this->assertInstanceOf(GoogleClient::class, $this->mock);
        $this->assertEquals(static::CONFIG['client_id'], $client->getClientId());
    }

    /** @test */
    public function it_always_sets_null_user_id_when_not_credentials_per_user()
    {
        config()->set('google.credentials_per_user', false);

        // client id is provided
        $client = new GoogleClient(1, null, config()->get('google'));
        $this->assertNull($client->userId);
    }

    /** @test */
    public function it_always_sets_user_id_when_credentials_per_user()
    {
        config()->set('google.credentials_per_user', true);

        // client id is not provided and auth()->id() is null
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(StaticMessages::USER_NOT_AUTHENTICATED);
        new GoogleClient(null, null, config()->get('google'));

        $client = new GoogleClient(123, null, config()->get('google'));
        $this->assertEquals(123, $client->userId);
    }

    /** @test */
    public function it_should_throw_exception_if_credentials_per_user_but_user_id_is_not_set()
    {
        config()->set('google.credentials_per_user', true);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(StaticMessages::USER_NOT_AUTHENTICATED);

        new GoogleClient(null, null, config()->get('google'));
    }

    /** @test */
    public function it_sets_token_and_calls_set_access_token()
    {
        $token = [
            'access_token' => 'test-access-token',
            'expires_in' => 3600,
            'refresh_token' => 'test-refresh-token',
        ];

        $this->mock->shouldReceive('setAccessToken')->once()->with($token);

        $method = $this->reflection->getMethod('setToken');
        $method->setAccessible(true);
        $method->invoke($this->mock, $token);

        $clientToken = $this->reflection->getProperty('token');
        $clientToken->setAccessible(true);
        $this->assertEquals($token, $clientToken->getValue($this->mock));
    }

    /** @test */
    public function it_sets_email_properly()
    {
        $email = 'info@example.com';
        $method = $this->reflection->getMethod('setEmail');
        $method->setAccessible(true);
        $method->invoke($this->mock, $email);

        $this->assertEquals($email, $this->mock->email);
    }

    /** @test */
    public function it_checks_user_is_not_authenticated_if_access_token_is_not_set()
    {
        $this->assertFalse($this->mock->isAuthenticated());
    }

    /** @test */
    public function it_checks_user_is_not_authenticated_if_access_token_expired()
    {
        $property = $this->reflection->getProperty('token');
        $property->setAccessible(true);
        $property->setValue($this->mock, ['access_token' => 'test-access-token']);

        $this->mock->shouldReceive('isAccessTokenExpired')->once()->andReturn(true);
        $this->assertFalse($this->mock->isAuthenticated());
    }

    /** @test */
    public function it_checks_user_is_authenticated_if_access_token_is_set_and_not_expired()
    {
        $property = $this->reflection->getProperty('token');
        $property->setAccessible(true);
        $property->setValue($this->mock, ['access_token' => 'test-access-token']);

        $this->mock->shouldReceive('isAccessTokenExpired')->once()->andReturn(false);
        $this->assertTrue($this->mock->isAuthenticated());
    }

    /** @test */
    public function it_will_not_refresh_access_token_if_token_is_not_set()
    {
        $this->mock->shouldNotReceive('setToken');
        $this->mock->refreshTokenIfNecessary();
    }

    /** @test */
    public function it_will_not_refresh_access_token_if_token_is_set_but_not_expired()
    {
        $property = $this->reflection->getProperty('token');
        $property->setAccessible(true);
        $property->setValue($this->mock, ['access_token' => 'test-access-token']);

        $this->mock->shouldReceive('isAccessTokenExpired')->once()->andReturn(false);
        $this->mock->shouldNotReceive('setToken');

        $this->mock->refreshTokenIfNecessary();
    }

    /** @test */
    public function it_will_refresh_access_token_if_token_is_set_and_expired()
    {
        $token = ['access_token' => 'test-access-token'];
        $property = $this->reflection->getProperty('token');
        $property->setAccessible(true);
        $property->setValue($this->mock, $token);

        $this->mock->shouldReceive('isAccessTokenExpired')->once()->andReturn(true);

        $this->mock->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->andReturn($token);
        $this->mock->shouldReceive('setToken')->once()->with($token);

        $this->mock->refreshTokenIfNecessary();
    }

    /** @test */
    public function it_should_generate_oauth_url()
    {
        $this->mock->shouldReceive('getAuthUrl')->once()->andReturn('https://example.com/auth');
        $this->assertEquals('https://example.com/auth', $this->mock->getAuthUrl());
    }

    /** @test */
    public function it_redirects_to_google_auth_url()
    {
        $this->mock->shouldReceive('getAuthUrl')->once()->andReturn('https://example.com/auth');

        $response = $this->mock->redirectToAuthUrl();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertEquals('https://example.com/auth', $response->getTargetUrl());
    }

    /** @test */
    public function it_should_not_authenticate_auth_code_is_not_passed_as_func_arg()
    {
        // Request::shouldReceive('capture')->once()->andReturnSelf();
        // Request::shouldReceive('input')->once()->with('code')->andReturn(null);
        // Request::shouldIgnoreMissing();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage(StaticMessages::OAUTH_CODE_NOT_FOUND);

        $this->mock->shouldReceive('fetchAccessTokenWithAuthCode')->never();

        $this->mock->authenticate();
    }

    /** @test */
    public function it_should_logout()
    {
        $this->addStoredCredentialsToMock(static::STORED_CREDENTIALS);

        $this->mock->shouldReceive('revokeToken')->once();

        $this->mock->logout();
    }
}
