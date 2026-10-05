<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\DTO;

use ProductDescriptionAI\Core\Exception\ValidationException;
use ProductDescriptionAI\Core\Image\ImageSourceInterface;

final class GenerateRequest
{
    public const MIN_TITLE_LENGTH = 1;

    public const MAX_TITLE_LENGTH = 500;

    public const MAX_WORD_COUNT = 5000;

    public function __construct(
        public readonly string $title,
        public readonly ImageSourceInterface $image,
        public readonly ?string $language = 'ru',
        public readonly ?string $style = null,
        public readonly ?int $minWords = null,
        public readonly ?int $maxWords = null,
        public readonly ?string $extraInstructions = null,
        public readonly float $temperature = 0.7,
        public readonly int $maxTokens = 2048,
    ) {
        $len = \mb_strlen(\trim($this->title));
        if ($len < self::MIN_TITLE_LENGTH || $len > self::MAX_TITLE_LENGTH) {
            throw new ValidationException(\sprintf(
                'Длина названия должна быть от %d до %d символов.',
                self::MIN_TITLE_LENGTH,
                self::MAX_TITLE_LENGTH,
            ));
        }

        if ($this->temperature < 0.0 || $this->temperature > 2.0) {
            throw new ValidationException('Температура должна быть от 0 до 2.');
        }

        if ($this->maxTokens < 1 || $this->maxTokens > 8192) {
            throw new ValidationException('maxTokens должен быть от 1 до 8192.');
        }

        $this->assertWordCount($this->minWords, 'minWords');
        $this->assertWordCount($this->maxWords, 'maxWords');

        if ($this->minWords !== null && $this->maxWords !== null && $this->minWords > $this->maxWords) {
            throw new ValidationException('minWords не может превышать maxWords.');
        }
    }

    public function normalizedTitle(): string
    {
        return \trim($this->title);
    }

    private function assertWordCount(?int $value, string $name): void
    {
        if ($value === null) {
            return;
        }

        if ($value < 1 || $value > self::MAX_WORD_COUNT) {
            throw new ValidationException(\sprintf(
                '%s должен быть от 1 до %d.',
                $name,
                self::MAX_WORD_COUNT,
            ));
        }
    }
}
