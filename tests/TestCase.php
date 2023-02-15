<?php

namespace Recca0120\LaravelParallel\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Recca0120\LaravelParallel\ParallelServiceProvider;
use Recca0120\LaravelParallel\Tests\Fixtures\setupDatabase;

class TestCase extends BaseTestCase
{
    use setupDatabase;

    protected function getEnvironmentSetUp($app)
    {
        $this->databaseSetUp($app);
    }

    /**
     * Get package providers.
     *
     * @param  Application  $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [ParallelServiceProvider::class];
    }

    /**
     * Define routes setup.
     *
     * @param  Router  $router
     * @return void
     */
    protected function defineRoutes($router): void
    {
        $router->match(['post', 'put', 'patch', 'options', 'delete'], '/auth/login', function (Request $request) {
            return Auth::attempt($request->only('email', 'password')) ? Auth::user() : [];
        });
    }
}
