<?php

namespace Recca0120\AsyncTesting\Tests\Fixtures;

use Illuminate\Support\Facades\Hash;

trait PrepareDatabase
{
    protected function databaseSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');

        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('auth.providers.users.model', User::class);

        $schema = $app['db']->getSchemaBuilder();

        $schema->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('api_token');
            $table->timestamps();
        });

        $app->booted(function () {
            User::create([
                'email' => 'recca0120@gmail.com',
                'password' => Hash::make('password'),
                'api_token' => '6Uv0zov7V2dAk5wWE45HHHhz05gpsmw2',
            ]);
        });
    }
}
