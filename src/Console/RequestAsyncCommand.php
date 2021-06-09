<?php

namespace Recca0120\ParallelTest\Console;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\MakesHttpRequests;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RequestAsyncCommand extends Command
{
    use MakesHttpRequests;

    /**
     * @var string
     */
    protected static $defaultName = 'request:async';
    /**
     * @var Application
     */
    protected $app;
    /**
     * @var string[]
     */
    private $methodLookup = [
        'GET' => 'get',
        'GETJSON' => 'getJson',
        'POST' => 'post',
        'POSTJSON' => 'postJson',
        'PUT' => 'put',
        'PUTJSON' => 'putJson',
        'PATCH' => 'patch',
        'PATCHJSON' => 'patchJson',
        'DELETE' => 'delete',
        'DELETEJSON' => 'deleteJson',
        'OPTIONS' => 'options',
        'OPTIONSJSON' => 'optionsJson',
        'JSON' => 'json',
    ];

    /**
     * LaravelCommand constructor.
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
        $method = Arr::get($this->methodLookup, strtoupper($input->getOption('method') ?: 'GET'));
        $uri = $input->getArgument('uri');
        $headers = self::getArrayFromOption($input, 'headers');
        $data = self::getArrayFromOption($input, 'data');

        $hasBody = ['post', 'postJson', 'put', 'putJson', 'patch', 'patchJson', 'delete', 'deleteJson', 'options', 'optionsJson'];

        if (in_array($method, $hasBody, true)) {
            return $this->{$method}($uri, $data, $headers);
        }

        if ($method === 'call') {
            $parameters = self::getArrayFromOption($input, 'parameters');
            $cookies = self::getArrayFromOption($input, 'cookies');
            $files = self::getArrayFromOption($input, 'files');
            $server = self::getArrayFromOption($input, 'server');
            $content = $input->getOption('content');

            return $this->call($method, $uri, $parameters, $cookies, $files, $server, $content);
        }

        return $this->{$method}($uri, $headers);
    }

    /**
     * @param TestResponse $testResponse
     * @return string
     */
    private function toMessage(TestResponse $testResponse): string
    {
        return Message::toString(new Response(
            $testResponse->getStatusCode(),
            $testResponse->headers->all(),
            $testResponse->getContent()
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
