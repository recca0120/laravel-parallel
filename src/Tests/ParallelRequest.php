<?php

namespace Recca0120\LaravelParallel\Tests;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Response;
use Recca0120\LaravelParallel\ParallelRequest as BaseParallelRequest;

class ParallelRequest extends BaseParallelRequest
{
    public function call(string $method, string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null): PromiseInterface
    {
        return parent::call($method, $uri, $parameters, $cookies, $files, $server, $content)
            ->then(static function (Response $response) {
                $class = class_exists(\Illuminate\Testing\TestResponse::class)
                    ? \Illuminate\Testing\TestResponse::class
                    : \Illuminate\Foundation\Testing\TestResponse::class;

                return new $class($response);
            });
    }
}
