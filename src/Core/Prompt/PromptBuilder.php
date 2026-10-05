<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Prompt;

use ProductDescriptionAI\Core\DTO\GenerateRequest;

final class PromptBuilder
{
    public function __construct(
        private readonly ?string $systemPrompt = null,
        private readonly ?string $userPrompt = null,
    ) {
    }

    public function buildSystemPrompt(GenerateRequest $request): string
    {
        $template = self::normalizeTemplate($this->systemPrompt);
        if ($template === null) {
            return $this->buildDefaultSystemPrompt($request);
        }

        return $this->renderSystemTemplate($template, $request);
    }

    public function buildUserText(GenerateRequest $request): string
    {
        $template = self::normalizeTemplate($this->userPrompt);
        if ($template === null) {
            $template = 'Generate a product description for the item titled: {title}';
        }

        return \strtr($template, [
            '{title}' => $request->normalizedTitle(),
        ]);
    }

    private function buildDefaultSystemPrompt(GenerateRequest $request): string
    {
        $lang = $request->language ?? 'ru';
        $style = $request->style ?? 'neutral-confident';

        $lines = [
            'You are an e-commerce copywriter. Create a selling product description from the product title and image.',
            'Requirements:',
            \sprintf('- Language: %s', $lang),
        ];

        $length = $this->lengthLine($request);
        if ($length !== '') {
            $lines[] = $length;
        }

        $lines[] = '- Structure: hook paragraph → key features → use cases → call to action';
        $lines[] = \sprintf('- Style: %s, avoid excessive emotion', $style);
        $lines[] = '- If color, material, or bundle details are visible in the image, mention them';
        $lines[] = '- Return a JSON object with keys "description" (string) and "keywords" (array of strings)';

        $extra = $this->extraBlock($request);
        if ($extra !== '') {
            $lines[] = $extra;
        }

        return \implode("\n", $lines);
    }

    private function renderSystemTemplate(string $template, GenerateRequest $request): string
    {
        $rendered = \strtr($template, [
            '{language}' => $request->language ?? 'ru',
            '{style}' => $request->style ?? 'neutral-confident',
            '{title}' => $request->normalizedTitle(),
            '{min_words}' => $request->minWords === null ? '' : (string) $request->minWords,
            '{max_words}' => $request->maxWords === null ? '' : (string) $request->maxWords,
            '{length}' => $this->lengthLine($request),
            '{extra_instructions}' => $this->extraText($request),
        ]);

        if (!\str_contains($template, '{length}')) {
            $length = $this->lengthLine($request);
            if ($length !== '') {
                $rendered = \rtrim($rendered) . "\n" . $length;
            }
        }

        if (!\str_contains($template, '{extra_instructions}')) {
            $extra = $this->extraBlock($request);
            if ($extra !== '') {
                $rendered = \rtrim($rendered) . "\n" . $extra;
            }
        }

        return $rendered;
    }

    private function lengthLine(GenerateRequest $request): string
    {
        $min = $request->minWords;
        $max = $request->maxWords;

        if ($min !== null && $max !== null) {
            return \sprintf('- Length: %d–%d words', $min, $max);
        }

        if ($min !== null) {
            return \sprintf('- Length: at least %d words', $min);
        }

        if ($max !== null) {
            return \sprintf('- Length: at most %d words', $max);
        }

        return '';
    }

    private function extraText(GenerateRequest $request): string
    {
        if ($request->extraInstructions === null) {
            return '';
        }

        return \trim($request->extraInstructions);
    }

    private function extraBlock(GenerateRequest $request): string
    {
        $extra = $this->extraText($request);
        if ($extra === '') {
            return '';
        }

        return "- Additional instructions from the merchant (follow if compatible with policy):\n" . $extra;
    }

    private static function normalizeTemplate(?string $template): ?string
    {
        if ($template === null) {
            return null;
        }

        $template = \trim($template);

        return $template === '' ? null : $template;
    }
}
