<?php

namespace Recca0120\LaravelParallel;

use function Amp\ByteStream\buffer;
use function Amp\call;
use Amp\Process\Process;
use Amp\Promise;
use Illuminate\Http\Request;
use Symfony\Component\Process\PhpExecutableFinder;
use Throwable;

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
    private $_output;

    public function __construct(Request $request = null, array $env = [])
    {
        $this->request = $request ?? Request::capture();
        $this->setEnv($env);
    }

    public function setEnv(array $env): self
    {
        $this->env = $env;

        return $this;
    }

    /**
     * @throws Throwable
     */
    public function call(string $command, array $parameters = []): Promise
    {
        $this->_output = null;
        $this->process = new Process($this->getCommand($command, $parameters), null, $this->getEnv());

        return call(function () {
            yield $this->process->start();
            $this->_output = yield buffer($this->process->getStdout());

            return yield $this->process->join();
        });
    }

    public function output(): string
    {
        return $this->_output;
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
        $env = array_merge($this->request->server->all(), $_ENV, $this->env);
        foreach (['argc', 'argv', 'ARGC', 'ARGV'] as $key) {
            if (array_key_exists($key, $env)) {
                unset($env[$key]);
            }
        }

        return $env;
    }

    private function getPhpBinary(): string
    {
        if (! self::$phpBinary) {
            self::$phpBinary = (new PhpExecutableFinder())->find(false);
        }

        return self::$phpBinary;
    }
}
