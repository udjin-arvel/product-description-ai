<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Image\Base64Image;
use ProductDescriptionAI\Core\Payload\PayloadBuilder;
use ProductDescriptionAI\Core\Prompt\PromptBuilder;

final class PromptBuilderTest extends TestCase
{
    public function testDefaultPromptOmitsLengthWhenUnset(): void
    {
        $prompt = (new PromptBuilder())->buildSystemPrompt($this->request());

        self::assertStringNotContainsString('Length:', $prompt);
        self::assertStringContainsString('Language: en', $prompt);
    }

    public function testLengthLineUsesOnlyProvidedBounds(): void
    {
        $builder = new PromptBuilder();

        self::assertStringContainsString(
            '- Length: at least 80 words',
            $builder->buildSystemPrompt($this->request(minWords: 80)),
        );
        self::assertStringContainsString(
            '- Length: at most 120 words',
            $builder->buildSystemPrompt($this->request(maxWords: 120)),
        );
        self::assertStringContainsString(
            '- Length: 80–120 words',
            $builder->buildSystemPrompt($this->request(minWords: 80, maxWords: 120)),
        );
    }

    public function testCustomTemplateReplacesPlaceholders(): void
    {
        $builder = new PromptBuilder(
            systemPrompt: 'Lang {language}. {length}. Extra: {extra_instructions}',
            userPrompt: 'Item {title}',
        );
        $request = $this->request(minWords: 40, maxWords: 60, extra: 'mention glaze');

        self::assertSame(
            'Lang en. - Length: 40–60 words. Extra: mention glaze',
            $builder->buildSystemPrompt($request),
        );
        self::assertSame('Item Ceramic vase', $builder->buildUserText($request));
    }

    public function testCustomTemplateAppendsMissingLength(): void
    {
        $builder = new PromptBuilder(systemPrompt: 'Write a product story.');
        $prompt = $builder->buildSystemPrompt($this->request(maxWords: 90));

        self::assertStringContainsString("Write a product story.\n- Length: at most 90 words", $prompt);
    }

    public function testPayloadUsesOverriddenPrompts(): void
    {
        $payload = (new PayloadBuilder(
            promptBuilder: new PromptBuilder(systemPrompt: 'Base {style}', userPrompt: 'Title {title}'),
            model: 'deepseek-flash',
        ))->build($this->request());

        self::assertSame('Base neutral-confident', $payload['messages'][0]['content']);
        self::assertSame('Title Ceramic vase', $payload['messages'][1]['content'][0]['text']);
    }

    private function request(?int $minWords = null, ?int $maxWords = null, ?string $extra = null): GenerateRequest
    {
        return new GenerateRequest(
            title: 'Ceramic vase',
            image: new Base64Image(\base64_encode('img'), 'jpeg'),
            language: 'en',
            minWords: $minWords,
            maxWords: $maxWords,
            extraInstructions: $extra,
        );
    }
}
