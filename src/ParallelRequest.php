<?php

namespace Recca0120\LaravelParallel;

use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Recca0120\LaravelParallel\Concerns\InteractsWithAuthentication;
use Recca0120\LaravelParallel\Concerns\MakesHttpRequests;
use Recca0120\LaravelParallel\Console\ParallelCommand;
use Symfony\Component\Process\Process;

class ParallelRequest
{
    use MakesHttpRequests;
    use InteractsWithAuthentication;

    /**
     * AsyncRequest constructor.
     * @param array $serverVariables
     */
    public function __construct(array $serverVariables = [])
    {
        $this->withServerVariables($serverVariables);
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
        return (new Artisan(Request::capture(), $this->serverVariables))->call(ParallelCommand::COMMAND_NAME, [
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
            '--ansi',
        ])->then(function (Process $process) {
            return $this->updateCookies($this->toResponse($process->getOutput()));
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
        Artisan::setBinary($binary);
    }

    /**
     * @param array $serverVariables
     * @return $this
     */
    public static function create(array $serverVariables = []): self
    {
        return new static($serverVariables);
    }

    /**
     * @param Response $response
     * @return Response
     */
    private function updateCookies(Response $response): Response
    {
        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            $this->withCookie($cookie->getName(), rawurldecode($cookie->getValue()));
        }

        return $response;
    }

    /**
     * @param string $message
     * @return Response
     */
    private function toResponse(string $message): Response
    {
        $response = Message::parseResponse(PreventEcho::prevent($message));

        return new Response((string) $response->getBody(), $response->getStatusCode(), $response->getHeaders());
    }
}
