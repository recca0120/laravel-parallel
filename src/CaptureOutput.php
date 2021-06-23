<?php

namespace Recca0120\AsyncTesting;

class CaptureOutput
{
    /**
     * @var callable|null
     */
    private static $printClosure;

    /**
     * @param string $output
     * @return string
     */
    public static function capture(string $output): string
    {
        preg_match('/^(?<output>.*)(?<message>HTTP\/.*\s[\d]{3}(\s.*)\r\n.*)/s', $output, $matches);

        if (! array_key_exists('message', $matches)) {
            $matches = ['output' => $output, 'message' => "HTTP/1.1 200 OK\r\n\r\n"];
        }

        self::printOutput($matches['output']);

        return $matches['message'];
    }

    /**
     * @param callable $callable
     */
    public static function printUsing(callable $callable): void
    {
        self::$printClosure = $callable;
    }

    /**
     * @param string $output
     */
    private static function printOutput(string $output): void
    {
        if (self::$printClosure !== null) {
            (self::$printClosure)($output);
        } else {
            echo $output;
        }
    }
}
