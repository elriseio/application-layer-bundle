<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\Dispatcher;

use Elrise\Bundle\AppLayerBundle\Dispatcher\DtoQueueDispatcherInterface;
use Elrise\Bundle\AppLayerBundle\Dispatcher\NullQueueDispatcher;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

final class NullQueueDispatcherTest extends TestCase
{
    public function testDispatchAcceptsAnyObjectWithoutSideEffect(): void
    {
        $dispatcher = new NullQueueDispatcher();

        $dispatcher->dispatch(new stdClass());
        $dispatcher->dispatch(new class {});

        $this->expectNotToPerformAssertions();
    }

    public function testImplementsDtoQueueDispatcherInterface(): void
    {
        $this->assertInstanceOf(DtoQueueDispatcherInterface::class, new NullQueueDispatcher());
    }

    public function testIsFinalReadonlyClass(): void
    {
        $reflection = new ReflectionClass(NullQueueDispatcher::class);

        $this->assertTrue($reflection->isFinal());
        $this->assertTrue($reflection->isReadOnly());
    }
}
