<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Image\UrlImage;
use ProductDescriptionAI\Tests\Support\MockHttpClient;
use ProductDescriptionAI\Yii\DeepSeekComponent;
use Nyholm\Psr7\Factory\Psr17Factory;

final class YiiComponentTest extends TestCase
{
    public function testComponentGeneratesDescription(): void
    {
        if (!\class_exists(DeepSeekComponent::class)) {
            self::markTestSkipped('Yii not installed.');
        }

        $http = new MockHttpClient();
        $http->enqueue(200, \json_encode([
            'choices' => [[
                'message' => ['content' => '{"description":"Yii description","keywords":[]}'],
            ]],
        ], JSON_THROW_ON_ERROR));

        $factory = new Psr17Factory();
        $component = new DeepSeekComponent([
            'apiKey' => 'key',
            'httpClient' => $http,
            'requestFactory' => $factory,
            'streamFactory' => $factory,
            'cacheEnabled' => false,
        ]);
        $component->init();

        $response = $component->generate(new GenerateRequest('Chair', new UrlImage('https://example.com/chair.jpg')));

        self::assertSame('Yii description', $response->description);
    }
}
