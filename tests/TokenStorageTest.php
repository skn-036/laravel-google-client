<?php

namespace Skn036\Google\Tests;

use Illuminate\Support\Facades\Storage;
use Orchestra\Testbench\TestCase;
use Skn036\Google\Traits\TokenStorage;

class TokenStorageTest extends TestCase
{
    use TokenStorage;

    protected $filePath = 'path/to/file.json';

    /** @test */
    public function it_checks_if_credentials_file_exists()
    {
        Storage::shouldReceive('disk')->once()->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')
            ->once()
            ->with($this->filePath)
            ->andReturnTrue();

        $this->assertTrue($this->isCredentialsFileExists($this->filePath));
    }

    /** @test */
    public function it_returns_empty_if_file_does_not_exist_when_parsing_json()
    {
        Storage::shouldReceive('disk')->once()->with('local')->andReturnSelf();
        Storage::shouldReceive('exists')
            ->once()
            ->with($this->filePath)
            ->andReturnFalse();

        $this->assertEquals($this->parseJsonDataFromFile($this->filePath), [
            'accounts' => [],
            'default_account' => null,
        ]);
    }

    /** @test */
    public function it_parses_json_data_from_file_successfully()
    {
        $data = ['key' => 'value'];
        Storage::shouldReceive('disk')->twice()->with('local')->andReturnSelf();

        Storage::shouldReceive('exists')
            ->once()
            ->with($this->filePath)
            ->andReturnTrue();

        Storage::shouldReceive('get')
            ->once()
            ->with($this->filePath)
            ->andReturn(encrypt(json_encode($data)));

        $this->assertEquals($data, $this->parseJsonDataFromFile($this->filePath));
    }

    /** @test */
    public function it_saves_json_data_to_file_successfully()
    {
        $data = ['key' => 'value'];

        Storage::shouldReceive('disk')->once()->with('local')->andReturnSelf();
        Storage::shouldReceive('put')
            ->once()
            ->withArgs(function ($filePath, $content) use ($data) {
                return $filePath === $this->filePath && decrypt($content) === json_encode($data);
            })
            ->andReturnTrue();

        $this->assertTrue($this->saveJsonDataToFile($this->filePath, $data));
    }

    /** @test */
    public function it_returns_true_if_file_does_not_exist_when_deleting()
    {
        Storage::shouldReceive('disk')->once()->with('local')->andReturnSelf();

        Storage::shouldReceive('exists')
            ->once()
            ->with($this->filePath)
            ->andReturnFalse();

        Storage::shouldNotReceive('delete');

        $this->assertTrue($this->deleteFile($this->filePath));
    }

    /** @test */
    public function it_deletes_file_successfully_if_it_exists()
    {
        Storage::shouldReceive('disk')->twice()->with('local')->andReturnSelf();

        Storage::shouldReceive('exists')
            ->once()
            ->with($this->filePath)
            ->andReturnTrue();

        Storage::shouldReceive('delete')
            ->once()
            ->with($this->filePath)
            ->andReturn(true);

        $this->assertTrue($this->deleteFile($this->filePath));
    }
}
