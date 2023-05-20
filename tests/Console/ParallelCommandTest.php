<?php

namespace Recca0120\LaravelParallel\Tests\Console;

use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Recca0120\LaravelParallel\Console\ParallelCommand;
use Recca0120\LaravelParallel\ResponseIdentifier;
use Recca0120\LaravelParallel\Tests\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ParallelCommandTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @var User
     */
    private $user = [
        'email' => 'recca0120@gmail.com',
        'password' => 'password',
    ];

    public function test_it_should_execute_call_method(): void
    {
        $response = $this->givenResponse([
            'uri' => '/auth/login',
            '--method' => 'post',
            '--parameters' => '[]',
            '--server' => json_encode([
                'CONTENT_TYPE' => 'application/json',
                'HTTP_Accept' => 'application/json',
            ]),
            '--content' => json_encode(['email' => $this->user['email'], 'password' => $this->user['password']]),
            '--followRedirects' => false,
        ]);

        $user = json_decode($response->content(), true);
        self::assertEquals('recca0120@gmail.com', $user['email']);
    }

    /**
     * @dataProvider hasBodyProvider
     */
    public function test_it_should_execute_other_methods_with_post_body($method): void
    {
        $response = $this->givenResponse([
            'uri' => '/auth/login',
            '--method' => $method,
            '--data' => json_encode(['email' => $this->user['email'], 'password' => $this->user['password']]),
        ]);

        $user = json_decode($response->content(), true);
        self::assertEquals('recca0120@gmail.com', $user['email']);
    }

    public static function hasBodyProvider(): array
    {
        return array_reduce(['post', 'put', 'patch', 'options', 'delete'], static function ($acc, $method) {
            return array_merge($acc, [[$method], [$method.'Json']]);
        }, []);
    }

    private function givenResponse(array $arguments = []): Response
    {
        $application = new Application();
        $application->add(new ParallelCommand($this->app));
        $command = $application->find(ParallelCommand::COMMAND_NAME);
        $commandTester = new CommandTester($command);
        $commandTester->execute($arguments);
        $output = $commandTester->getDisplay();

        return ResponseIdentifier::fromMessage($output)->toResponse();
    }
}
