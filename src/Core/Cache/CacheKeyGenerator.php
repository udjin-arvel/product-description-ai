<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Cache;

use ProductDescriptionAI\Core\DTO\GenerateRequest;

final class CacheKeyGenerator
{
    public function generate(GenerateRequest $request, string $model, string $promptFragment = ''): string
    {
        $parts = [
            'pda:v1',
            $model,
            \hash('sha256', $request->normalizedTitle()),
            $request->image->cacheKeyFragment(),
            (string) ($request->language ?? ''),
            (string) ($request->minWords ?? ''),
            (string) ($request->maxWords ?? ''),
            (string) ($request->style ?? ''),
            (string) $request->temperature,
            \hash('sha256', (string) ($request->extraInstructions ?? '')),
            $promptFragment,
        ];

        return 'product_description_ai:' . \hash('sha256', \implode('|', $parts));
    }
}
