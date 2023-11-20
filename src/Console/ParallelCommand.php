<?php

namespace Recca0120\LaravelParallel\Console;

use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;
use Illuminate\Support\Facades\Auth;
use Recca0120\LaravelParallel\ResponseIdentifier;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\Response;

class ParallelCommand extends Command
{
    use MakesHttpRequests;
    use SerializesAndRestoresModelIdentifiers;

    public const COMMAND_NAME = 'parallel:request';

    /**
     * @var string
     */
    protected static $defaultName = self::COMMAND_NAME;

    /**
     * @var Application
     */
    protected $app;

    public function __construct(Application $app = null)
    {
        parent::__construct();
        $this->setLaravel($app);
    }

    /**
     * @return $this
     */
    public function setLaravel(Application $app = null): self
    {
        $this->app = $app;

        return $this;
    }

    protected function configure(): void
    {
        $this->addArgument('uri', InputArgument::REQUIRED);
        $this->addOption('method', null, InputOption::VALUE_OPTIONAL, '', 'GET');
        $this->addOption('headers', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('data', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('parameters', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('cookies', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('files', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('server', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('content', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('withoutMiddleware', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('withMiddleware', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('withUnencryptedCookies', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('serverVariables', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('followRedirects', null, InputOption::VALUE_REQUIRED);
        $this->addOption('withCredentials', null, InputOption::VALUE_REQUIRED);
        $this->addOption('disableCookieEncryption', null, InputOption::VALUE_REQUIRED);
        $this->addOption('user', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('guard', null, InputOption::VALUE_OPTIONAL);
        $this->addOption('testNow', null, InputOption::VALUE_OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $response = $this->makeRequest($input);
        } catch (Exception $e) {
            $this->app['log']->debug($e);
            $response = new Response($e->getMessage(), 500);
        }
        $output->write((string) ResponseIdentifier::fromSymfonyResponse($response));

        return $response->isSuccessful() ? 0 : 1;
    }

    private function makeRequest(InputInterface $input): Response
    {
        $this->handleWithoutMiddleware($input)
            ->handleWithMiddleware($input)
            ->handleWithUnencryptedCookies($input)
            ->handleServerVariables($input)
            ->handleFollowRedirects($input)
            ->handleWithCredentials($input)
            ->handleAuthenticatable($input)
            ->handleTestNow($input);

        return $this->makeTestResponse($input)->baseResponse;
    }

    private function handleAuthenticatable(InputInterface $input): self
    {
        $serialized = $input->getOption('user');

        if (empty($serialized)) {
            return $this;
        }

        $user = $this->getRestoredPropertyValue(
            unserialize(base64_decode($serialized), [Authenticatable::class, ModelIdentifier::class])
        );

        Auth::guard($input->getOption('guard'))->setUser($user);

        return $this;
    }

    private function handleWithCredentials(InputInterface $input): self
    {
        if ($input->getOption('withCredentials')) {
            $this->withCredentials();
        }

        return $this;
    }

    private function handleFollowRedirects(InputInterface $input): self
    {
        if ($input->getOption('followRedirects')) {
            $this->followingRedirects();
        }

        return $this;
    }

    private function handleServerVariables(InputInterface $input): self
    {
        if ($input->getOption('serverVariables')) {
            $this->withServerVariables(self::getArrayFromOption($input, 'serverVariables'));
        }

        return $this;
    }

    private static function getArrayFromOption(InputInterface $input, string $name): array
    {
        return json_decode($input->getOption($name), true) ?: [];
    }

    private function handleWithUnencryptedCookies(InputInterface $input): self
    {
        $values = self::getArrayFromOption($input, 'withUnencryptedCookies');
        if (! empty($values)) {
            foreach ($values as $value) {
                $this->withUnencryptedCookies($value);
            }
        }

        return $this;
    }

    private function handleWithMiddleware(InputInterface $input): self
    {
        $values = self::getArrayFromOption($input, 'withMiddleware');
        if (! empty($values)) {
            foreach ($values as $value) {
                $this->withMiddleware(...$value);
            }
        }

        return $this;
    }

    private function handleWithoutMiddleware(InputInterface $input): self
    {
        $values = self::getArrayFromOption($input, 'withoutMiddleware');
        if (! empty($values)) {
            foreach ($values as $value) {
                $this->withoutMiddleware(...$value);
            }
        }

        return $this;
    }

    private function handleTestNow(InputInterface $input): self
    {
        $testNow = $input->getOption('testNow');

        if (! empty($testNow)) {
            Carbon::setTestNow($testNow);
        }

        return $this;
    }

    /**
     * @return \Illuminate\Testing\TestResponse
     */
    private function makeTestResponse(InputInterface $input)
    {
        $method = strtolower($input->getOption('method') ?: 'get');
        $uri = $input->getArgument('uri');

        if ($input->getOption('parameters') !== null) {
            $parameters = self::getArrayFromOption($input, 'parameters');
            $cookies = self::getArrayFromOption($input, 'cookies');
            $files = self::getArrayFromOption($input, 'files');
            $server = $this->handleFrom(self::getArrayFromOption($input, 'server'));
            $content = $input->getOption('content');

            return $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
        }

        $method = $method !== 'json' ? str_replace('json', 'Json', $method) : $method;
        $headers = $this->handleFrom(self::getArrayFromOption($input, 'headers'));

        return in_array($method, ['get', 'getJson', 'json'], true)
            ? $this->{$method}($uri, $headers)
            : $this->{$method}($uri, self::getArrayFromOption($input, 'data'), $headers);
    }

    private function handleFrom(array $headers): array
    {
        if (array_key_exists('HTTP_REFERER', $headers)) {
            $this->from($headers['HTTP_REFERER']);
        }

        return $headers;
    }
}
