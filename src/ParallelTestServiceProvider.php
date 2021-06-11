<?php

namespace Recca0120\ParallelTest;

use Illuminate\Support\ServiceProvider;
use Recca0120\ParallelTest\Console\AsyncCallCommand;

class ParallelTestServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([AsyncCallCommand::class]);

        $this->app->bind(AsyncRequest::class, function () {
            return new AsyncRequest($this->app['request']->server->all());
        });
    }
}
