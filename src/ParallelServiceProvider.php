<?php

namespace Recca0120\LaravelParallel;

use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;
use Recca0120\LaravelParallel\Console\ParallelCommand;

class ParallelServiceProvider extends ServiceProvider
{
    public function register()
    {
        config([
            'database.connections.laravel-parallel' => [
                'driver' => 'sqlite',
                'database' => \env('DB_PARALLEL_DATABASE', database_path('laravel-parallel.sqlite')),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        $this->commands([ParallelCommand::class]);

        $this->app->bind(ParallelRequest::class, function () {
            /** @var Request $request */
            $request = app('request');

            return (new ParallelRequest($request))->withServerVariables($request->server->all());
        });
    }
}
