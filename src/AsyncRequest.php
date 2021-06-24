<?php

namespace Recca0120\AsyncTesting;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Recca0120\AsyncTesting\Concerns\InteractsWithAuthentication;
use Recca0120\AsyncTesting\Concerns\MakesHttpRequests;
use Recca0120\AsyncTesting\Concerns\MakesIlluminateResponses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class AsyncRequest
{
    use MakesHttpRequests;
    use InteractsWithAuthentication;
    use MakesIlluminateResponses;

    /**
     * @var string|null
     */
    private $phpBinary;

    /**
     * @var string|null
     */
    private static $binary = 'artisan';

    /**
     * AsyncRequest constructor.
     * @param array $serverVariables
     */
    public function __construct(array $serverVariables = [])
    {
        $this->withServerVariables(
            array_merge(Request::createFromGlobals()->server->all(), $serverVariables)
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
            'user' => $this->user,
            'guard' => $this->guard,
        ]);

        $process = $this->createProcess($uri, $options);
        $promise = new Promise(function () use ($process) {
            $process->wait();
        });
        $process->start(function () use ($process, $promise) {
            $promise->resolve($process);
        });

        return $promise->then(function (Process $process) {
            $response = $this->toTestResponse(CaptureOutput::capture($process->getOutput()));
            $cookies = $response->headers->getCookies();
            foreach ($cookies as $cookie) {
                $this->withCookie($cookie->getName(), rawurldecode($cookie->getValue()));
            }

            return $response;
        });
    }

    /**
     * @param int $times
     * @return BatchRequest
     */
    public function times(int $times): BatchRequest
    {
        return new BatchRequest($this, $times);
    }

    /**
     * @param string $binary
     */
    public static function setBinary(string $binary): void
    {
        self::$binary = $binary;
    }

    /**
     * @param array $serverVariables
     * @return $this
     */
    public static function create(array $serverVariables = []): self
    {
        return new self($serverVariables);
    }

    /**
     * @param string $uri
     * @param array $options
     * @return Process
     */
    private function createProcess(string $uri, array $options): Process
    {
        $command = array_merge([
            $this->getPhpBinary(),
            $this->getBinary(),
            'async:request',
            $uri,
        ], $options, ['--ansi']);

        return new Process($command, null, $this->serverVariables, null, 86400);
    }

    /**
     * @param array $data
     * @return array
     */
    private static function toCommandOptions(array $data): array
    {
        $data = array_merge(['parameters' => '[]'], array_filter($data, static function ($value) {
            return ! empty($value);
        }));

        $options = [];
        foreach ($data as $key => $value) {
            $options[] = '--'.$key.'='.(is_array($value) ? json_encode($value) : $value);
        }

        return $options;
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
