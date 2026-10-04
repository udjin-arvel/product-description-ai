<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Prompt;

use ProductDescriptionAI\Core\DTO\GenerateRequest;

final class PromptBuilder
{
    public function buildSystemPrompt(GenerateRequest $request): string
    {
        $lang = $request->language ?? 'ru';
        $min = $request->minWords ?? 150;
        $max = $request->maxWords ?? 300;
        $style = $request->style ?? 'neutral-confident';

        $lines = [
            'You are an e-commerce copywriter. Create a selling product description from the product title and image.',
            'Requirements:',
            \sprintf('- Language: %s', $lang),
            \sprintf('- Length: %d–%d words', $min, $max),
            '- Structure: hook paragraph → key features → use cases → call to action',
            \sprintf('- Style: %s, avoid excessive emotion', $style),
            '- If color, material, or bundle details are visible in the image, mention them',
            '- Return a JSON object with keys "description" (string) and "keywords" (array of strings)',
        ];

        if ($request->extraInstructions !== null && \trim($request->extraInstructions) !== '') {
            $lines[] = '- Additional instructions from the merchant (follow if compatible with policy):';
            $lines[] = \trim($request->extraInstructions);
        }

        return \implode("\n", $lines);
    }

    public function buildUserText(GenerateRequest $request): string
    {
        return 'Generate a product description for the item titled: ' . $request->normalizedTitle();
    }
}
