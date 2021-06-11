<?php

namespace Recca0120\ParallelTest\Tests\Console;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Recca0120\ParallelTest\Console\AsyncCallCommand;
use Recca0120\ParallelTest\Tests\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AsyncCallCommandTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @var User
     */
    private $user;

    public function setUp(): void
    {
        parent::setUp();
        $this->user = tap(new User(), function (User $user) {
            $user->forceFill([
                'name' => $this->faker->name,
                'email' => $this->faker->email,
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ])->save();
        });
    }

    public function test_execute_call(): void
    {
        $response = $this->givenResponse([
            'uri' => '/auth/login',
            '--method' => 'post',
            '--server' => json_encode([
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Accept' => 'application/json',
            ]),
            '--content' => json_encode(['email' => $this->user->email, 'password' => 'password']),
            '--call' => true,
        ]);

        self::assertJsonStringEqualsJsonString($this->user->toJson(), (string) $response->getBody());
    }

    /**
     * @dataProvider hasBodyProvider
     */
    public function test_execute_uri_with_body($method): void
    {
        $response = $this->givenResponse([
            'uri' => '/auth/login',
            '--method' => $method,
            '--data' => json_encode(['email' => $this->user->email, 'password' => 'password']),
        ]);

        self::assertJsonStringEqualsJsonString($this->user->toJson(), (string) $response->getBody());
    }

    public function hasBodyProvider(): array
    {
        return [
            ['postJson'],
            ['post'],
            ['put'],
            ['putJson'],
            ['patch'],
            ['patchJson'],
            ['options'],
            ['optionsJson'],
            ['delete'],
            ['deleteJson'],
        ];
    }

    /**
     * @param array $arguments
     * @return Response
     */
    private function givenResponse(array $arguments = []): Response
    {
        $application = new Application();
        $application->add(new AsyncCallCommand($this->app));
        $command = $application->find('async:call');
        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments);
        $output = $commandTester->getDisplay();

        return Message::parseResponse($output);
    }
}
