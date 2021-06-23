<?php

namespace Recca0120\AsyncTesting\Concerns;

use GuzzleHttp\Psr7\Message;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

trait MakesIlluminateResponses
{
    /**
     * @param string $message
     * @return \Illuminate\Testing\TestResponse|\Illuminate\Foundation\Testing\TestResponse
     */
    public function toTestResponse(string $message)
    {
        $class = class_exists(\Illuminate\Testing\TestResponse::class)
            ? \Illuminate\Testing\TestResponse::class
            : \Illuminate\Foundation\Testing\TestResponse::class;

        return new $class(
            $this->createBaseResponse(Message::parseResponse($message))
        );
    }

    /**
     * @param Psr7Response $response
     * @return JsonResponse|Response
     */
    private function createBaseResponse(Psr7Response $response)
    {
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
