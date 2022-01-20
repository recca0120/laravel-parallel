<?php

namespace Recca0120\LaravelParallel;

use Illuminate\Support\ServiceProvider;
use Recca0120\LaravelParallel\Console\ParallelCommand;

class ParallelServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->commands([ParallelCommand::class]);
    }
}
