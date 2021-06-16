<?php

namespace Recca0120\AsyncTesting\Tests;

use GuzzleHttp\Promise\Utils;
use Illuminate\Testing\TestResponse;
use Recca0120\AsyncTesting\AsyncRequest;
use Throwable;

class AsyncRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        AsyncRequest::setBinary(__DIR__.'/fixtures/artisan');
    }

    public function test_it_should_return_test_response(): void
    {
        $asyncRequest = AsyncRequest::create()->from('/foo');

        $asyncRequest->get('/')->wait()
            ->assertOk()
            ->assertSee('Hello World');
    }

    public function test_it_should_return_previous_url()
    {
        $from = '/foo';
        $asyncRequest = AsyncRequest::create()->from($from);

        $asyncRequest->get('/previous_url')->wait()
            ->assertOk()
            ->assertSee($from);
    }

    public function test_it_should_has_db_connection_in_server_variables()
    {
        $asyncRequest = AsyncRequest::create(['CUSTOM' => 'custom']);

        $asyncRequest->getJson('/server_variables')->wait()
            ->assertOk()
            ->assertJson([
                'DB_CONNECTION' => 'testing',
                'CUSTOM' => 'custom',
            ]);
    }

    public function test_it_should_return_test_response_with_json_response(): void
    {
        $asyncRequest = AsyncRequest::create();

        $asyncRequest->json('GET', '/')->wait()
            ->assertOk()
            ->assertJson(['content' => 'Hello World']);
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_get_json_ten_times(): void
    {
        $batch = AsyncRequest::create()->times(10);

        $responses = Utils::unwrap($batch->json('GET', '/'));

        self::assertCount(10, $responses);
    }

    /**
     * @dataProvider httpStatusCodeProvider
     * @param int $code
     */
    public function test_it_should_assert_http_status_code(int $code): void
    {
        AsyncRequest::create()->get('/status_code/'.$code)->wait()
            ->assertStatus($code);
    }

    /**
     * @return int[][]
     */
    public function httpStatusCodeProvider(): array
    {
        return array_map(static function ($code) {
            return [$code];
        }, [401, 403, 404, 500, 504]);
    }
}
