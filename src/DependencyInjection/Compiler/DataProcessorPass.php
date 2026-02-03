<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

class DataProcessorPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $taggedServices = $container->findTaggedServiceIds('app_layer.data_processor');
        $services = [];

        foreach ($taggedServices as $id => $tags) {
            $services[$id] = new Reference($id);
        }

        $container->register('app_layer.data_processor.locator', ServiceLocator::class)
            ->addArgument(new ServiceLocatorArgument($services))
            ->addTag('container.service_locator');
    }
}
