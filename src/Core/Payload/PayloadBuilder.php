<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Payload;

use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Image\Base64Image;
use ProductDescriptionAI\Core\Image\UrlImage;
use ProductDescriptionAI\Core\Prompt\PromptBuilder;

final class PayloadBuilder
{
    public function __construct(
        private readonly PromptBuilder $promptBuilder = new PromptBuilder(),
        private readonly string $model = 'deepseek-flash',
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function build(GenerateRequest $request): array
    {
        $imageBlock = $this->buildImageBlock($request);

        return [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->promptBuilder->buildSystemPrompt($request),
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => $this->promptBuilder->buildUserText($request),
                        ],
                        $imageBlock,
                    ],
                ],
            ],
            'temperature' => $request->temperature,
            'max_tokens' => $request->maxTokens,
            'response_format' => ['type' => 'json_object'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildImageBlock(GenerateRequest $request): array
    {
        $image = $request->image;

        if ($image instanceof Base64Image) {
            $block = [
                'type' => 'image_url',
                'image_url' => [
                    'url' => \sprintf(
                        'data:image/%s;base64,%s',
                        $image->normalizedFormat(),
                        $image->data,
                    ),
                ],
            ];
            if ($image->detail !== null) {
                $block['image_url']['detail'] = $image->detail;
            }

            return $block;
        }

        if ($image instanceof UrlImage) {
            $block = [
                'type' => 'image_url',
                'image_url' => ['url' => $image->url],
            ];
            if ($image->detail !== null) {
                $block['image_url']['detail'] = $image->detail;
            }

            return $block;
        }

        throw new \InvalidArgumentException('Unsupported image source type.');
    }
}
