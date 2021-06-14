<?php

namespace Recca0120\AsyncTesting;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class AsyncRequest
{
    /**
     * Additional cookies for the request.
     *
     * @var array
     */
    protected $defaultCookies = [];

    /**
     * Additional cookies will not be encrypted for the request.
     *
     * @var array
     */
    protected $unencryptedCookies = [];

    /**
     * Additional server variables for the request.
     *
     * @var array
     */
    protected $serverVariables = [];

    /**
     * Indicates whether redirects should be followed.
     *
     * @var bool
     */
    protected $followRedirects = false;

    /**
     * Indicates whether cookies should be encrypted.
     *
     * @var bool
     */
    protected $encryptCookies = true;

    /**
     * Indicated whether JSON requests should be performed "with credentials" (cookies).
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/API/XMLHttpRequest/withCredentials
     *
     * @var bool
     */
    protected $withCredentials = false;

    /**
     * @var array
     */
    protected $withoutMiddleware = [];

    /**
     * @var array
     */
    protected $withMiddleware = [];

    /**
     * @var string|null
     */
    private $phpBinary;

    /**
     * @var string|null
     */
    private static $binary = 'artisan';

    /**
     * @var array
     */
    private $defaultHeaders = [];

    /**
     * Define additional headers to be sent with the request.
     *
     * @param array $headers
     * @return $this
     */
    public function withHeaders(array $headers): self
    {
        $this->defaultHeaders = array_merge($this->defaultHeaders, $headers);

        return $this;
    }

    /**
     * Add a header to be sent with the request.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function withHeader(string $name, string $value): self
    {
        $this->defaultHeaders[$name] = $value;

        return $this;
    }

    /**
     * Add an authorization token for the request.
     *
     * @param string $token
     * @param string $type
     * @return $this
     */
    public function withToken(string $token, string $type = 'Bearer'): self
    {
        return $this->withHeader('Authorization', $type.' '.$token);
    }

    /**
     * Flush all the configured headers.
     *
     * @return $this
     */
    public function flushHeaders(): self
    {
        $this->defaultHeaders = [];

        return $this;
    }

    /**
     * Define a set of server variables to be sent with the requests.
     *
     * @param array $server
     * @return $this
     */
    public function withServerVariables(array $server): self
    {
        $this->serverVariables = $server;

        return $this;
    }

    /**
     * Disable middleware for the test.
     *
     * @param string|array|null $middleware
     * @return $this
     */
    public function withoutMiddleware($middleware = null): self
    {
        $this->withoutMiddleware[] = func_get_args();

        return $this;
    }

    /**
     * Enable the given middleware for the test.
     *
     * @param string|array|null $middleware
     * @return $this
     */
    public function withMiddleware($middleware = null): self
    {
        $this->withMiddleware[] = func_get_args();

        return $this;
    }

    /**
     * Define additional cookies to be sent with the request.
     *
     * @param array $cookies
     * @return $this
     */
    public function withCookies(array $cookies): self
    {
        $this->defaultCookies = array_merge($this->defaultCookies, $cookies);

        return $this;
    }

    /**
     * Add a cookie to be sent with the request.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function withCookie(string $name, string $value): self
    {
        $this->defaultCookies[$name] = $value;

        return $this;
    }

    /**
     * Define additional cookies will not be encrypted before sending with the request.
     *
     * @param array $cookies
     * @return $this
     */
    public function withUnencryptedCookies(array $cookies): self
    {
        $this->unencryptedCookies = array_merge($this->unencryptedCookies, $cookies);

        return $this;
    }

    /**
     * Add a cookie will not be encrypted before sending with the request.
     *
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function withUnencryptedCookie(string $name, string $value): self
    {
        $this->unencryptedCookies[$name] = $value;

        return $this;
    }

    /**
     * Automatically follow any redirects returned from the response.
     *
     * @return $this
     */
    public function followingRedirects(): self
    {
        $this->followRedirects = true;

        return $this;
    }

    /**
     * Include cookies and authorization headers for JSON requests.
     *
     * @return $this
     */
    public function withCredentials(): self
    {
        $this->withCredentials = true;

        return $this;
    }

    /**
     * Disable automatic encryption of cookie values.
     *
     * @return $this
     */
    public function disableCookieEncryption(): self
    {
        $this->encryptCookies = false;

        return $this;
    }

    /**
     * Set the referer header and previous URL session value in order to simulate a previous request.
     *
     * @param string $url
     * @return $this
     */
    public function from(string $url): self
    {
        return $this->withHeader('referer', $url);
    }

    /**
     * Visit the given URI with a GET request.
     *
     * @param string $uri
     * @param array $headers
     * @return PromiseInterface
     */
    public function get(string $uri, array $headers = []): PromiseInterface
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('GET', $uri, [], $cookies, [], $server);
    }

    /**
     * Visit the given URI with a GET request, expecting a JSON response.
     *
     * @param string $uri
     * @param array $headers
     * @return PromiseInterface
     */
    public function getJson(string $uri, array $headers = []): PromiseInterface
    {
        return $this->json('GET', $uri, [], $headers);
    }

    /**
     * Visit the given URI with a POST request.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function post(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('POST', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a POST request, expecting a JSON response.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function postJson(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        return $this->json('POST', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a PUT request.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function put(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('PUT', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a PUT request, expecting a JSON response.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function putJson(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        return $this->json('PUT', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a PATCH request.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function patch(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('PATCH', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a PATCH request, expecting a JSON response.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function patchJson(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        return $this->json('PATCH', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with a DELETE request.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function delete(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('DELETE', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with a DELETE request, expecting a JSON response.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function deleteJson(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        return $this->json('DELETE', $uri, $data, $headers);
    }

    /**
     * Visit the given URI with an OPTIONS request.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function options(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        $server = $this->transformHeadersToServerVars($headers);
        $cookies = $this->prepareCookiesForRequest();

        return $this->call('OPTIONS', $uri, $data, $cookies, [], $server);
    }

    /**
     * Visit the given URI with an OPTIONS request, expecting a JSON response.
     *
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function optionsJson(string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        return $this->json('OPTIONS', $uri, $data, $headers);
    }

    /**
     * Call the given URI with a JSON request.
     *
     * @param string $method
     * @param string $uri
     * @param array $data
     * @param array $headers
     * @return PromiseInterface
     */
    public function json(string $method, string $uri, array $data = [], array $headers = []): PromiseInterface
    {
        $content = json_encode($data);

        $headers = array_merge([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/json',
            'Accept' => 'application/json',
        ], $headers);

        return $this->call(
            $method,
            $uri,
            [],
            $this->prepareCookiesForRequest(),
            [],
            $this->transformHeadersToServerVars($headers),
            $content
        );
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param string $method
     * @param string $uri
     * @param array $parameters
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param null $content
     * @return PromiseInterface
     */
    public function call(string $method, string $uri, array $parameters = [], array $cookies = [], array $files = [], array $server = [], $content = null): PromiseInterface
    {
        $options = self::toCommandOptions([
            'method' => $method,
            'parameters' => $parameters,
            'cookies' => $cookies,
            'files' => $files,
            'server' => $server,
            'content' => $content,
            'withoutMiddleware' => $this->withoutMiddleware,
            'withMiddleware' => $this->withMiddleware,
            'withUnencryptedCookies' => $this->unencryptedCookies,
            // 'serverVariables' => $this->serverVariables,
            'followRedirects' => $this->followRedirects,
            'withCredentials' => $this->withCredentials,
            'disableCookieEncryption' => ! $this->encryptCookies,
        ]);

        return (new FulfilledPromise($this->createProcess($uri, $options)))
            ->then(function (Process $process) {
                $process->wait();

                return AsyncResponse::create($process->getOutput())->toTestResponse();
            });
    }

    /**
     * @param string $binary
     */
    public static function setBinary(string $binary): void
    {
        self::$binary = $binary;
    }

    /**
     * @param array|null $serverVariables
     * @return $this
     */
    public static function create(array $serverVariables = null): self
    {
        return (new self())->withServerVariables(
            $serverVariables ?: Request::createFromGlobals()->server->all()
        );
    }

    /**
     * @param string $uri
     * @param array $options
     * @return Process
     */
    private function createProcess(string $uri, array $options): Process
    {
        $command = [$this->getPhpBinary(), $this->getBinary(), 'async:request', $uri];
        $process = new Process(
            array_merge($command, $options),
            null,
            $this->serverVariables,
            null,
            86400
        );
        $process->start();

        return $process;
    }

    /**
     * @param array $data
     * @return array
     */
    private static function toCommandOptions(array $data): array
    {
        $options = [];
        foreach ($data as $key => $value) {
            $value = is_array($value) ? json_encode($value) : $value;
            if (! empty($value)) {
                $options[] = '--'.$key.'='.$value;
            }
        }

        return $options;
    }

    /**
     * @param array $headers
     * @return array
     */
    private function transformHeadersToServerVars(array $headers): array
    {
        $result = [];
        $headers = array_merge($this->defaultHeaders, $headers);
        foreach ($headers as $name => $value) {
            $name = str_replace('-', '_', strtoupper($name));

            $result[$this->formatServerHeaderKey($name)] = $value;
        }

        return $result;
    }

    /**
     * @param string $name
     * @return string
     */
    private function formatServerHeaderKey(string $name): string
    {
        if (! (strpos($name, 'HTTP_') === 0) && ! in_array($name, ['CONTENT_TYPE', 'REMOTE_ADDR'], true)) {
            return 'HTTP_'.$name;
        }

        return $name;
    }

    /**
     * @return array
     */
    private function prepareCookiesForRequest(): array
    {
        return $this->defaultCookies;
    }

    /**
     * @return false|string
     */
    private function getPhpBinary()
    {
        if (! $this->phpBinary) {
            $this->phpBinary = (new PhpExecutableFinder())->find(false);
        }

        return $this->phpBinary;
    }

    /**
     * @return string
     */
    private function getBinary(): string
    {
        return self::$binary;
    }
}
