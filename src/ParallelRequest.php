<?php

namespace Recca0120\LaravelParallel;

use Carbon\Carbon;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Recca0120\LaravelParallel\Concerns\InteractsWithAuthentication;
use Recca0120\LaravelParallel\Concerns\MakesHttpRequests;
use Recca0120\LaravelParallel\Console\ParallelCommand;

class ParallelRequest
{
    use MakesHttpRequests;
    use InteractsWithAuthentication;

    /**
     * @var Request|null
     */
    private $request;

    /**
     * AsyncRequest constructor.
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request ?? Request::capture();
    }

    public static function setBinary(string $binary): void
    {
        ParallelArtisan::setBinary($binary);
    }

    /**
     * @return $this
     */
    public static function create(Request $request = null): self
    {
        return new static($request);
    }

    /**
     * Call the given URI and return the Response.
     *
     * @param  null  $content
     */
    public function call(
        string $method,
        string $uri,
        array $parameters = [],
        array $cookies = [],
        array $files = [],
        array $server = [],
        $content = null
    ): PromiseInterface {
        $artisan = new ParallelArtisan($this->request, $this->serverVariables);
        $params = $this->toParams($uri, $method, $parameters, $cookies, $files, $server, $content);

        return $artisan->call(ParallelCommand::COMMAND_NAME, $params)
            ->then(function () use ($artisan) {
                return $this->updateCookies(
                    ResponseIdentifier::fromMessage($artisan->output())->toResponse()
                );
            });
    }

    public function times(int $times): BatchRequest
    {
        return new BatchRequest($this, $times);
    }

    private function toParams(
        string $uri,
        string $method,
        array $parameters,
        array $cookies,
        array $files,
        array $server,
        $content
    ): array {
        return [
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
            '--testNow' => Carbon::getTestNow() ? Carbon::getTestNow()->toIso8601String() : null,
            '--ansi',
        ];
    }

    private function updateCookies(Response $response): Response
    {
        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            $this->withCookie($cookie->getName(), rawurldecode($cookie->getValue()));
        }

        return $response;
    }
}
