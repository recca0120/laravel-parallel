<?php

namespace Recca0120\ParallelTest;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\JsonResponse as IlluminateJsonResponse;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class AsyncRequest
{
    private $env = [];
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
     * @param array $env
     * @return $this
     */
    public function setEnv(array $env = []): self
    {
        $this->env = $env;

        return $this;
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
        $options = self::toOptions([
            'method' => $method,
            'parameters' => json_encode($parameters),
            'cookies' => json_encode($cookies),
            'files' => json_encode($files),
            'server' => json_encode($server),
            'content' => $content,
            'call' => true,
        ]);

        $phpBinary = $this->getPhpBinary();
        $binary = $this->getBinary();
        $command = array_merge([$phpBinary, $binary, 'async:call', $uri], $options);
        $process = new Process($command, null, $this->env, null, 86400);
        $process->start();

        return (new FulfilledPromise($process))->then(function (Process $process) {
            $process->wait();

            return $this->toTestResponse($process->getOutput());
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
     * @param string $message
     * @return TestResponse
     */
    private function toTestResponse(string $message): TestResponse
    {
        return new TestResponse($this->toIlluminateResponse($message));
    }

    /**
     * @return IlluminateJsonResponse|IlluminateResponse
     */
    private function toIlluminateResponse(string $message)
    {
        $response = Message::parseResponse($message);
        $statusCode = $response->getStatusCode();
        $headers = $response->getHeaders();
        $content = (string) $response->getBody();

        return $this->isJson($response)
            ? IlluminateJsonResponse::fromJsonString($content, $statusCode, $headers)
            : new IlluminateResponse($content, $statusCode, $headers);
    }

    /**
     * @param Response $response
     * @return bool
     */
    private function isJson(Response $response): bool
    {
        return $response->hasHeader('content-type') && strpos($response->getHeader('content-type')[0], 'json') !== false;
    }

    /**
     * @param array $data
     * @return array
     */
    private static function toOptions(array $data): array
    {
        $options = [];
        foreach ($data as $key => $value) {
            $options[] = '--'.$key.'='.$value;
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
        return [];
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
