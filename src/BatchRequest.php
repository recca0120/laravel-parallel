<?php

namespace Recca0120\LaravelParallel;

use GuzzleHttp\Promise\PromiseInterface;

/**
 * @method self withHeaders(array $headers)
 * @method self withHeader(string $name, string $value)
 * @method self withToken(string $token, string $type = 'Bearer')
 * @method self flushHeaders()
 * @method self withServerVariables(array $server)
 * @method self withoutMiddleware($middleware = null)
 * @method self withMiddleware($middleware = null)
 * @method self withCookies(array $cookies)
 * @method self withCookie(string $name, string $value)
 * @method self withUnencryptedCookies(array $cookies)
 * @method self withUnencryptedCookie(string $name, string $value)
 * @method self followingRedirects()
 * @method self withCredentials()
 * @method self disableCookieEncryption()
 * @method self from(string $url)
 * @method PromiseInterface[] get(string $uri, array $headers = [])
 * @method PromiseInterface[] getJson(string $uri, array $headers = [])
 * @method PromiseInterface[] post(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] postJson(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] put(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] putJson(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] patch(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] patchJson(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] delete(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] deleteJson(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] options(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] optionsJson(string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] json(string $method, string $uri, array $data = [], array $headers = [])
 * @method PromiseInterface[] call(string $method, string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null)
 */
class BatchRequest
{
    /**
     * @var ParallelRequest
     */
    private $request;

    /**
     * @var int
     */
    private $times;

    /**
     * @var string[]
     */
    private $executeMethods = [
        'get',
        'getJson',
        'post',
        'postJson',
        'put',
        'putJson',
        'patch',
        'patchJson',
        'delete',
        'deleteJson',
        'options',
        'optionsJson',
        'json',
        'call',
    ];

    /**
     * Batch constructor.
     *
     * @param  ParallelRequest  $request
     * @param  int  $times
     */
    public function __construct(ParallelRequest $request, int $times)
    {
        $this->request = $request;
        $this->times = $times;
    }

    /**
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return ! in_array($method, $this->executeMethods, true)
            ? call_user_func_array([$this->request, $method], $arguments)
            : $this->handle(function () use ($method, $arguments) {
                return call_user_func_array([$this->request, $method], $arguments);
            });
    }

    /**
     * @param  callable  $callable
     * @return PromiseInterface[]
     */
    public function handle(callable $callable): array
    {
        return array_map(function ($index) use ($callable) {
            return $callable($this->request, $index);
        }, range(0, $this->times - 1));
    }
}
