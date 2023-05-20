<?php

namespace Recca0120\LaravelParallel;

use Illuminate\Support\ServiceProvider;
use Recca0120\LaravelParallel\Console\ParallelCommand;

class ParallelServiceProvider extends ServiceProvider
{
    public function register()
    {
        config([
            'database.connections.laravel-parallel' => [
                'driver' => 'sqlite',
                'database' => database_path('laravel-parallel.sqlite'),
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        $this->commands([ParallelCommand::class]);
    }
}
