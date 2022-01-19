<?php

namespace Recca0120\LaravelParallel;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Request;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ParallelArtisan
{
    /**
     * @var string|null
     */
    private static $phpBinary;
    /**
     * @var string
     */
    private static $binary = 'artisan';
    /**
     * @var Request
     */
    private $request;
    /**
     * @var array
     */
    private $env;
    /**
     * @var Process
     */
    private $process;

    public function __construct(Request $request, array $env = [])
    {
        $this->request = $request;
        $this->setEnv($env);
    }

    public function setEnv(array $env): self
    {
        $this->env = $env;

        return $this;
    }

    public function call(string $command, array $parameters = []): PromiseInterface
    {
        $this->process = new Process($this->getCommand($command, $parameters), null, $this->getEnv(), null, 86400);

        $promise = new Promise(function () {
            $this->process->wait();
        });

        $this->process->start(function () use ($promise) {
            $promise->resolve($this->process->getExitCode());
        });

        return $promise;
    }

    public function output(): string
    {
        return $this->process->getOutput();
    }

    public static function setBinary(string $binary): void
    {
        self::$binary = $binary;
    }

    private function getCommand(string $command, array $parameters): array
    {
        return array_merge([
            $this->getPhpBinary(),
            self::$binary,
            $command,
        ], $this->parseParameters($parameters));
    }

    private function parseParameters(array $parameters): array
    {
        $parameters = array_map(static function ($parameter) {
            return is_array($parameter) ? json_encode($parameter) : $parameter;
        }, $parameters);

        $params = [];
        foreach ($parameters as $param => $val) {
            if ($param && is_string($param) && '-' === $param[0]) {
                $glue = ('-' === $param[1]) ? '=' : ' ';
                if (is_array($val)) {
                    foreach ($val as $v) {
                        $params[] = $param.('' !== $v ? $glue.$v : '');
                    }
                } else {
                    $params[] = $param.('' !== $val ? $glue.$val : '');
                }
            } else {
                $params[] = is_array($val) ? implode(' ', $val) : $val;
            }
        }

        return $params;
    }

    private function getEnv(): array
    {
        return array_filter(array_merge($this->request->server->all(), $_ENV, $this->env), static function ($env) {
            return ! is_array($env);
        });
    }

    private function getPhpBinary(): string
    {
        if (! self::$phpBinary) {
            self::$phpBinary = (new PhpExecutableFinder())->find(false);
        }

        return self::$phpBinary;
    }
}
