<?php

namespace Recca0120\ParallelTest\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Recca0120\ParallelTest\ParallelTestServiceProvider;

class TestCase extends BaseTestCase
{
    /**
     * Get package providers.
     *
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [ParallelTestServiceProvider::class];
    }

    /**
     * Define database migrations.
     *
     * @return void
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadLaravelMigrations();
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
            $user = ['email' => 'recca0120@gmail.com', 'password' => 'password'];

            return $user['email'] === $request->get('email') && $user['password'] === $request->get('password') ? $user : [];
        });
    }
}
