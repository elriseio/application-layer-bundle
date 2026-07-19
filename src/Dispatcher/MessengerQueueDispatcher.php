<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Dispatcher;

use Elrise\Bundle\AppLayerBundle\Exception\RequestException;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
final class MessengerQueueDispatcher implements DtoQueueDispatcherInterface
{
    private ?MessageBusInterface $messageBus;

    public function __construct(MessageBusInterface $messageBus)
    {
        $this->messageBus = $messageBus;
    }

    public function dispatch(object $dto): void
    {
        if ($this->messageBus === null) {
            throw new RequestException(
                sprintf(
                    '%s has no message bus wired. Ensure the messenger.default_bus service is configured in your application.',
                    self::class,
                ),
                ['dispatcher' => self::class],
            );
        }

        $this->messageBus->dispatch($dto);
    }
}