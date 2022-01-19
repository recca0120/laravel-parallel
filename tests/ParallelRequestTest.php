<?php

namespace Recca0120\LaravelParallel\Tests;

use function Amp\Promise\all;
use function Amp\Promise\wait;
use Illuminate\Auth\GenericUser;
use Recca0120\LaravelParallel\Tests\Fixtures\User;
use Throwable;

class ParallelRequestTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        ParallelRequest::setBinary(__DIR__.'/Fixtures/artisan');
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_return_test_response(): void
    {
        $request = ParallelRequest::create()->from('/foo');

        $response = wait($request->get('/'));

        $response->assertOk()->assertSee('Hello World');
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_return_previous_url(): void
    {
        $from = '/foo';
        $request = ParallelRequest::create()->from($from);

        $response = wait($request->get('/previous_url'));

        $response->assertOk()->assertSee($from);
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_has_db_connection_in_server_variables(): void
    {
        $request = ParallelRequest::create()->withServerVariables(['CUSTOM' => 'custom']);

        $response = wait($request->getJson('/server_variables'));

        $response->assertOk()->assertJson([
            'DB_CONNECTION' => 'testbench',
            'CUSTOM' => 'custom',
        ]);
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_return_test_response_with_json_response(): void
    {
        $request = ParallelRequest::create();

        $response = wait($request->json('GET', '/'));

        $response->assertOk()->assertJson(['content' => 'Hello World']);
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_get_json_ten_times(): void
    {
        $batch = ParallelRequest::create()->times(10);

        $responses = [];
        foreach (wait(all($batch->json('GET', '/'))) as $response) {
            $responses[] = $response->assertSee('Hello World');
        }

        self::assertCount(10, $responses);
    }

    /**
     * @dataProvider httpStatusCodeProvider
     * @param int $code
     * @throws Throwable
     */
    public function test_it_should_assert_http_status_code(int $code): void
    {
        $response = wait(ParallelRequest::create()->get('/status_code/'.$code));

        $response->assertStatus($code);
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_show_echo_in_console(): void
    {
        $this->expectOutputRegex('/echo foo/');

        $response = wait(ParallelRequest::create()->get('/echo'));

        $response->assertSee('bar');
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_show_dump_in_console(): void
    {
        $this->expectOutputRegex('/dump\(foo\)/');

        $response = wait(ParallelRequest::create()->get('/dump'));

        $response->assertSee('bar');
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_show_dd_in_console(): void
    {
        $this->expectOutputRegex('/dd\(foo\)/');

        wait(ParallelRequest::create()->get('/dd'));
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_show_generic_user_info(): void
    {
        $user = new GenericUser(['email' => 'recca0120@gmail.com']);
        $request = ParallelRequest::create()->actingAs($user);

        $response = wait($request->postJson('/user'));

        $response->assertJsonPath('email', 'recca0120@gmail.com');
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_show_eloquent_user_info(): void
    {
        $request = ParallelRequest::create()->actingAs(User::first(), 'api');

        $response = wait($request->postJson('/api/user'));

        $response->assertJsonPath('email', 'recca0120@gmail.com');
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_login_and_get_user_info(): void
    {
        $request = ParallelRequest::create();

        $response = wait($request->post('/auth/login', [
            'email' => 'recca0120@gmail.com',
            'password' => 'password',
        ]));

        $response->assertJsonPath('email', 'recca0120@gmail.com');
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_get_user_info_with_token(): void
    {
        $token = '6Uv0zov7V2dAk5wWE45HHHhz05gpsmw2';
        $request = ParallelRequest::create()->withToken($token);

        $response = wait($request->post('/api/user'));

        $response->assertJsonPath('email', 'recca0120@gmail.com');
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_get_session_value(): void
    {
        $sessionValue = uniqid('session_', true);
        $request = ParallelRequest::create();
        wait($request->patch('/session?session='.$sessionValue));

        $response = wait($request->getJson('/session'));

        $response->assertJsonPath('session', $sessionValue);
    }

    /**
     * @throws Throwable
     */
    public function test_it_should_finish_10_requests_in_5_seconds(): void
    {
        $startTime = microtime(true);

        foreach (wait(all(ParallelRequest::create()->times(10)->get('/sleep'))) as $response) {
            $response->assertSee('success');
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
