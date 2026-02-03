<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Dispatcher;

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @internal
 */
final readonly class MessengerQueueDispatcher implements DtoQueueDispatcherInterface
{
    public function __construct(
        private ?MessageBusInterface $messageBus = null,
    ) {
    }

    public function dispatch(object $dto): void
    {
        if ($this->messageBus) {
            $this->messageBus->dispatch($dto);
        }
    }
}
