<?php

namespace Recca0120\LaravelParallel\Tests;

use function Amp\call;
use Amp\Promise;
use Recca0120\LaravelParallel\ParallelRequest as BaseParallelRequest;

class ParallelRequest extends BaseParallelRequest
{
    public function call(string $method, string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null): Promise
    {
        return call(function () use ($method, $uri, $parameters, $cookies, $files, $server, $content) {
            $response = yield parent::call($method, $uri, $parameters, $cookies, $files, $server, $content);
            $class = class_exists(\Illuminate\Testing\TestResponse::class)
                ? \Illuminate\Testing\TestResponse::class
                : \Illuminate\Foundation\Testing\TestResponse::class;

            return new $class($response);
        });
    }
}
