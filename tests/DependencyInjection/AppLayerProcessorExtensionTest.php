<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\DependencyInjection;

use Elrise\Bundle\AppLayerBundle\DependencyInjection\AppLayerProcessorExtension;
use Elrise\Bundle\AppLayerBundle\Dispatcher\DtoQueueDispatcherInterface;
use Elrise\Bundle\AppLayerBundle\Dispatcher\MessengerQueueDispatcher;
use Elrise\Bundle\AppLayerBundle\Dispatcher\NullQueueDispatcher;
use Elrise\Bundle\AppLayerBundle\Handler\DtoRequestHandler;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\Messenger\MessageBusInterface;

final class AppLayerProcessorExtensionTest extends TestCase
{
    public function testLoadAliasesNullQueueDispatcherWhenMessageBusIsAbsent(): void
    {
        $container = new ContainerBuilder();

        (new AppLayerProcessorExtension())->load([], $container);

        $this->assertTrue($container->has(DtoQueueDispatcherInterface::class));
        $this->assertFalse($container->hasDefinition(DtoQueueDispatcherInterface::class));
        $this->assertSame(NullQueueDispatcher::class, (string) $container->getAlias(DtoQueueDispatcherInterface::class));
    }

    public function testLoadRegistersMessengerQueueDispatcherDefinitionWhenMessageBusIsPresent(): void
    {
        if (!interface_exists(MessageBusInterface::class)) {
            $this->markTestSkipped('symfony/messenger is not installed.');
        }

        $container = new ContainerBuilder();
        $container->setDefinition('messenger.default_bus', new Definition(MessageBusInterface::class));

        (new AppLayerProcessorExtension())->load([], $container);

        $this->assertTrue($container->hasDefinition(DtoQueueDispatcherInterface::class));
        $this->assertFalse($container->hasAlias(DtoQueueDispatcherInterface::class));
        $this->assertSame(MessengerQueueDispatcher::class, $container->getDefinition(DtoQueueDispatcherInterface::class)->getClass());
    }

    public function testMessengerDefinitionIsAutowiredAndAutoconfigured(): void
    {
        if (!interface_exists(MessageBusInterface::class)) {
            $this->markTestSkipped('symfony/messenger is not installed.');
        }

        $container = new ContainerBuilder();
        $container->setDefinition('messenger.default_bus', new Definition(MessageBusInterface::class));

        (new AppLayerProcessorExtension())->load([], $container);

        $definition = $container->getDefinition(DtoQueueDispatcherInterface::class);

        $this->assertTrue($definition->isAutowired());
        $this->assertTrue($definition->isAutoconfigured());
    }

    public function testLoadRegistersYamlConfiguredServices(): void
    {
        $container = new ContainerBuilder();

        (new AppLayerProcessorExtension())->load([], $container);

        $this->assertTrue($container->hasParameter('app_layer.default_deserialize_group'));
        $this->assertTrue($container->hasDefinition(DtoRequestHandler::class));
        $this->assertTrue($container->hasDefinition(NullQueueDispatcher::class));
        $this->assertTrue($container->hasDefinition(MessengerQueueDispatcher::class));
    }
}
