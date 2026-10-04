<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Response;

use ProductDescriptionAI\Core\DTO\GenerateResponse;
use ProductDescriptionAI\Core\DTO\Usage;
use ProductDescriptionAI\Core\Exception\MalformedResponseException;

final class ResponseParser
{
    /**
     * @param array<string, mixed> $decoded
     */
    public function parse(array $decoded, ?string $model = null): GenerateResponse
    {
        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!\is_string($content) || $content === '') {
            throw new MalformedResponseException('Missing message content in API response.');
        }

        $parsed = \json_decode($content, true);
        if (!\is_array($parsed)) {
            throw new MalformedResponseException('Expected JSON object in model content.');
        }

        $description = $parsed['description'] ?? null;
        if (!\is_string($description) || \trim($description) === '') {
            throw new MalformedResponseException('Missing or empty "description" in JSON response.');
        }

        $keywords = [];
        if (isset($parsed['keywords']) && \is_array($parsed['keywords'])) {
            foreach ($parsed['keywords'] as $kw) {
                if (\is_string($kw) && $kw !== '') {
                    $keywords[] = $kw;
                }
            }
        }

        $usage = null;
        if (isset($decoded['usage']) && \is_array($decoded['usage'])) {
            $usage = Usage::fromApiArray($decoded['usage']);
        }

        return new GenerateResponse(
            description: $description,
            keywords: $keywords,
            usage: $usage,
            rawContent: $content,
            model: $model ?? (\is_string($decoded['model'] ?? null) ? $decoded['model'] : null),
        );
    }
}
