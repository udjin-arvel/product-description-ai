<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\DTO;

final class Usage
{
    public function __construct(
        public readonly int $promptTokens = 0,
        public readonly int $completionTokens = 0,
        public readonly int $totalTokens = 0,
    ) {
    }

    /**
     * @param array<string, mixed> $usage
     */
    public static function fromApiArray(array $usage): self
    {
        return new self(
            promptTokens: (int) ($usage['prompt_tokens'] ?? 0),
            completionTokens: (int) ($usage['completion_tokens'] ?? 0),
            totalTokens: (int) ($usage['total_tokens'] ?? 0),
        );
    }
}
