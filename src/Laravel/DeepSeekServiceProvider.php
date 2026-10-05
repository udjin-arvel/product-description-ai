<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Laravel;

use Illuminate\Support\ServiceProvider;
use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Laravel\Cache\LaravelCacheAdapter;
use ProductDescriptionAI\Laravel\Http\GuzzleDiscovery;
use ProductDescriptionAI\Core\DeepSeekClient;
use ProductDescriptionAI\Core\Factory\ServiceFactory;
use ProductDescriptionAI\Core\ProductDescriptionService;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;

final class DeepSeekServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        GuzzleDiscovery::registerBindings($this->app);

        $this->mergeConfigFrom(__DIR__ . '/../../config/deepseek.php', 'deepseek');

        $this->app->singleton(ClientConfig::class, function ($app) {
            $cfg = $app['config']->get('deepseek');

            return new ClientConfig(
                apiKey: (string) ($cfg['api_key'] ?? ''),
                endpoint: (string) ($cfg['endpoint'] ?? ClientConfig::DEFAULT_ENDPOINT),
                model: (string) ($cfg['model'] ?? ClientConfig::DEFAULT_MODEL),
                timeoutSeconds: (int) ($cfg['timeout_seconds'] ?? ClientConfig::DEFAULT_TIMEOUT_SECONDS),
                maxRetries: (int) ($cfg['max_retries'] ?? ClientConfig::DEFAULT_MAX_RETRIES),
            );
        });

        $this->app->singleton(DeepSeekClient::class, function ($app) {
            return ServiceFactory::createClient(
                $app->make(ClientConfig::class),
                $app->make(ClientInterface::class),
                $app->make(RequestFactoryInterface::class),
                $app->make(StreamFactoryInterface::class),
            );
        });

        $this->app->singleton(ProductDescriptionService::class, function ($app) {
            $cfg = $app['config']->get('deepseek');
            $cacheConfig = $cfg['cache'] ?? [];

            return ServiceFactory::createService(
                $app->make(ClientConfig::class),
                $app->make(ClientInterface::class),
                $app->make(RequestFactoryInterface::class),
                $app->make(StreamFactoryInterface::class),
                $app->bound('cache.store')
                    ? new LaravelCacheAdapter($app->make('cache.store'))
                    : null,
                $app->bound(LoggerInterface::class) ? $app->make(LoggerInterface::class) : null,
                (bool) ($cacheConfig['enabled'] ?? true),
                (int) ($cacheConfig['ttl'] ?? ProductDescriptionService::DEFAULT_CACHE_TTL),
                self::promptOrNull($cfg['system_prompt'] ?? null),
                self::promptOrNull($cfg['user_prompt'] ?? null),
            );
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/deepseek.php' => \config_path('deepseek.php'),
            ], 'deepseek-config');
        }
    }

    private static function promptOrNull(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = \trim($value);

        return $value === '' ? null : $value;
    }
}
