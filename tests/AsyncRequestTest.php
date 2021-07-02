<?php

namespace Recca0120\AsyncTesting\Tests;

use Illuminate\Auth\GenericUser;
use Recca0120\AsyncTesting\AsyncRequest;
use Recca0120\AsyncTesting\Tests\Fixtures\User;
use Throwable;

class AsyncRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        AsyncRequest::setBinary(__DIR__.'/Fixtures/artisan');
    }

    public function test_it_should_return_test_response(): void
    {
        $asyncRequest = AsyncRequest::create()->from('/foo');

        $response = $asyncRequest->get('/')->wait();

        $response->assertOk()->assertSee('Hello World');
    }

    public function test_it_should_return_previous_url(): void
    {
        $from = '/foo';
        $asyncRequest = AsyncRequest::create()->from($from);

        $response = $asyncRequest->get('/previous_url')->wait();

        $response->assertOk()->assertSee($from);
    }

    public function test_it_should_has_db_connection_in_server_variables(): void
    {
        $asyncRequest = AsyncRequest::create(['CUSTOM' => 'custom']);

        $response = $asyncRequest->getJson('/server_variables')->wait();

        $response->assertOk()->assertJson([
            'DB_CONNECTION' => 'testbench',
            'CUSTOM' => 'custom',
        ]);
    }

    public function test_it_should_return_test_response_with_json_response(): void
    {
        $asyncRequest = AsyncRequest::create();

        $response = $asyncRequest->json('GET', '/')->wait();

        $response->assertOk()->assertJson(['content' => 'Hello World']);
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_get_json_ten_times(): void
    {
        $batch = AsyncRequest::create()->times(10);

        $responses = [];
        foreach ($batch->json('GET', '/') as $promise) {
            $responses[] = $promise->wait();
        }

        self::assertCount(10, $responses);
    }

    /**
     * @dataProvider httpStatusCodeProvider
     * @param int $code
     */
    public function test_it_should_assert_http_status_code(int $code): void
    {
        $response = AsyncRequest::create()->get('/status_code/'.$code)->wait();

        $response->assertStatus($code);
    }

    public function test_it_should_show_echo_in_console(): void
    {
        $this->expectOutputRegex('/echo foo/');

        $response = AsyncRequest::create()->get('/echo')->wait();

        $response->assertSee('bar');
    }

    public function test_it_should_show_dump_in_console(): void
    {
        $this->expectOutputRegex('/dump\(foo\)/');

        $response = AsyncRequest::create()->get('/dump')->wait();

        $response->assertSee('bar');
    }

    public function test_it_should_show_dd_in_console(): void
    {
        $this->expectOutputRegex('/dd\(foo\)/');

        AsyncRequest::create()->get('/dd')->wait();
    }

    public function test_it_should_show_generic_user_info(): void
    {
        $user = new GenericUser(['email' => 'recca0120@gmail.com']);
        $asyncRequest = AsyncRequest::create()->actingAs($user);

        $response = $asyncRequest->postJson('/user')->wait();

        $response->assertJsonPath('email', 'recca0120@gmail.com');
    }

    public function test_it_should_show_eloquent_user_info(): void
    {
        $asyncRequest = AsyncRequest::create()->actingAs(User::first(), 'api');

        $response = $asyncRequest->postJson('/api/user')->wait();

        $response->assertJsonPath('email', 'recca0120@gmail.com');
    }

    public function test_it_should_login_and_get_user_info(): void
    {
        $asyncRequest = AsyncRequest::create();

        $response = $asyncRequest->post('/auth/login', [
            'email' => 'recca0120@gmail.com',
            'password' => 'password',
        ])->wait();

        $response->assertJsonPath('email', 'recca0120@gmail.com');
    }

    public function test_it_should_get_user_info_with_token(): void
    {
        $token = '6Uv0zov7V2dAk5wWE45HHHhz05gpsmw2';
        $asyncRequest = AsyncRequest::create()->withToken($token);

        $response = $asyncRequest->post('/api/user')->wait();

        $response->assertJsonPath('email', 'recca0120@gmail.com');
    }

    public function test_it_should_get_session_value(): void
    {
        $sessionValue = uniqid('session_', true);
        $asyncRequest = AsyncRequest::create();
        $asyncRequest->patch('/session?session='.$sessionValue)->wait();

        $response = $asyncRequest->getJson('/session')->wait();

        $response->assertJsonPath('session', $sessionValue);
    }

    public function test_it_should_finish_10_requests_in_5_seconds(): void
    {
        $startTime = microtime(true);

        foreach (AsyncRequest::create()->times(10)->get('/sleep') as $promise) {
            $promise->wait();
        }

        self::assertLessThan(5, microtime(true) - $startTime);
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
