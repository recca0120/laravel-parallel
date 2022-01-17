<?php

namespace Recca0120\LaravelParallel\Console;

use Exception;
use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\ModelIdentifier;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Queue\SerializesAndRestoresModelIdentifiers;
use Illuminate\Support\Facades\Auth;
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

    /**
     * @param Application|null $app
     */
    public function __construct(Application $app = null)
    {
        parent::__construct();
        $this->setLaravel($app);
    }

    /**
     * @param Application|null $app
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
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $response = $this->makeRequest($input);
        } catch (Exception $e) {
            $response = new Response($e->getMessage(), 500);
        }
        $output->write($this->toMessage($response));

        return $response->isSuccessful() ? 0 : 1;
    }

    /**
     * @param InputInterface $input
     * @return Response
     */
    private function makeRequest(InputInterface $input): Response
    {
        $this->handleWithoutMiddleware($input)
            ->handleWithMiddleware($input)
            ->handleWithUnencryptedCookies($input)
            ->handleServerVariables($input)
            ->handleFollowRedirects($input)
            ->handleWithCredentials($input)
            ->handleAuthenticatable($input);

        return $this->makeTestResponse($input)->baseResponse;
    }

    /**
     * @param InputInterface $input
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

    /**
     * @param Response $response
     * @return string
     */
    private function toMessage(Response $response): string
    {
        return Message::toString(new Psr7Response(
            $response->getStatusCode(),
            $response->headers->all(),
            $response->getContent()
        ));
    }

    /**
     * @param InputInterface $input
     * @param string $name
     * @return array
     */
    private static function getArrayFromOption(InputInterface $input, string $name): array
    {
        return json_decode($input->getOption($name), true) ?: [];
    }

    /**
     * @param InputInterface $input
     * @return ParallelCommand
     */
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

    /**
     * @param InputInterface $input
     * @return ParallelCommand
     */
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

    /**
     * @param InputInterface $input
     * @return ParallelCommand
     */
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

    /**
     * @param InputInterface $input
     * @return ParallelCommand
     */
    private function handleServerVariables(InputInterface $input): self
    {
        if ($input->getOption('serverVariables')) {
            $this->withServerVariables(self::getArrayFromOption($input, 'serverVariables'));
        }

        return $this;
    }

    /**
     * @param InputInterface $input
     * @return ParallelCommand
     */
    private function handleFollowRedirects(InputInterface $input): self
    {
        if ($input->getOption('followRedirects')) {
            $this->followingRedirects();
        }

        return $this;
    }

    /**
     * @param InputInterface $input
     * @return ParallelCommand
     */
    private function handleWithCredentials(InputInterface $input): self
    {
        if ($input->getOption('withCredentials')) {
            $this->withCredentials();
        }

        return $this;
    }

    /**
     * @param array $headers
     * @return array
     */
    private function handleFrom(array $headers): array
    {
        if (array_key_exists('HTTP_REFERER', $headers)) {
            $this->from($headers['HTTP_REFERER']);
        }

        return $headers;
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
}
