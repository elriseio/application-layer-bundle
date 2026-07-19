<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\DependencyInjection;

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
        $this->registerQueueDispatcher($container);

        $loader = new YamlFileLoader(
            $container,
            new FileLocator(__DIR__ . '/../Resources/config'),
        );

        $loader->load('services.yaml');
    }

    private function registerQueueDispatcher(ContainerBuilder $container): void
    {
        if (interface_exists(MessageBusInterface::class) && $container->has('messenger.default_bus')) {
            $definition = new Definition(MessengerQueueDispatcher::class);
            $definition->setAutowired(true);
            $definition->setAutoconfigured(true);
            $container->setDefinition(DtoQueueDispatcherInterface::class, $definition);

            return;
        }

        $container->setAlias(
            DtoQueueDispatcherInterface::class,
            NullQueueDispatcher::class,
        );
    }
}
