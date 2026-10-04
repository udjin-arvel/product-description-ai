<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Support;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class MockHttpClient implements ClientInterface
{
    /** @var list<array{status:int, body:string, headers?:array<string,string>}> */
    private array $responses = [];

    /** @var list<RequestInterface> */
    public array $requests = [];

    /**
     * @param array<string, string> $headers
     */
    public function enqueue(int $status, string $body, array $headers = []): void
    {
        $this->responses[] = ['status' => $status, 'body' => $body, 'headers' => $headers];
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->requests[] = $request;

        if ($this->responses === []) {
            throw new \RuntimeException('No mock response queued.');
        }

        $next = \array_shift($this->responses);

        return new MockResponse($next['status'], $next['body'], $next['headers'] ?? []);
    }
}
