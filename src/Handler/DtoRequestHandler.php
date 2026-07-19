<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Handler;

use Elrise\Bundle\AppLayerBundle\Contract\CommandHandlerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\QueryHandlerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestSanitizerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestToDtoConverterInterface;
use Elrise\Bundle\AppLayerBundle\Dispatcher\DtoQueueDispatcherInterface;
use Elrise\Bundle\AppLayerBundle\Exception\RequestException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Throwable;

final class DtoRequestHandler
{
    public function __construct(
        #[TaggedLocator('app_layer.command_handler')]
        private readonly ServiceLocator $commandLocator,
        #[TaggedLocator('app_layer.query_handler')]
        private readonly ServiceLocator $queryLocator,
        private readonly RequestToDtoConverterInterface $dtoConverter,
        private readonly DtoQueueDispatcherInterface $queueDispatcher,
        private readonly ?RequestSanitizerInterface $requestSanitizer = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
    }

    /**
     * Dispatches a Command: HTTP request → sanitization → DTO conversion →
     * synchronous handler invocation → optional queue dispatch.
     *
     * @template T of object
     * @param class-string<T> $commandFqcn
     * @param class-string<CommandHandlerInterface> $handlerFqcn
     * @return mixed The command handler result (id, presenter, etc.).
     *
     * @throws RequestException
     */
    public function dispatchCommand(
        Request $request,
        string $commandFqcn,
        string $handlerFqcn,
        bool $dispatchToQueue = false,
    ): mixed {
        $this->logger?->info('Dispatching command.', [
            'command' => $commandFqcn,
            'handler' => $handlerFqcn,
            'async' => $dispatchToQueue,
        ]);

        $request = $this->sanitize($request);
        $command = $this->dtoConverter->convert($request, $commandFqcn);

        $handler = $this->resolveHandler(
            $this->commandLocator,
            $handlerFqcn,
            CommandHandlerInterface::class,
        );

        $result = $handler->handle($request, $command);

        if ($dispatchToQueue) {
            $this->logger?->info('Dispatching command to queue.', ['command' => $command::class]);
            $this->queueDispatcher->dispatch($command);
        }

        return $result;
    }

    /**
     * Dispatches a Query: HTTP request → DTO conversion → synchronous
     * handler invocation. Queries do not mutate the request and are
     * never queued — sanitization is the responsibility of the
     * caller or the query handler itself.
     *
     * @template T of object
     * @param class-string<T> $queryFqcn
     * @param class-string<QueryHandlerInterface> $handlerFqcn
     * @return mixed the query result (DTO, array, scalar)
     *
     * @throws RequestException
     */
    public function dispatchQuery(
        Request $request,
        string $queryFqcn,
        string $handlerFqcn,
    ): mixed {
        $this->logger?->info('Dispatching query.', [
            'query' => $queryFqcn,
            'handler' => $handlerFqcn,
        ]);

        $query = $this->dtoConverter->convert($request, $queryFqcn);

        $handler = $this->resolveHandler(
            $this->queryLocator,
            $handlerFqcn,
            QueryHandlerInterface::class,
        );

        return $handler->handle($request, $query);
    }

    private function sanitize(Request $request): Request
    {
        if ($this->requestSanitizer === null) {
            return $request;
        }

        return $this->requestSanitizer->sanitize($request);
    }

    /**
     * @template T of object
     * @param ServiceLocator<object> $locator
     * @param class-string<T> $handlerFqcn
     * @param class-string<object> $expectedInterface
     */
    private function resolveHandler(
        ServiceLocator $locator,
        string $handlerFqcn,
        string $expectedInterface,
    ): object {
        if (!is_subclass_of($handlerFqcn, $expectedInterface)) {
            throw new RequestException(sprintf('Handler "%s" must implement "%s".', $handlerFqcn, $expectedInterface), ['handler' => $handlerFqcn, 'expected_interface' => $expectedInterface]);
        }

        try {
            $handler = $locator->get($handlerFqcn);
        } catch (Throwable $e) {
            throw new RequestException(sprintf('Failed to resolve handler "%s" from the application locator.', $handlerFqcn), ['handler' => $handlerFqcn], 0, $e);
        }

        if (!$handler instanceof $expectedInterface) {
            throw new RequestException(sprintf('Service "%s" resolved to "%s" which does not implement "%s".', $handlerFqcn, $handler::class, $expectedInterface));
        }

        return $handler;
    }
}
