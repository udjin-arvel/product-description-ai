<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Core\Image;

use ProductDescriptionAI\Core\Exception\ValidationException;

final class Base64Image implements ImageSourceInterface
{
    /** @var list<string> */
    public const ALLOWED_MIME = ['jpeg', 'jpg', 'png', 'gif', 'webp'];

    /** Approx max decoded size ~32 MiB (API inline limit). */
    public const MAX_DECODED_BYTES = 33_554_432;

    public function __construct(
        public readonly string $data,
        public readonly string $format = 'jpeg',
        public readonly ?string $detail = 'auto',
    ) {
        if ($this->data === '') {
            throw new ValidationException('Данные изображения в Base64 не должны быть пустыми.');
        }

        $decoded = \base64_decode($this->data, true);
        if ($decoded === false) {
            throw new ValidationException('Некорректные данные изображения в Base64.');
        }

        if (\strlen($decoded) > self::MAX_DECODED_BYTES) {
            throw new ValidationException('Изображение превышает максимально допустимый размер.');
        }

        $normalized = \strtolower($this->format);
        if ($normalized === 'jpg') {
            $normalized = 'jpeg';
        }

        if (!\in_array($normalized, ['jpeg', 'png', 'gif', 'webp'], true)) {
            throw new ValidationException(\sprintf(
                'Неподдерживаемый формат изображения «%s». Допустимые: jpeg, png, gif, webp.',
                $this->format,
            ));
        }

        if ($this->detail !== null && !\in_array($this->detail, ['low', 'high', 'original', 'auto'], true)) {
            throw new ValidationException('Недопустимое значение detail для изображения.');
        }
    }

    public function normalizedFormat(): string
    {
        $f = \strtolower($this->format);

        return $f === 'jpg' ? 'jpeg' : $f;
    }

    public function type(): string
    {
        return 'base64';
    }

    public function cacheKeyFragment(): string
    {
        return 'b64:' . \hash('sha256', $this->data);
    }
}
