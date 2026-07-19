<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\Dispatcher;

use Elrise\Bundle\AppLayerBundle\Dispatcher\MessengerQueueDispatcher;
use Elrise\Bundle\AppLayerBundle\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class MessengerQueueDispatcherTest extends TestCase
{
    public function testDispatchPassesThroughToMessageBus(): void
    {
        if (!interface_exists(MessageBusInterface::class)) {
            $this->markTestSkipped('symfony/messenger is not installed.');
        }

        $dto = new stdClass();
        $envelope = new Envelope($dto);

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->expects($this->once())
            ->method('dispatch')
            ->with($dto)
            ->willReturn($envelope);

        $dispatcher = new MessengerQueueDispatcher($bus);

        $dispatcher->dispatch($dto);
    }

    public function testDispatchThrowsRequestExceptionWhenBusIsNull(): void
    {
        if (!interface_exists(MessageBusInterface::class)) {
            $this->markTestSkipped('symfony/messenger is not installed.');
        }

        $bus = $this->createStub(MessageBusInterface::class);

        $dispatcher = new MessengerQueueDispatcher($bus);

        $property = new \ReflectionProperty(MessengerQueueDispatcher::class, 'messageBus');
        $property->setValue($dispatcher, null);

        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('has no message bus wired. Ensure the messenger.default_bus service is configured in your application.');

        $dispatcher->dispatch(new stdClass());
    }
}