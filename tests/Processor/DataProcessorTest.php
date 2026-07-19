<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\Processor;

use Elrise\Bundle\AppLayerBundle\Contract\DataProcessorInterface;
use Elrise\Bundle\AppLayerBundle\Processor\DataProcessor;
use Exception;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
use Symfony\Component\HttpFoundation\Request;

final class DataProcessorTest extends TestCase
{
    public function testProcessWithValidProcessor(): void
    {
        $request = new Request();
        $processorFqcn = 'Elrise\\Bundle\\AppLayerBundle\\Tests\\Processor\\DummyProcessor';

        $processor = $this->createMock(DataProcessorInterface::class);
        $processor->expects($this->once())
            ->method('process')
            ->with($request)
            ->willReturn('processed');

        $locator = $this->createMock(ContainerInterface::class);
        $locator->expects($this->once())
            ->method('get')
            ->with($processorFqcn)
            ->willReturn($processor);

        $dataProcessor = new DataProcessor($locator);

        $result = $dataProcessor->process($request, $processorFqcn);

        $this->assertEquals('processed', $result);
    }

    public function testProcessThrowsIfClassDoesNotImplementInterface(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $locator = $this->createStub(ContainerInterface::class);
        $dataProcessor = new DataProcessor($locator);

        $dataProcessor->process(new Request(), stdClass::class);
    }

    public function testProcessThrowsIfServiceNotFound(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $processorFqcn = DummyProcessor::class;

        $locator = $this->createStub(ContainerInterface::class);
        $locator->method('get')
            ->willThrowException(new class extends Exception implements NotFoundExceptionInterface {});

        $dataProcessor = new DataProcessor($locator);

        $dataProcessor->process(new Request(), $processorFqcn);
    }
}

class DummyProcessor implements DataProcessorInterface
{
    public function process(Request $request): mixed
    {
        return 'dummy';
    }
}
