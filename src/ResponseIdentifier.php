<?php

namespace Recca0120\LaravelParallel;

use GuzzleHttp\Psr7\Message;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ResponseIdentifier
{
    /**
     * @var string
     */
    public $content;
    /**
     * @var int
     */
    public $status;
    /**
     * @var array
     */
    public $headers = [];

    public function __construct(string $content, int $status, array $headers)
    {
        $this->content = $content;
        $this->status = $status;
        $this->headers = $headers;
    }

    public function __toString(): string
    {
        return $this->toMessage();
    }

    public static function fromSymfonyResponse(SymfonyResponse $response): self
    {
        return new self((string) $response->getContent(), $response->getStatusCode(), $response->headers->all());
    }

    public static function fromMessage(string $message): self
    {
        $response = Message::parseResponse(PreventEcho::prevent($message));

        return new self((string) $response->getBody(), $response->getStatusCode(), $response->getHeaders());
    }

    public function toMessage(): string
    {
        return (string) $this->toResponse();
    }

    public function toResponse(): Response
    {
        return new Response($this->content, $this->status, $this->headers);
    }
}
