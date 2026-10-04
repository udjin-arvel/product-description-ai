<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Image;

use ProductDescriptionAI\Core\Exception\ValidationException;

final class UrlImage implements ImageSourceInterface
{
    public const MAX_URL_LENGTH = 8192;

    public function __construct(
        public readonly string $url,
        public readonly ?string $detail = 'auto',
    ) {
        if ($this->url === '') {
            throw new ValidationException('URL изображения не должен быть пустым.');
        }

        if (\strlen($this->url) > self::MAX_URL_LENGTH) {
            throw new ValidationException('URL изображения превышает максимальную длину (8192).');
        }

        $parsed = \parse_url($this->url);
        if ($parsed === false || !isset($parsed['scheme'], $parsed['host'])) {
            throw new ValidationException('URL изображения должен быть корректным абсолютным URL.');
        }

        if (!\in_array(\strtolower($parsed['scheme']), ['http', 'https'], true)) {
            throw new ValidationException('URL изображения должен использовать схему http или https.');
        }

        if ($this->detail !== null && !\in_array($this->detail, ['low', 'high', 'original', 'auto'], true)) {
            throw new ValidationException('Недопустимое значение detail для изображения.');
        }
    }

    public function type(): string
    {
        return 'url';
    }

    public function cacheKeyFragment(): string
    {
        return 'url:' . \hash('sha256', $this->url);
    }
}
