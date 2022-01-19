<?php

namespace Recca0120\LaravelParallel\Tests;

use Illuminate\Http\Request;
use Recca0120\LaravelParallel\Artisan;
use Throwable;

class ArtisanTest extends TestCase
{
    /**
     * @throws Throwable
     */
    public function test_echo_command(): void
    {
        Artisan::setBinary(__DIR__.'/Fixtures/artisan');
        $artisan = new Artisan(Request::capture());

        self::assertEquals(0, $artisan->call('echo', ['hello world'])->wait());
        self::assertEquals('hello world', $artisan->output());
    }
}
