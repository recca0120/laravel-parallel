<?php

namespace Recca0120\AsyncTesting;

use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\Promise\RejectedPromise;
use InvalidArgumentException;
use Recca0120\AsyncTesting\Concerns\MakesHttpRequests;
use Recca0120\AsyncTesting\Concerns\MakesIlluminateResponses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class AsyncRequest
{
    use MakesHttpRequests;
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
        ]);

        return (new FulfilledPromise($this->createProcess($uri, $options)))
            ->then(function (Process $process) {
                $process->wait();
                $message = $process->getOutput();

                try {
                    return $this->toTestResponse($message);
                } catch (InvalidArgumentException $e) {
                    return new RejectedPromise(new InvalidArgumentException(
                        $e->getMessage().PHP_EOL.$message, $e->getCode(), $e
                    ));
                }
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
