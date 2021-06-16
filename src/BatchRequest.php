<?php

namespace Recca0120\AsyncTesting;

use Prophecy\Promise\PromiseInterface;

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
 * @method \GuzzleHttp\Promise\PromiseInterface[] get(string $uri, array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] getJson(string $uri, array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] post(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] postJson(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] put(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] putJson(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] patch(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] patchJson(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] delete(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] deleteJson(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] options(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] optionsJson(string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] json(string $method, string $uri, array $data = [], array $headers = [])
 * @method \GuzzleHttp\Promise\PromiseInterface[] call(string $method, string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null)
 */
class BatchRequest
{
    /**
     * @var AsyncRequest
     */
    private $asyncRequest;
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
     * @param AsyncRequest $asyncRequest
     * @param int $times
     */
    public function __construct(AsyncRequest $asyncRequest, int $times)
    {
        $this->asyncRequest = $asyncRequest;
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
            ? call_user_func_array([$this->asyncRequest, $method], $arguments)
            : $this->handle(function () use ($method, $arguments) {
                return call_user_func_array([$this->asyncRequest, $method], $arguments);
            });
    }

    /**
     * @param callable $callable
     * @return PromiseInterface[]
     */
    public function handle(callable $callable): array
    {
        return array_map(function ($index) use ($callable) {
            return $callable($this->asyncRequest, $index);
        }, range(0, $this->times - 1));
    }
}
