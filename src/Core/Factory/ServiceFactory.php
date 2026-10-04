<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Factory;

use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Core\DeepSeekClient;
use ProductDescriptionAI\Core\Payload\PayloadBuilder;
use ProductDescriptionAI\Core\ProductDescriptionService;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

final class ServiceFactory
{
    public static function createClient(
        ClientConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
    ): DeepSeekClient {
        return new DeepSeekClient($config, $httpClient, $requestFactory, $streamFactory);
    }

    public static function createService(
        ClientConfig $config,
        ClientInterface $httpClient,
        RequestFactoryInterface $requestFactory,
        StreamFactoryInterface $streamFactory,
        ?CacheInterface $cache = null,
        ?LoggerInterface $logger = null,
        bool $cacheEnabled = true,
        int $cacheTtlSeconds = ProductDescriptionService::DEFAULT_CACHE_TTL,
    ): ProductDescriptionService {
        $client = self::createClient($config, $httpClient, $requestFactory, $streamFactory);

        return new ProductDescriptionService(
            client: $client,
            config: $config,
            payloadBuilder: new PayloadBuilder(model: $config->model),
            cache: $cache,
            logger: $logger ?? new NullLogger(),
            cacheTtlSeconds: $cacheTtlSeconds,
            cacheEnabled: $cacheEnabled,
        );
    }
}
