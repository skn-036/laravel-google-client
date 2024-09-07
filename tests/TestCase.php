<?php

namespace Skn036\Google\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Skn036\Google\GoogleClientServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Factory::guessFactoryNamesUsing(
        //     fn (string $modelName) => 'Skn036\\Google\\Database\\Factories\\'.class_basename($modelName).'Factory'
        // );
    }

    protected function getPackageProviders($app)
    {
        return [GoogleClientServiceProvider::class];
    }
}
