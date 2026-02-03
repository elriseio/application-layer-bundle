<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\DependencyInjection;

use Elrise\Bundle\AppLayerBundle\DependencyInjection\Compiler\DataProcessorPass;
use Elrise\Bundle\AppLayerBundle\Dispatcher\DtoQueueDispatcherInterface;
use Elrise\Bundle\AppLayerBundle\Dispatcher\MessengerQueueDispatcher;
use Elrise\Bundle\AppLayerBundle\Dispatcher\NullQueueDispatcher;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Messenger\MessageBusInterface;

class AppLayerProcessorExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $container->addCompilerPass(new DataProcessorPass());

        if (class_exists(MessageBusInterface::class)) {
            $definition = new Definition(MessengerQueueDispatcher::class);
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);
            $container->setDefinition(DtoQueueDispatcherInterface::class, $definition);
        } else {
            $container->setAlias(
                DtoQueueDispatcherInterface::class,
                NullQueueDispatcher::class,
            );
        }

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config'),
        );

        $loader->load('services.yaml');
    }
}
