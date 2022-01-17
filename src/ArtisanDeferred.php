<?php

namespace Recca0120\AsyncTesting;

use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use Symfony\Component\Process\PhpExecutableFinder;
use Symfony\Component\Process\Process;

class ArtisanDeferred
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
     * @var string
     */
    private $method;
    /**
     * @var array
     */
    private $parameters;
    /**
     * @var array
     */
    private $env;

    public function __construct(string $method, array $parameters = [], array $env = [])
    {
        $this->method = $method;
        $this->parameters = $parameters;
        $this->env = $env;
    }

    public function promise(): PromiseInterface
    {
        $process = new Process($this->getCommand(), null, $this->env, null, 86400);

        $promise = new Promise(function () use ($process) {
            $process->wait();
        });

        $process->start(function () use ($process, $promise) {
            $promise->resolve($process);
        });

        return $promise;
    }

    public static function setBinary(string $binary): void
    {
        self::$binary = $binary;
    }

    private function getCommand(): array
    {
        return array_merge([
            $this->getPhpBinary(),
            self::$binary,
            $this->method,
        ], $this->parseParameters(), ['--ansi']);
    }

    private function parseParameters(): array
    {
        $parameters = array_merge(['--parameters' => []], array_filter($this->parameters, static function ($parameter) {
            return ! empty($parameter);
        }));

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

    private function getPhpBinary(): string
    {
        if (! self::$phpBinary) {
            self::$phpBinary = (new PhpExecutableFinder())->find(false);
        }

        return self::$phpBinary;
    }
}
