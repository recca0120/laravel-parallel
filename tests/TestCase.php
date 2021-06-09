<?php

namespace Recca0120\ParallelTest\Tests;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Auth;
use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
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
            return Auth::attempt($request->only('email', 'password'))
                ? Auth::user()->setHidden(['email_verified_at', 'remember_token'])
                : [];
        });
    }
}
