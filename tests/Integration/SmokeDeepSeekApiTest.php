<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Factory\ServiceFactory;
use ProductDescriptionAI\Core\Image\UrlImage;
use GuzzleHttp\Client;
use Nyholm\Psr7\Factory\Psr17Factory;

/**
 * Opt-in live API smoke test. Set DEEPSEEK_API_KEY and DEEPSEEK_SMOKE_TEST=1.
 */
final class SmokeDeepSeekApiTest extends TestCase
{
    public function testLiveApiWhenEnabled(): void
    {
        if (\getenv('DEEPSEEK_SMOKE_TEST') !== '1') {
            self::markTestSkipped('Set DEEPSEEK_SMOKE_TEST=1 to run live API smoke test.');
        }

        $apiKey = \getenv('DEEPSEEK_API_KEY') ?: '';
        if ($apiKey === '') {
            self::markTestSkipped('DEEPSEEK_API_KEY is required for smoke test.');
        }

        $config = new ClientConfig(apiKey: $apiKey);
        $factory = new Psr17Factory();
        $service = ServiceFactory::createService(
            $config,
            new Client(['timeout' => 60]),
            $factory,
            $factory,
            cacheEnabled: false,
        );

        $response = $service->generate(new GenerateRequest(
            title: 'Minimalist white mug',
            image: new UrlImage('https://upload.wikimedia.org/wikipedia/commons/thumb/4/45/White_tea_cup.jpg/320px-White_tea_cup.jpg'),
        ));

        self::assertNotSame('', \trim($response->description));
    }
}
