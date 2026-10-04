<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Symfony;

use ProductDescriptionAI\Symfony\DependencyInjection\DeepSeekExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class DeepSeekBundle extends Bundle
{
    public function getContainerExtension(): ExtensionInterface
    {
        return new DeepSeekExtension();
    }

    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        if ($container->hasExtension('framework')) {
            $container->prependExtensionConfig('framework', [
                'http_client' => ['enabled' => true],
            ]);
        }
    }
}
