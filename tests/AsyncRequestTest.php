<?php

namespace Recca0120\ParallelTest\Tests;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Testing\TestResponse;
use Recca0120\ParallelTest\AsyncRequest;

class AsyncRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        AsyncRequest::setBinary(__DIR__.'/fixtures/artisan');
    }

    public function test_handle_async_request(): void
    {
        $asyncRequest = new AsyncRequest();

        $response = $this->toTestResponse(
            $asyncRequest->get('/', ['HTTP_FOO' => 'foo'])
        );

        $response
            ->assertOk()
            ->assertSee('Hello World');
    }

    public function test_handle_async_json_request(): void
    {
        $asyncRequest = new AsyncRequest();

        $response = $this->toTestResponse(
            $asyncRequest->json('GET', '/', [], ['HTTP_FOO' => 'foo'])
        );

        $response
            ->assertOk()
            ->assertJson(['content' => 'Hello World']);
    }

    public function test_handle_not_found_request(): void
    {
        $asyncRequest = new AsyncRequest();

        $response = $this->toTestResponse(
            $asyncRequest->get('/404', ['HTTP_FOO' => 'foo'])
        );

        $response->assertNotFound();
    }

    /**
     * @param PromiseInterface $promise
     * @return TestResponse
     */
    private function toTestResponse(PromiseInterface $promise): TestResponse
    {
        return $promise->wait(true);
    }
}
