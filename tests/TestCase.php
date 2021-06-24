<?php

namespace Recca0120\AsyncTesting\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Recca0120\AsyncTesting\AsyncTestingServiceProvider;
use Recca0120\AsyncTesting\Tests\Fixtures\PrepareDatabase;

class TestCase extends BaseTestCase
{
    use PrepareDatabase;

    protected function getEnvironmentSetUp($app)
    {
        $this->databaseSetUp($app);
    }

    /**
     * Get package providers.
     *
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [AsyncTestingServiceProvider::class];
    }

    /**
     * Define routes setup.
     *
     * @param Router $router
     *
     * @return void
     */
    protected function defineRoutes($router): void
    {
        $router->match(['post', 'put', 'patch', 'options', 'delete'], '/auth/login', function (Request $request) {
            return Auth::attempt($request->only('email', 'password')) ? Auth::user() : [];
        });
    }
}
