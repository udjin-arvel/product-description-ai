<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\DTO;

final class GenerateResponse
{
    /**
     * @param list<string> $keywords
     */
    public function __construct(
        public readonly string $description,
        public readonly array $keywords = [],
        public readonly ?Usage $usage = null,
        public readonly ?string $rawContent = null,
        public readonly ?string $model = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'description' => $this->description,
            'keywords' => $this->keywords,
            'usage' => $this->usage !== null ? [
                'prompt_tokens' => $this->usage->promptTokens,
                'completion_tokens' => $this->usage->completionTokens,
                'total_tokens' => $this->usage->totalTokens,
            ] : null,
            'model' => $this->model,
        ];
    }
}
