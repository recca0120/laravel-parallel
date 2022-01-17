<?php

namespace Recca0120\LaravelParallel;

use Illuminate\Support\ServiceProvider;
use Recca0120\LaravelParallel\Console\ParallelCommand;
use Recca0120\LaravelParallel\Tests\ParallelRequest;

class ParallelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([ParallelCommand::class]);

        $this->app->bind(ParallelRequest::class, function () {
            return ParallelRequest::create($this->app['request']->server->all());
        });
        $this->app->bind(ParallelRequest::class, function () {
            return ParallelRequest::create($this->app['request']->server->all());
        });
    }
}
