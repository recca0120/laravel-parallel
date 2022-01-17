<?php

namespace Recca0120\AsyncTesting;

use GuzzleHttp\Promise\PromiseInterface;
use Recca0120\AsyncTesting\Concerns\InteractsWithAuthentication;
use Recca0120\AsyncTesting\Concerns\MakesHttpRequests;
use Recca0120\AsyncTesting\Concerns\MakesIlluminateResponses;
use Recca0120\AsyncTesting\Console\AsyncRequestCommand;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\Process;

class AsyncRequest
{
    use MakesHttpRequests;
    use InteractsWithAuthentication;
    use MakesIlluminateResponses;

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
        $deferred = new ArtisanDeferred(AsyncRequestCommand::COMMAND_NAME, [
            $uri,
            '--method' => $method,
            '--parameters' => $parameters,
            '--cookies' => $cookies,
            '--files' => $files,
            '--server' => $server,
            '--content' => $content,
            '--withoutMiddleware' => $this->withoutMiddleware,
            '--withMiddleware' => $this->withMiddleware,
            '--withUnencryptedCookies' => $this->unencryptedCookies,
            // '--serverVariables' => $this->serverVariables,
            '--followRedirects' => $this->followRedirects,
            '--withCredentials' => $this->withCredentials,
            '--disableCookieEncryption' => ! $this->encryptCookies,
            '--user' => $this->user,
            '--guard' => $this->guard,
        ], $this->serverVariables);

        return $deferred->promise()->then(function (Process $process) {
            $response = $this->toTestResponse(PreventEcho::prevent($process->getOutput()));
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
        ArtisanDeferred::setBinary($binary);
    }

    /**
     * @param array $serverVariables
     * @return $this
     */
    public static function create(array $serverVariables = []): self
    {
        return new self($serverVariables);
    }
}
