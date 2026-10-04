<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Support;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

final class MockResponse implements ResponseInterface
{
    private StreamInterface $body;

    /** @param array<string, string> $headers */
    public function __construct(
        private readonly int $statusCode,
        string $body,
        private readonly array $headers = [],
    ) {
        $this->body = new MockStream($body);
    }

    public function getProtocolVersion(): string
    {
        return '1.1';
    }

    public function withProtocolVersion($version): static
    {
        return $this;
    }

    public function getHeaders(): array
    {
        $out = [];
        foreach ($this->headers as $name => $value) {
            $out[$name] = [$value];
        }

        return $out;
    }

    public function hasHeader($name): bool
    {
        return isset($this->headers[\strtolower((string) $name)]);
    }

    public function getHeader($name): array
    {
        $key = \strtolower((string) $name);

        return isset($this->headers[$key]) ? [$this->headers[$key]] : [];
    }

    public function getHeaderLine($name): string
    {
        return $this->headers[\strtolower((string) $name)] ?? '';
    }

    public function withHeader($name, $value): static
    {
        return $this;
    }

    public function withAddedHeader($name, $value): static
    {
        return $this;
    }

    public function withoutHeader($name): static
    {
        return $this;
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): static
    {
        return $this;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): static
    {
        return $this;
    }

    public function getReasonPhrase(): string
    {
        return '';
    }
}
