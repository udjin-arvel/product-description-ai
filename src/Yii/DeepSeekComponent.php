<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Yii;

use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\DTO\GenerateResponse;
use ProductDescriptionAI\Core\Factory\ServiceFactory;
use ProductDescriptionAI\Core\ProductDescriptionService;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use yii\base\Component;
use yii\base\InvalidConfigException;

final class DeepSeekComponent extends Component
{
    public string $apiKey = '';

    public string $endpoint = ClientConfig::DEFAULT_ENDPOINT;

    public string $model = ClientConfig::DEFAULT_MODEL;

    public int $timeoutSeconds = ClientConfig::DEFAULT_TIMEOUT_SECONDS;

    public int $maxRetries = ClientConfig::DEFAULT_MAX_RETRIES;

    public bool $cacheEnabled = true;

    public int $cacheTtl = ProductDescriptionService::DEFAULT_CACHE_TTL;

    public ?string $systemPrompt = null;

    public ?string $userPrompt = null;

    public ?ClientInterface $httpClient = null;

    public ?RequestFactoryInterface $requestFactory = null;

    public ?StreamFactoryInterface $streamFactory = null;

    public ?CacheInterface $cache = null;

    public ?LoggerInterface $logger = null;

    private ?ProductDescriptionService $service = null;

    public function init(): void
    {
        parent::init();

        if ($this->httpClient === null || $this->requestFactory === null || $this->streamFactory === null) {
            throw new InvalidConfigException(
                'httpClient, requestFactory, and streamFactory must be configured for DeepSeekComponent.',
            );
        }

        $config = new ClientConfig(
            apiKey: $this->apiKey,
            endpoint: $this->endpoint,
            model: $this->model,
            timeoutSeconds: $this->timeoutSeconds,
            maxRetries: $this->maxRetries,
        );

        $this->service = ServiceFactory::createService(
            $config,
            $this->httpClient,
            $this->requestFactory,
            $this->streamFactory,
            $this->cache,
            $this->logger,
            $this->cacheEnabled,
            $this->cacheTtl,
            $this->systemPrompt,
            $this->userPrompt,
        );
    }

    public function generate(GenerateRequest $request): GenerateResponse
    {
        if ($this->service === null) {
            throw new InvalidConfigException('DeepSeekComponent is not initialized.');
        }

        return $this->service->generate($request);
    }
}
