<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core;

use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Core\Exception\AuthenticationException;
use ProductDescriptionAI\Core\Exception\DeepSeekException;
use ProductDescriptionAI\Core\Exception\InvalidRequestException;
use ProductDescriptionAI\Core\Exception\MalformedResponseException;
use ProductDescriptionAI\Core\Exception\RateLimitException;
use ProductDescriptionAI\Core\Exception\ServerException;
use ProductDescriptionAI\Core\Exception\TransportException;
use ProductDescriptionAI\Core\Retry\NativeSleeper;
use ProductDescriptionAI\Core\Retry\SleeperInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class DeepSeekClient
{
    public function __construct(
        private readonly ClientConfig $config,
        private readonly ClientInterface $httpClient,
        private readonly RequestFactoryInterface $requestFactory,
        private readonly StreamFactoryInterface $streamFactory,
        private readonly SleeperInterface $sleeper = new NativeSleeper(),
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     */
    public function chat(array $payload): array
    {
        $body = \json_encode($payload, JSON_THROW_ON_ERROR);
        $attempt = 0;
        $maxAttempts = $this->config->maxRetries;

        while ($attempt < $maxAttempts) {
            ++$attempt;

            try {
                $request = $this->requestFactory
                    ->createRequest('POST', $this->config->endpoint)
                    ->withHeader('Authorization', 'Bearer ' . $this->config->apiKey)
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream($body));

                $response = $this->httpClient->sendRequest($request);
                $status = $response->getStatusCode();
                $responseBody = (string) $response->getBody();

                if ($status >= 200 && $status < 300) {
                    $decoded = \json_decode($responseBody, true);
                    if (!\is_array($decoded)) {
                        throw new MalformedResponseException('API returned non-JSON response body.');
                    }

                    return $decoded;
                }

                $this->throwForStatus($status, $responseBody, $response->getHeaderLine('Retry-After'));
            } catch (DeepSeekException $e) {
                if (!$this->shouldRetry($e, $attempt, $maxAttempts)) {
                    throw $e;
                }

                $delayMicros = $this->computeBackoffMicroseconds($attempt, $e);
                $this->sleeper->sleepMicroseconds($delayMicros);
            } catch (ClientExceptionInterface $e) {
                if ($attempt >= $maxAttempts) {
                    throw new TransportException(
                        'HTTP transport error while calling DeepSeek API.',
                        null,
                        $e,
                    );
                }

                $this->sleeper->sleepMicroseconds($this->computeBackoffMicroseconds($attempt));
            }
        }

        throw new TransportException('DeepSeek API request failed after retries.');
    }

    private function throwForStatus(int $status, string $body, string $retryAfterHeader): void
    {
        $message = $this->safeErrorMessage($body);

        if ($status === 401 || $status === 403) {
            throw new AuthenticationException($message, $status);
        }

        if ($status === 429) {
            $retryAfter = $this->parseRetryAfter($retryAfterHeader);

            throw new RateLimitException($message, $retryAfter, $status);
        }

        if ($status === 408) {
            throw new ServerException($message, $status);
        }

        if ($status >= 400 && $status < 500) {
            throw new InvalidRequestException($message, $status);
        }

        if ($status >= 500) {
            throw new ServerException($message, $status);
        }

        throw new InvalidRequestException($message, $status);
    }

    private function shouldRetry(DeepSeekException $e, int $attempt, int $maxAttempts): bool
    {
        if ($attempt >= $maxAttempts) {
            return false;
        }

        return $e instanceof TransportException
            || $e instanceof ServerException
            || $e instanceof RateLimitException;
    }

    private function computeBackoffMicroseconds(int $attempt, ?DeepSeekException $e = null): int
    {
        if ($e instanceof RateLimitException && $e->retryAfterSeconds !== null && $e->retryAfterSeconds > 0) {
            return $e->retryAfterSeconds * 1_000_000;
        }

        $base = 500_000 * (2 ** ($attempt - 1));
        $jitter = \random_int(0, 100_000);

        return (int) \min($base + $jitter, 8_000_000);
    }

    private function parseRetryAfter(string $header): ?int
    {
        if ($header === '') {
            return null;
        }

        if (\ctype_digit($header)) {
            return (int) $header;
        }

        $ts = \strtotime($header);
        if ($ts === false) {
            return null;
        }

        return \max(0, $ts - \time());
    }

    private function safeErrorMessage(string $body): string
    {
        $decoded = \json_decode($body, true);
        if (\is_array($decoded) && isset($decoded['error']['message']) && \is_string($decoded['error']['message'])) {
            $msg = $decoded['error']['message'];

            return $this->redactSecrets($msg);
        }

        if (\strlen($body) > 500) {
            $body = \substr($body, 0, 500) . '…';
        }

        return $this->redactSecrets($body !== '' ? $body : 'DeepSeek API request failed.');
    }

    private function redactSecrets(string $message): string
    {
        $redacted = \preg_replace('/Bearer\s+\S+/i', 'Bearer [REDACTED]', $message) ?? $message;
        $redacted = \preg_replace('/data:image\/[^;]+;base64,[A-Za-z0-9+\/=]+/', 'data:image/…;base64,[REDACTED]', $redacted) ?? $redacted;

        return $redacted;
    }
}
