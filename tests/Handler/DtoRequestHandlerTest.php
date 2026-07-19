<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Tests\Handler;

use Elrise\Bundle\AppLayerBundle\Contract\CommandHandlerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\QueryHandlerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestSanitizerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestToDtoConverterInterface;
use Elrise\Bundle\AppLayerBundle\Dispatcher\DtoQueueDispatcherInterface;
use Elrise\Bundle\AppLayerBundle\Exception\RequestException;
use Elrise\Bundle\AppLayerBundle\Handler\DtoRequestHandler;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use RuntimeException;
use stdClass;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;

final class DtoRequestHandlerTest extends TestCase
{
    public function testDispatchCommandReturnsHandlerResult(): void
    {
        $request = new Request();
        $sanitized = new Request();
        $command = new stdClass();
        $expectedResult = ['id' => 'cmd-1'];

        $sanitizer = $this->createMock(RequestSanitizerInterface::class);
        $sanitizer->expects($this->once())->method('sanitize')->with($request)->willReturn($sanitized);

        $converter = $this->createMock(RequestToDtoConverterInterface::class);
        $converter->expects($this->once())->method('convert')->with($sanitized, stdClass::class)->willReturn($command);

        $handler = $this->createMock(CommandHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($sanitized, $command)->willReturn($expectedResult);

        $dispatcher = $this->createStub(DtoQueueDispatcherInterface::class);

        $commandLocator = $this->createMock(ServiceLocator::class);
        $commandLocator->method('get')->with(CommandHandlerStub::class)->willReturn($handler);

        $queryLocator = $this->createStub(ServiceLocator::class);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info');

        $sut = new DtoRequestHandler(
            $commandLocator,
            $queryLocator,
            $converter,
            $dispatcher,
            $sanitizer,
            $logger,
        );

        $result = $sut->dispatchCommand($request, stdClass::class, CommandHandlerStub::class);

        $this->assertSame($expectedResult, $result);
    }

    public function testDispatchCommandQueuesWhenRequested(): void
    {
        $request = new Request();
        $command = new stdClass();

        $converter = $this->createStub(RequestToDtoConverterInterface::class);
        $converter->method('convert')->willReturn($command);

        $handler = $this->createStub(CommandHandlerInterface::class);

        $dispatcher = $this->createMock(DtoQueueDispatcherInterface::class);
        $dispatcher->expects($this->once())->method('dispatch')->with($command);

        $commandLocator = $this->createStub(ServiceLocator::class);
        $commandLocator->method('get')->willReturn($handler);

        $sut = new DtoRequestHandler(
            $commandLocator,
            $this->createStub(ServiceLocator::class),
            $converter,
            $dispatcher,
        );

        $sut->dispatchCommand($request, stdClass::class, CommandHandlerStub::class, dispatchToQueue: true);
    }

    public function testDispatchQuerySkipsSanitizerAndQueue(): void
    {
        $request = new Request();
        $query = new stdClass();
        $expected = ['items' => []];

        $sanitizer = $this->createMock(RequestSanitizerInterface::class);
        $sanitizer->expects($this->never())->method('sanitize');

        $converter = $this->createMock(RequestToDtoConverterInterface::class);
        $converter->expects($this->once())->method('convert')->with($request, stdClass::class)->willReturn($query);

        $handler = $this->createMock(QueryHandlerInterface::class);
        $handler->expects($this->once())->method('handle')->with($request, $query)->willReturn($expected);

        $dispatcher = $this->createStub(DtoQueueDispatcherInterface::class);

        $queryLocator = $this->createMock(ServiceLocator::class);
        $queryLocator->method('get')->with(QueryHandlerStub::class)->willReturn($handler);

        $sut = new DtoRequestHandler(
            $this->createStub(ServiceLocator::class),
            $queryLocator,
            $converter,
            $dispatcher,
            $sanitizer,
        );

        $result = $sut->dispatchQuery($request, stdClass::class, QueryHandlerStub::class);

        $this->assertSame($expected, $result);
    }

    public function testInvalidCommandHandlerClassThrows(): void
    {
        $this->expectException(RequestException::class);

        $converter = $this->createStub(RequestToDtoConverterInterface::class);
        $converter->method('convert')->willReturn(new stdClass());

        $sut = new DtoRequestHandler(
            $this->createStub(ServiceLocator::class),
            $this->createStub(ServiceLocator::class),
            $converter,
            $this->createStub(DtoQueueDispatcherInterface::class),
        );

        $sut->dispatchCommand(new Request(), stdClass::class, stdClass::class);
    }

    public function testMissingCommandHandlerThrows(): void
    {
        $this->expectException(RequestException::class);

        $converter = $this->createStub(RequestToDtoConverterInterface::class);
        $converter->method('convert')->willReturn(new stdClass());

        $commandLocator = $this->createStub(ServiceLocator::class);
        $commandLocator->method('get')->willThrowException(new RuntimeException('not found'));

        $sut = new DtoRequestHandler(
            $commandLocator,
            $this->createStub(ServiceLocator::class),
            $converter,
            $this->createStub(DtoQueueDispatcherInterface::class),
        );

        $sut->dispatchCommand(new Request(), stdClass::class, CommandHandlerStub::class);
    }
}

final class CommandHandlerStub implements CommandHandlerInterface
{
    public function handle(Request $request, object $command): mixed
    {
        return null;
    }
}

final class QueryHandlerStub implements QueryHandlerInterface
{
    public function handle(Request $request, object $query): mixed
    {
        return null;
    }
}
