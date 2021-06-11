<?php

namespace Recca0120\ParallelTest\Console;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AsyncCallCommand extends Command
{
    use MakesHttpRequests;

    /**
     * @var string
     */
    protected static $defaultName = 'async:call';
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
        $this->addOption('headers', null, InputOption::VALUE_OPTIONAL, '', '[]');
        $this->addOption('data', null, InputOption::VALUE_OPTIONAL, '', '[]');
        $this->addOption('parameters', null, InputOption::VALUE_OPTIONAL, '', '[]');
        $this->addOption('cookies', null, InputOption::VALUE_OPTIONAL, '', '[]');
        $this->addOption('files', null, InputOption::VALUE_OPTIONAL, '', '[]');
        $this->addOption('server', null, InputOption::VALUE_OPTIONAL, '', '[]');
        $this->addOption('content', null, InputOption::VALUE_OPTIONAL, '', '');
        $this->addOption('call', null, InputOption::VALUE_REQUIRED);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $response = $this->getTestResponse($input);
        $output->write($this->toMessage($response));

        return $response->isOk() ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * @param InputInterface $input
     * @return TestResponse
     */
    private function getTestResponse(InputInterface $input): TestResponse
    {
        $method = strtolower($input->getOption('method') ?: 'get');
        $uri = $input->getArgument('uri');

        if ($input->getOption('call')) {
            $parameters = self::getArrayFromOption($input, 'parameters');
            $cookies = self::getArrayFromOption($input, 'cookies');
            $files = self::getArrayFromOption($input, 'files');
            $server = self::getArrayFromOption($input, 'server');
            $content = $input->getOption('content');

            return $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
        }

        $method = $method !== 'json' ? str_replace('json', 'Json', $method) : $method;
        $headers = self::getArrayFromOption($input, 'headers');

        return in_array($method, ['get', 'getJson', 'json'], true)
            ? $this->{$method}($uri, $headers)
            : $this->{$method}($uri, self::getArrayFromOption($input, 'data'), $headers);
    }

    /**
     * @param TestResponse $response
     * @return string
     */
    private function toMessage(TestResponse $response): string
    {
        return Message::toString(new Response(
            $response->getStatusCode(), $response->headers->all(), $response->getContent()
        ));
    }

    /**
     * @param InputInterface $input
     * @param string $name
     * @return array
     */
    private static function getArrayFromOption(InputInterface $input, string $name): array
    {
        return json_decode($input->getOption($name), true);
    }
}
