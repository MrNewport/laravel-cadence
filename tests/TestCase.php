<?php
namespace MrNewport\LaravelCadence\Tests;
class TestCase extends \Orchestra\Testbench\TestCase
{
    protected function getPackageProviders($app): array
    {
        return [\MrNewport\LaravelCadence\CadenceServiceProvider::class];
    }
}
