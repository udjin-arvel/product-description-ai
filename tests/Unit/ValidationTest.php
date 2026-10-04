<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Exception\ValidationException;
use ProductDescriptionAI\Core\Image\Base64Image;
use ProductDescriptionAI\Core\Image\UrlImage;

final class ValidationTest extends TestCase
{
    public function testValidBase64Image(): void
    {
        $img = new Base64Image(\base64_encode('fake-image'), 'jpeg');
        self::assertSame('base64', $img->type());
    }

    public function testInvalidBase64Throws(): void
    {
        $this->expectException(ValidationException::class);
        new Base64Image('not-base64!!!', 'jpeg');
    }

    public function testUrlTooLongThrows(): void
    {
        $this->expectException(ValidationException::class);
        new UrlImage('https://example.com/' . \str_repeat('a', 8200));
    }

    public function testTitleLengthValidation(): void
    {
        $this->expectException(ValidationException::class);
        new GenerateRequest('', new Base64Image(\base64_encode('x'), 'png'));
    }
}
