<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Symfony\DependencyInjection;

use ProductDescriptionAI\Core\Config\ClientConfig;
use ProductDescriptionAI\Core\DeepSeekClient;
use ProductDescriptionAI\Core\Factory\ServiceFactory;
use ProductDescriptionAI\Core\ProductDescriptionService;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

final class DeepSeekExtension extends Extension
{
    public function getAlias(): string
    {
        return 'deepseek';
    }

    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $container->setParameter('product_description_ai.cache_enabled', $config['cache']['enabled']);
        $container->setParameter('product_description_ai.cache_ttl', $config['cache']['ttl']);

        $container
            ->setDefinition('product_description_ai.client_config', new Definition(ClientConfig::class))
            ->setArguments([
                $config['api_key'],
                $config['endpoint'],
                $config['model'],
                $config['timeout_seconds'],
                $config['max_retries'],
            ]);

        $factoryClass = \Nyholm\Psr7\Factory\Psr17Factory::class;

        $container
            ->setDefinition('product_description_ai.request_factory', new Definition($factoryClass));

        $container
            ->setDefinition('product_description_ai.stream_factory', new Definition($factoryClass));

        $container
            ->setDefinition('product_description_ai.client', new Definition(DeepSeekClient::class))
            ->setFactory([ServiceFactory::class, 'createClient'])
            ->setArguments([
                new Reference('product_description_ai.client_config'),
                new Reference('http_client'),
                new Reference('product_description_ai.request_factory'),
                new Reference('product_description_ai.stream_factory'),
            ]);

        $serviceDef = new Definition(ProductDescriptionService::class);
        $serviceDef->setFactory([ServiceFactory::class, 'createService']);
        $serviceDef->setArguments([
            new Reference('product_description_ai.client_config'),
            new Reference('http_client'),
            new Reference('product_description_ai.request_factory'),
            new Reference('product_description_ai.stream_factory'),
            new Reference('cache.app', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            new Reference('logger', ContainerBuilder::NULL_ON_INVALID_REFERENCE),
            '%product_description_ai.cache_enabled%',
            '%product_description_ai.cache_ttl%',
        ]);
        $serviceDef->setPublic(true);

        $container->setDefinition('product_description_ai.service', $serviceDef);
        $container->setAlias(ProductDescriptionService::class, 'product_description_ai.service')->setPublic(true);
    }
}
