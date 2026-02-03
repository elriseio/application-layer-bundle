<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Dispatcher;

final class NullQueueDispatcher implements DtoQueueDispatcherInterface
{
    public function dispatch(object $dto): void
    {
    }
}
