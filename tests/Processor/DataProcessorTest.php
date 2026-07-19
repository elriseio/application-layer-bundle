<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\Processor;

use Elrise\Bundle\AppLayerBundle\Contract\DataProcessorInterface;
use Elrise\Bundle\AppLayerBundle\Exception\RequestException;
use Elrise\Bundle\AppLayerBundle\Processor\DataProcessor;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\NotFoundExceptionInterface;
use stdClass;
use Symfony\Component\DependencyInjection\ServiceLocator;
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

        $locator = $this->createMock(ServiceLocator::class);
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
        $this->expectException(RequestException::class);
        $this->expectExceptionMessage('Class "stdClass" must implement interface "Elrise\Bundle\AppLayerBundle\Contract\DataProcessorInterface".');

        $locator = $this->createStub(ServiceLocator::class);
        $dataProcessor = new DataProcessor($locator);

        $dataProcessor->process(new Request(), stdClass::class);
    }

    public function testProcessThrowsRequestExceptionWithDetails(): void
    {
        $locator = $this->createStub(ServiceLocator::class);
        $dataProcessor = new DataProcessor($locator);

        try {
            $dataProcessor->process(new Request(), stdClass::class);
            $this->fail('Expected RequestException was not thrown.');
        } catch (RequestException $e) {
            $this->assertSame(['processor' => stdClass::class, 'expected_interface' => DataProcessorInterface::class], $e->getDetails());
        }
    }

    public function testProcessThrowsIfServiceNotFound(): void
    {
        $this->expectException(NotFoundExceptionInterface::class);

        $processorFqcn = DummyProcessor::class;

        $locator = $this->createStub(ServiceLocator::class);
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
