<?php

namespace Recca0120\LaravelParallel\Tests;

use function Amp\Promise\wait;
use Illuminate\Http\Request;
use Recca0120\LaravelParallel\ParallelArtisan;
use Throwable;

class ParallelArtisanTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function test_echo_command(): void
    {
        ParallelArtisan::setBinary(__DIR__.'/Fixtures/artisan');
        $artisan = new ParallelArtisan(Request::capture());

        self::assertEquals(0, wait($artisan->call('echo', ['hello world'])));
        self::assertEquals('hello world', $artisan->output());
    }
}
