<?php

namespace Recca0120\AsyncTesting;

use Illuminate\Support\ServiceProvider;
use Recca0120\AsyncTesting\Console\AsyncRequestCommand;

class AsyncTestingServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([AsyncRequestCommand::class]);

        $this->app->bind(AsyncRequest::class, function () {
            return AsyncRequest::create($this->app['request']->server->all());
        });
    }
}
