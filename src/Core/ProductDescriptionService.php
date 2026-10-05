<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core;

use ProductDescriptionAI\Core\Cache\CacheKeyGenerator;
use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\DTO\GenerateResponse;
use ProductDescriptionAI\Core\Payload\PayloadBuilder;
use ProductDescriptionAI\Core\Response\ResponseParser;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;

final class ProductDescriptionService
{
    public const DEFAULT_CACHE_TTL = 86_400;

    public function __construct(
        private readonly DeepSeekClient $client,
        private readonly ClientConfig $config,
        private readonly PayloadBuilder $payloadBuilder,
        private readonly ResponseParser $responseParser = new ResponseParser(),
        private readonly CacheKeyGenerator $cacheKeyGenerator = new CacheKeyGenerator(),
        private readonly ?CacheInterface $cache = null,
        private readonly LoggerInterface $logger = new NullLogger(),
        private readonly int $cacheTtlSeconds = self::DEFAULT_CACHE_TTL,
        private readonly bool $cacheEnabled = true,
    ) {
    }

    public function generate(GenerateRequest $request): GenerateResponse
    {
        $cacheKey = $this->cacheKeyGenerator->generate(
            $request,
            $this->config->model,
            $this->payloadBuilder->cacheFragment($request),
        );

        if ($this->cacheEnabled && $this->cache !== null) {
            $cached = $this->cache->get($cacheKey);
            if (\is_array($cached) && isset($cached['description']) && \is_string($cached['description'])) {
                $this->logger->info('product_description_ai.cache_hit', [
                    'cache_key' => $cacheKey,
                    'model' => $this->config->model,
                ]);

                return $this->responseFromCachedArray($cached);
            }
        }

        $payload = $this->payloadBuilder->build($request);
        $started = \microtime(true);

        $decoded = $this->client->chat($payload);
        $response = $this->responseParser->parse($decoded, $this->config->model);

        $latencyMs = (int) \round((\microtime(true) - $started) * 1000);

        $this->logger->info('product_description_ai.generated', [
            'model' => $response->model ?? $this->config->model,
            'latency_ms' => $latencyMs,
            'prompt_tokens' => $response->usage?->promptTokens,
            'completion_tokens' => $response->usage?->completionTokens,
            'total_tokens' => $response->usage?->totalTokens,
            'request_id' => \is_string($decoded['id'] ?? null) ? $decoded['id'] : null,
        ]);

        if ($this->cacheEnabled && $this->cache !== null) {
            $this->cache->set($cacheKey, $response->toArray(), $this->cacheTtlSeconds);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $cached
     */
    private function responseFromCachedArray(array $cached): GenerateResponse
    {
        $keywords = [];
        if (isset($cached['keywords']) && \is_array($cached['keywords'])) {
            foreach ($cached['keywords'] as $kw) {
                if (\is_string($kw)) {
                    $keywords[] = $kw;
                }
            }
        }

        return new GenerateResponse(
            description: (string) $cached['description'],
            keywords: $keywords,
            usage: null,
            model: \is_string($cached['model'] ?? null) ? $cached['model'] : null,
        );
    }
}
