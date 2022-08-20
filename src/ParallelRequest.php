<?php

namespace Recca0120\LaravelParallel;

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
     * @param Request|null $request
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request ?? Request::capture();
    }

    /**
     * @param string $binary
     */
    public static function setBinary(string $binary): void
    {
        ParallelArtisan::setBinary($binary);
    }

    /**
     * @param Request|null $request
     * @return $this
     */
    public static function create(Request $request = null): self
    {
        return new static($request);
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
        $artisan = new ParallelArtisan($this->request, $this->serverVariables);
        $params = $this->toParams($uri, $method, $parameters, $cookies, $files, $server, $content);

        return $artisan->call(ParallelCommand::COMMAND_NAME, $params)
            ->then(function () use ($artisan) {
                return $this->updateCookies(
                    ResponseIdentifier::fromMessage($artisan->output())->toResponse()
                );
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
     * @param string $uri
     * @param string $method
     * @param array $parameters
     * @param array $cookies
     * @param array $files
     * @param array $server
     * @param $content
     * @return array
     */
    private function toParams(string $uri, string $method, array $parameters, array $cookies, array $files, array $server, $content): array
    {
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
            '--ansi',
        ];
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
}
