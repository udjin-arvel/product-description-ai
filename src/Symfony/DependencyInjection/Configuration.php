<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Symfony\DependencyInjection;

use ProductDescriptionAI\Core\Config\ClientConfig;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('deepseek');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('api_key')->defaultValue('')->end()
                ->scalarNode('endpoint')->defaultValue(ClientConfig::DEFAULT_ENDPOINT)->end()
                ->scalarNode('model')->defaultValue(ClientConfig::DEFAULT_MODEL)->end()
                ->integerNode('timeout_seconds')->defaultValue(ClientConfig::DEFAULT_TIMEOUT_SECONDS)->min(1)->end()
                ->integerNode('max_retries')->defaultValue(ClientConfig::DEFAULT_MAX_RETRIES)->min(1)->end()
                ->arrayNode('cache')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->booleanNode('enabled')->defaultTrue()->end()
                        ->integerNode('ttl')->defaultValue(86_400)->min(1)->end()
                    ->end()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
