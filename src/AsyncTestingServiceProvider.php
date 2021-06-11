<?php

namespace Recca0120\AsyncTesting;

use Illuminate\Support\ServiceProvider;
use Recca0120\AsyncTesting\Console\AsyncCallCommand;

class AsyncTestingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([AsyncCallCommand::class]);

        $this->app->bind(AsyncRequest::class, function () {
            return AsyncRequest::create($this->app['request']->server->all());
        });
    }
}
