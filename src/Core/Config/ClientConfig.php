<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Config;

use ProductDescriptionAI\Core\Exception\ConfigurationException;

final class ClientConfig
{
    public const DEFAULT_ENDPOINT = 'https://api.deepseek.com/chat/completions';

    /** @see https://api-docs.deepseek.com/guides/vision */
    public const DEFAULT_MODEL = 'deepseek-flash';

    public const DEFAULT_TIMEOUT_SECONDS = 60;

    public const DEFAULT_MAX_RETRIES = 3;

    public function __construct(
        public readonly string $apiKey,
        public readonly string $endpoint = self::DEFAULT_ENDPOINT,
        public readonly string $model = self::DEFAULT_MODEL,
        public readonly int $timeoutSeconds = self::DEFAULT_TIMEOUT_SECONDS,
        public readonly int $maxRetries = self::DEFAULT_MAX_RETRIES,
    ) {
        if ($this->apiKey === '') {
            throw new ConfigurationException('API key must not be empty.');
        }

        if ($this->endpoint === '') {
            throw new ConfigurationException('Endpoint must not be empty.');
        }

        if ($this->model === '') {
            throw new ConfigurationException('Model must not be empty.');
        }

        if ($this->timeoutSeconds < 1) {
            throw new ConfigurationException('Timeout must be at least 1 second.');
        }

        if ($this->maxRetries < 1) {
            throw new ConfigurationException('Max retries must be at least 1.');
        }
    }
}
