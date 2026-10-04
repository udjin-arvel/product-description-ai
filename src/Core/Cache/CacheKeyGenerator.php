<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Cache;

use ProductDescriptionAI\Core\DTO\GenerateRequest;

final class CacheKeyGenerator
{
    public function generate(GenerateRequest $request, string $model): string
    {
        $parts = [
            'pda:v1',
            $model,
            \hash('sha256', $request->normalizedTitle()),
            $request->image->cacheKeyFragment(),
            (string) ($request->language ?? ''),
            (string) ($request->minWords ?? ''),
            (string) ($request->maxWords ?? ''),
            (string) $request->temperature,
            \hash('sha256', (string) ($request->extraInstructions ?? '')),
        ];

        return 'product_description_ai:' . \hash('sha256', \implode('|', $parts));
    }
}
