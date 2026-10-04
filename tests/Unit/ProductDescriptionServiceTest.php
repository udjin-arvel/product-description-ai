<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Unit;

use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Core\DeepSeekClient;
use ProductDescriptionAI\Core\DTO\GenerateRequest;
use ProductDescriptionAI\Core\Image\Base64Image;
use ProductDescriptionAI\Core\Payload\PayloadBuilder;
use ProductDescriptionAI\Core\ProductDescriptionService;
use ProductDescriptionAI\Tests\Support\ArrayCache;
use ProductDescriptionAI\Tests\Support\FakeSleeper;
use ProductDescriptionAI\Tests\Support\MockHttpClient;
use Nyholm\Psr7\Factory\Psr17Factory;

final class ProductDescriptionServiceTest extends TestCase
{
    public function testGenerateUsesCacheOnSecondCall(): void
    {
        $http = new MockHttpClient();
        $http->enqueue(200, \json_encode([
            'id' => 'req_1',
            'model' => 'deepseek-flash',
            'choices' => [[
                'message' => ['content' => '{"description":"Cached candidate","keywords":["k1"]}'],
            ]],
            'usage' => ['prompt_tokens' => 1, 'completion_tokens' => 2, 'total_tokens' => 3],
        ], JSON_THROW_ON_ERROR));

        $config = new ClientConfig('secret-key');
        $factory = new Psr17Factory();
        $client = new DeepSeekClient($config, $http, $factory, $factory, new FakeSleeper());
        $cache = new ArrayCache();

        $service = new ProductDescriptionService(
            client: $client,
            config: $config,
            payloadBuilder: new PayloadBuilder(model: $config->model),
            cache: $cache,
        );

        $request = new GenerateRequest('Phone case', new Base64Image(\base64_encode('img'), 'jpeg'));

        $first = $service->generate($request);
        $second = $service->generate($request);

        self::assertSame('Cached candidate', $first->description);
        self::assertSame('Cached candidate', $second->description);
        self::assertCount(1, $http->requests);
    }
}
