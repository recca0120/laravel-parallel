<?php

namespace Recca0120\ParallelTest\Tests\Console;

use GuzzleHttp\Psr7\Message;
use Illuminate\Foundation\Auth\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Recca0120\ParallelTest\Console\RequestAsyncCommand;
use Recca0120\ParallelTest\Tests\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class RequestAsyncCommandTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @dataProvider hasBodyProvider
     */
    public function test_execute_uri_with_body($method): void
    {
        $user = tap(new User(), function (User $user) {
            $user->forceFill([
                'name' => $this->faker->name,
                'email' => $this->faker->email,
                'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
            ])->save();
        });

        $application = new Application();
        $application->add(new RequestAsyncCommand($this->app));
        $command = $application->find('request:async');
        $commandTester = new CommandTester($command);
        $commandTester->execute([
            'uri' => '/auth/login',
            '--method' => $method,
            '--data' => json_encode(['email' => $user->email, 'password' => 'password']),
        ]);
        $output = $commandTester->getDisplay();
        $response = Message::parseResponse($output);

        self::assertJsonStringEqualsJsonString($user->toJson(), (string) $response->getBody());
    }

    public function hasBodyProvider(): array
    {
        return [
            ['post'],
            ['postJson'],
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
}
