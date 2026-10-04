<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Tests\Integration;

use PHPUnit\Framework\TestCase;
use ProductDescriptionAI\Core\ProductDescriptionService;
use ProductDescriptionAI\Symfony\DependencyInjection\Configuration;
use ProductDescriptionAI\Symfony\DependencyInjection\DeepSeekExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class SymfonyExtensionTest extends TestCase
{
    public function testExtensionRegistersService(): void
    {
        if (!\class_exists(ContainerBuilder::class)) {
            self::markTestSkipped('Symfony DI not installed.');
        }

        $container = new ContainerBuilder();
        $container->register('http_client', \ProductDescriptionAI\Tests\Support\MockHttpClient::class);

        $extension = new DeepSeekExtension();
        $extension->load([[
            'api_key' => 'test',
            'endpoint' => 'https://api.deepseek.com/chat/completions',
            'model' => 'deepseek-flash',
            'timeout_seconds' => 60,
            'max_retries' => 3,
            'cache' => ['enabled' => false, 'ttl' => 3600],
        ]], $container);

        self::assertTrue($container->has(ProductDescriptionService::class));
        self::assertInstanceOf(ProductDescriptionService::class, $container->get(ProductDescriptionService::class));
    }

    public function testConfigurationTree(): void
    {
        $config = new Configuration();
        self::assertSame('deepseek', $config->getConfigTreeBuilder()->buildTree()->getName());
    }
}
