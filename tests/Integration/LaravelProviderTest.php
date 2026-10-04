<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Integration;

use Illuminate\Config\Repository;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\ProductDescriptionService;
use ProductDescriptionAI\Laravel\DeepSeekServiceProvider;
use ProductDescriptionAI\Tests\Support\MockHttpClient;
use Psr\Http\Client\ClientInterface;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final class LaravelProviderTest extends TestCase
{
    public function testProviderRegistersService(): void
    {
        if (!\class_exists(Application::class)) {
            self::markTestSkipped('Laravel not installed.');
        }

        $app = new Application(\dirname(__DIR__, 2));

        $app->instance('config', new Repository([
            'deepseek' => [
                'api_key' => 'test-key',
                'endpoint' => 'https://api.deepseek.com/chat/completions',
                'model' => 'deepseek-flash',
                'timeout_seconds' => 60,
                'max_retries' => 3,
                'cache' => ['enabled' => false, 'ttl' => 3600],
            ],
        ]));

        $app->instance(ClientInterface::class, new MockHttpClient());
        $factory = new Psr17Factory();
        $app->instance(RequestFactoryInterface::class, $factory);
        $app->instance(StreamFactoryInterface::class, $factory);

        $provider = new DeepSeekServiceProvider($app);
        $provider->register();

        self::assertTrue($app->bound(ProductDescriptionService::class));
        self::assertInstanceOf(ProductDescriptionService::class, $app->make(ProductDescriptionService::class));
    }
}
