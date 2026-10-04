<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Core\DeepSeekClient;
use ProductDescriptionAI\Core\Exception\AuthenticationException;
use ProductDescriptionAI\Core\Exception\RateLimitException;
use ProductDescriptionAI\Tests\Support\FakeSleeper;
use ProductDescriptionAI\Tests\Support\MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;

final class DeepSeekClientRetryTest extends TestCase
{
    public function testRetriesOn429ThenSucceeds(): void
    {
        $http = new MockHttpClient();
        $http->enqueue(429, '{"error":{"message":"rate limit"}}', ['retry-after' => '1']);
        $http->enqueue(200, \json_encode([
            'choices' => [['message' => ['content' => '{"ok":true}']]],
        ], JSON_THROW_ON_ERROR));

        $sleeper = new FakeSleeper();
        $factory = new Psr17Factory();
        $client = new DeepSeekClient(
            new ClientConfig('test-key', maxRetries: 3),
            $http,
            $factory,
            $factory,
            $sleeper,
        );

        $result = $client->chat(['model' => 'x', 'messages' => []]);

        self::assertTrue($result['choices'][0]['message']['content'] !== '');
        self::assertCount(1, $sleeper->sleptMicroseconds);
        self::assertSame(1_000_000, $sleeper->sleptMicroseconds[0]);
    }

    public function testDoesNotRetry401(): void
    {
        $http = new MockHttpClient();
        $http->enqueue(401, '{"error":{"message":"invalid key"}}');

        $factory = new Psr17Factory();
        $client = new DeepSeekClient(
            new ClientConfig('bad-key', maxRetries: 3),
            $http,
            $factory,
            $factory,
            new FakeSleeper(),
        );

        $this->expectException(AuthenticationException::class);
        $client->chat(['model' => 'x', 'messages' => []]);
    }

    public function testRateLimitExceptionWithoutRetryWhenExhausted(): void
    {
        $http = new MockHttpClient();
        $http->enqueue(429, '{}');
        $http->enqueue(429, '{}');

        $factory = new Psr17Factory();
        $client = new DeepSeekClient(
            new ClientConfig('key', maxRetries: 2),
            $http,
            $factory,
            $factory,
            new FakeSleeper(),
        );

        $this->expectException(RateLimitException::class);
        $client->chat(['model' => 'x', 'messages' => []]);
    }
}
