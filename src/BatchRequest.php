<?php

namespace Recca0120\LaravelParallel;

use Amp\Promise;

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
 * @method Promise[] get(string $uri, array $headers = [])
 * @method Promise[] getJson(string $uri, array $headers = [])
 * @method Promise[] post(string $uri, array $data = [], array $headers = [])
 * @method Promise[] postJson(string $uri, array $data = [], array $headers = [])
 * @method Promise[] put(string $uri, array $data = [], array $headers = [])
 * @method Promise[] putJson(string $uri, array $data = [], array $headers = [])
 * @method Promise[] patch(string $uri, array $data = [], array $headers = [])
 * @method Promise[] patchJson(string $uri, array $data = [], array $headers = [])
 * @method Promise[] delete(string $uri, array $data = [], array $headers = [])
 * @method Promise[] deleteJson(string $uri, array $data = [], array $headers = [])
 * @method Promise[] options(string $uri, array $data = [], array $headers = [])
 * @method Promise[] optionsJson(string $uri, array $data = [], array $headers = [])
 * @method Promise[] json(string $method, string $uri, array $data = [], array $headers = [])
 * @method Promise[] call(string $method, string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null)
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
     * @param ParallelRequest $request
     * @param int $times
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
     * @param callable $callable
     * @return Promise[]
     */
    public function handle(callable $callable): array
    {
        return array_map(function ($index) use ($callable) {
            return $callable($this->request, $index);
        }, range(0, $this->times - 1));
    }
}
