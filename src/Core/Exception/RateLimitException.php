<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Exception;

final class RateLimitException extends DeepSeekException
{
    public function __construct(
        string $message,
        public readonly ?int $retryAfterSeconds = null,
        ?int $statusCode = 429,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }
}
