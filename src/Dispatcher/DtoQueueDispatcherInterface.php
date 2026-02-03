<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Dispatcher;

interface DtoQueueDispatcherInterface
{
    public function dispatch(object $dto): void;
}
