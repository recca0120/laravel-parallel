<?php

namespace Recca0120\LaravelParallel;

class PreventEcho
{
    /**
     * @var callable|null
     */
    private static $echoCallable = [self::class, 'output'];

    public static function prevent(string $output): string
    {
        preg_match('/^(?<output>.*)(?<message>HTTP\/.*\s\d{3}([^\r\n]*)\r\n.*)/s', $output, $matches);

        if (! array_key_exists('message', $matches)) {
            $matches = ['output' => $output, 'message' => "HTTP/1.1 200 OK\r\n\r\n"];
        }

        self::echoOutput($matches['output']);

        return $matches['message'];
    }

    public static function echoUsing(callable $callable): void
    {
        self::$echoCallable = $callable;
    }

    private static function echoOutput(string $output): void
    {
        $cb = self::$echoCallable;
        $cb($output);
    }

    private static function output($output): void
    {
        echo $output;
    }
}
