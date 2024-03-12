<?php

namespace Recca0120\LaravelParallel\Tests\Concerns;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\Traits\CanConfigureMigrationCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Recca0120\LaravelParallel\Tests\ParallelRequest;

trait WithParallelPhyiscalDatabase
{
    use CanConfigureMigrationCommands;

    private $parallelDatabaseName = 'laravel-parallel';

    private $beforeRefreshParallelPhyiscalDatabaseCallbacks = [];

    public function useParallelPhyiscalDatabase(): void
    {
        $this->setParallelPhyiscalDatabase();
        $this->callBeforeRefreshParallelPhyiscalDatabase();
        $this->refreshParallelPhyiscalDatabase();
        $this->bindParallelRequest();
    }

    protected function setParallelPhyiscalDatabase(): void
    {
        if ($this->usingPhyiscalDatabase()) {
            return;
        }

        $config = collect(Arr::dot(config()->all()))
            ->filter(function ($value, $key) {
                return $value && Str::endsWith($key, 'database.connection');
            })
            ->map(function () {
                return $this->parallelDatabaseName;
            })
            ->merge(['database.default' => $this->parallelDatabaseName])
            ->toArray();

        config($config);

        File::put(config('database.connections.'.$this->parallelDatabaseName.'.database'), '');
    }

    protected function beforeRefreshParallelPhyiscalDatabase($callback): self
    {
        $this->beforeRefreshParallelPhyiscalDatabaseCallbacks[] = $callback;

        return $this;
    }

    protected function refreshParallelPhyiscalDatabase(): void
    {
        $this->artisan('migrate:fresh', $this->migrateFreshUsing());

        $this->app[Kernel::class]->setArtisan(null);

        $uses = array_flip(class_uses_recursive(static::class));
        foreach ([RefreshDatabase::class, DatabaseMigrations::class] as $class) {
            if (array_key_exists($class, $uses)) {
                RefreshDatabaseState::$migrated = false;
                RefreshDatabaseState::$lazilyRefreshed = false;

                return;
            }
        }
    }

    private function bindParallelRequest(): void
    {
        $this->app->bind(ParallelRequest::class, function () {
            /** @var Request $request */
            $request = app('request');
            $default = config('database.default');

            return (new ParallelRequest($request))->withServerVariables(array_merge($request->server->all(), [
                'DB_CONNECTION' => $default,
                'DB_DATABASE' => config('database.connections.'.$default.'.database'),
            ]));
        });
    }

    protected function usingPhyiscalDatabase(): bool
    {
        $default = config('database.default');

        return config("database.connections.$default.database") !== ':memory:';
    }

    private function callBeforeRefreshParallelPhyiscalDatabase(): void
    {
        foreach ($this->beforeRefreshParallelPhyiscalDatabaseCallbacks as $callback) {
            $callback();
        }
    }
}
