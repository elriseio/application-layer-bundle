<?php

namespace Elrise\Bundle\AppLayerBundle\Tests\Handler;

use Elrise\Bundle\AppLayerBundle\Contract\RequestHandlerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestHandlerTraitMapProviderInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestSanitizerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestToDtoConverterInterface;
use Elrise\Bundle\AppLayerBundle\Dispatcher\DtoQueueDispatcherInterface;
use Elrise\Bundle\AppLayerBundle\Handler\DtoRequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use InvalidArgumentException;

final class DtoRequestHandlerTest extends TestCase
{
    public function testHandleWithSanitizerAndQueueDispatch(): void
    {
        $request = new Request();
        $sanitizedRequest = new Request();

        $dto = new TestDto();
        $dtoFqcn = get_class($dto);
        $handlerFqcn = DummyHandler::class;

        $traitMap = [DummyTrait::class => DummyTraitHandler::class];

        // Mock sanitizer
        $sanitizer = $this->createMock(RequestSanitizerInterface::class);
        $sanitizer->expects($this->once())
            ->method('sanitize')
            ->with($request)
            ->willReturn($sanitizedRequest);

        // Mock converter
        $converter = $this->createMock(RequestToDtoConverterInterface::class);
        $converter->expects($this->once())
            ->method('convert')
            ->with($sanitizedRequest, $dtoFqcn)
            ->willReturn($dto);

        // Mock trait handler
        $traitHandler = $this->createMock(RequestHandlerInterface::class);
        $traitHandler->expects($this->once())
            ->method('handle')
            ->with($sanitizedRequest, $dto)
            ->willReturn($dto);

        // Mock main handler
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects($this->once())
            ->method('handle')
            ->with($sanitizedRequest, $dto)
            ->willReturn($dto);

        // Mock queue dispatcher
        $dispatcher = $this->createMock(DtoQueueDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with($dto);

        // Logger (optional)
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))->method('info');
        $logger->expects($this->once())->method('debug');

        // Trait map provider
        $traitMapProvider = $this->createMock(RequestHandlerTraitMapProviderInterface::class);
        $traitMapProvider->method('getTraitMap')
            ->willReturn($traitMap);

        // Service locator
        $locator = $this->createMock(ServiceLocator::class);
        $locator->method('get')
            ->willReturnCallback(function ($fqcn) use ($handlerFqcn, $traitMap, $traitHandler, $handler) {
                if ($fqcn === $handlerFqcn) {
                    return $handler;
                }

                if ($fqcn === $traitMap[DummyTrait::class]) {
                    return $traitHandler;
                }

                return null;
            });

        $requestHandler = new DtoRequestHandler(
            $locator,
            $traitMapProvider,
            $converter,
            $dispatcher,
            $sanitizer,
            $logger,
        );

        $result = $requestHandler->handle($request, $dtoFqcn, $handlerFqcn, true);

        $this->assertSame($dto, $result);
    }

    public function testInvalidHandlerClassThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $request = new Request();
        $dto = new TestDto();
        $dtoFqcn = get_class($dto);
        $invalidHandlerFqcn = \stdClass::class;

        $traitMapProvider = $this->createMock(RequestHandlerTraitMapProviderInterface::class);
        $traitMapProvider->method('getTraitMap')->willReturn([]);

        $converter = $this->createMock(RequestToDtoConverterInterface::class);
        $converter->method('convert')->willReturn($dto);

        $locator = $this->createMock(ServiceLocator::class);
        $dispatcher = $this->createMock(DtoQueueDispatcherInterface::class);

        $handler = new DtoRequestHandler(
            $locator,
            $traitMapProvider,
            $converter,
            $dispatcher
        );

        $handler->handle($request, $dtoFqcn, $invalidHandlerFqcn);
    }
}

class TestDto
{
    use DummyTrait;
}

trait DummyTrait {}

class DummyHandler implements RequestHandlerInterface
{
    public function handle(Request $request, object $dto): object
    {
        return $dto;
    }
}

class DummyTraitHandler implements RequestHandlerInterface
{
    public function handle(Request $request, object $dto): object
    {
        return $dto;
    }
}