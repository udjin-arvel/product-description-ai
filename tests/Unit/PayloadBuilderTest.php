<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Image\Base64Image;
use ProductDescriptionAI\Core\Image\UrlImage;
use ProductDescriptionAI\Core\Payload\PayloadBuilder;

final class PayloadBuilderTest extends TestCase
{
    public function testBuildsBase64Payload(): void
    {
        $builder = new PayloadBuilder(model: 'deepseek-flash');
        $request = new GenerateRequest(
            title: 'Test product',
            image: new Base64Image(\base64_encode('img'), 'png'),
        );

        $payload = $builder->build($request);

        self::assertSame('deepseek-flash', $payload['model']);
        self::assertSame('json_object', $payload['response_format']['type']);
        self::assertSame('disabled', $payload['thinking']['type']);
        $userContent = $payload['messages'][1]['content'];
        self::assertSame('text', $userContent[0]['type']);
        self::assertStringContainsString('Test product', $userContent[0]['text']);
        self::assertStringStartsWith('data:image/png;base64,', $userContent[1]['image_url']['url']);
    }

    public function testBuildsUrlPayload(): void
    {
        $builder = new PayloadBuilder(model: 'deepseek-flash');
        $request = new GenerateRequest(
            title: 'URL product',
            image: new UrlImage('https://cdn.example.com/p.jpg'),
        );

        $payload = $builder->build($request);
        $userContent = $payload['messages'][1]['content'];

        self::assertSame('https://cdn.example.com/p.jpg', $userContent[1]['image_url']['url']);
    }
}
