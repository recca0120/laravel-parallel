<?php

namespace Recca0120\AsyncTesting;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class AsyncResponse
{
    /**
     * @var string
     */
    private $message;

    /**
     * AsyncResponse constructor.
     * @param string $message
     */
    public function __construct(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return \Illuminate\Testing\TestResponse
     */
    public function toTestResponse()
    {
        $class = class_exists(\Illuminate\Testing\TestResponse::class)
            ? \Illuminate\Testing\TestResponse::class
            : \Illuminate\Foundation\Testing\TestResponse::class;

        return new $class($this->createBaseResponse());
    }

    /**
     * @param string $message
     * @return AsyncResponse
     */
    public static function create(string $message): self
    {
        return new self($message);
    }

    /**
     * @return Psr7Response
     */
    private function toPsr7Response(): Psr7Response
    {
        return Message::parseResponse($this->message);
    }

    /**
     * @return JsonResponse|Response
     */
    private function createBaseResponse()
    {
        $response = $this->toPsr7Response();
        $headers = $response->getHeaders();
        $statusCode = $response->getStatusCode();
        $content = (string) $response->getBody();

        if (self::isJson($response)) {
            $data = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return new JsonResponse($data, $statusCode, $headers);
            }
        }

        return new Response($content, $statusCode, $headers);
    }

    /**
     * @param Psr7Response $response
     * @return bool
     */
    private static function isJson(Psr7Response $response): bool
    {
        return $response->hasHeader('content-type') && strpos($response->getHeader('content-type')[0], 'json') !== false;
    }
}
