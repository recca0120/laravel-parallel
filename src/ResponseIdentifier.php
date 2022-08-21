<?php

namespace Recca0120\LaravelParallel;

use Illuminate\Http\Response;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class ResponseIdentifier
{
    private const HEADER_REGEX = "(^([^()<>@,;:\\\"/[\]?={}\x01-\x20\x7F]++):[ \t]*+((?:[ \t]*+[\x21-\x7E\x80-\xFF]++)*+)[ \t]*+\r?\n)m";
    private const HEADER_FOLD_REGEX = "(\r?\n[ \t]++)";

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
        $data = self::parseMessage(PreventEcho::prevent($message));
        // According to https://tools.ietf.org/html/rfc7230#section-3.1.2 the space
        // between status-code and reason-phrase is required. But browsers accept
        // responses without space and reason as well.
        if (! preg_match('/^HTTP\/.* [0-9]{3}( .*|$)/', $data['start-line'])) {
            throw new InvalidArgumentException('Invalid response string: '.$data['start-line']);
        }
        $parts = explode(' ', $data['start-line'], 3);

        return new self($data['body'], (int) $parts[1], $data['headers']);
    }

    /**
     * Parses an HTTP message into an associative array.
     *
     * The array contains the "start-line" key containing the start line of
     * the message, "headers" key containing an associative array of header
     * array values, and a "body" key containing the body of the message.
     *
     * @link    https://github.com/guzzle/psr7/blob/2.4.0/src/Message.php#L114-L167
     *
     * @license https://github.com/guzzle/psr7/blob/2.4.0/LICENSE
     *
     * @param string $message HTTP request or response to parse.
     */
    public static function parseMessage(string $message): array
    {
        if (! $message) {
            throw new InvalidArgumentException('Invalid message');
        }

        $message = ltrim($message, "\r\n");

        $messageParts = preg_split("/\r?\n\r?\n/", $message, 2);

        if ($messageParts === false || count($messageParts) !== 2) {
            throw new InvalidArgumentException('Invalid message: Missing header delimiter');
        }

        [$rawHeaders, $body] = $messageParts;
        $rawHeaders .= "\r\n"; // Put back the delimiter we split previously
        $headerParts = preg_split("/\r?\n/", $rawHeaders, 2);

        if ($headerParts === false || count($headerParts) !== 2) {
            throw new InvalidArgumentException('Invalid message: Missing status line');
        }

        [$startLine, $rawHeaders] = $headerParts;

        if (preg_match("/(?:^HTTP\/|^[A-Z]+ \S+ HTTP\/)(\d+(?:\.\d+)?)/i", $startLine, $matches) && $matches[1] === '1.0') {
            // Header folding is deprecated for HTTP/1.1, but allowed in HTTP/1.0
            $rawHeaders = preg_replace(self::HEADER_FOLD_REGEX, ' ', $rawHeaders);
        }

        /** @var array[] $headerLines */
        $count = preg_match_all(self::HEADER_REGEX, $rawHeaders, $headerLines, PREG_SET_ORDER);

        // If these aren't the same, then one line didn't match and there's an invalid header.
        if ($count !== substr_count($rawHeaders, "\n")) {
            // Folding is deprecated, see https://tools.ietf.org/html/rfc7230#section-3.2.4
            if (preg_match(self::HEADER_FOLD_REGEX, $rawHeaders)) {
                throw new InvalidArgumentException('Invalid header syntax: Obsolete line folding');
            }

            throw new InvalidArgumentException('Invalid header syntax');
        }

        $headers = [];

        foreach ($headerLines as $headerLine) {
            $headers[$headerLine[1]][] = $headerLine[2];
        }

        return [
            'start-line' => $startLine,
            'headers' => $headers,
            'body' => $body,
        ];
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
