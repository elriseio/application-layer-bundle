<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Handler;

use InvalidArgumentException;
use Elrise\Bundle\AppLayerBundle\Contract\RequestHandlerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestHandlerTraitMapProviderInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestSanitizerInterface;
use Elrise\Bundle\AppLayerBundle\Contract\RequestToDtoConverterInterface;
use Elrise\Bundle\AppLayerBundle\Dispatcher\DtoQueueDispatcherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;

final class DtoRequestHandler
{
    private array $requestHandlerTraitMap;

    public function __construct(
        #[TaggedLocator('app_layer.dto_request_handler')]
        private readonly ServiceLocator $locator,
        private readonly RequestHandlerTraitMapProviderInterface $traitMapProvider,
        private readonly RequestToDtoConverterInterface $dtoConverter,
        private readonly DtoQueueDispatcherInterface $queueDispatcher,
        private readonly ?RequestSanitizerInterface $requestSanitizer = null,
        private readonly ?LoggerInterface $logger = null,
    ) {
        $this->requestHandlerTraitMap = $traitMapProvider->getTraitMap();
    }

    public function handle(Request $request, string $dtoFqcn, string $handlerFqcn, bool $dispatchToQueue = false): object
    {
        $this->logger?->info('Handling DTO request.', [
            'dto' => $dtoFqcn,
            'handler' => $handlerFqcn,
        ]);

        if ($this->requestSanitizer !== null) {
            $request = $this->requestSanitizer->sanitize($request);
        }

        $dto = $this->dtoConverter->convert($request, $dtoFqcn);

        if (!is_subclass_of($handlerFqcn, RequestHandlerInterface::class)) {
            throw new InvalidArgumentException(sprintf('Class "%s" must implement interface "%s".', $handlerFqcn, RequestHandlerInterface::class));
        }

        $usedTraits = class_uses($dto, false);

        foreach ($this->requestHandlerTraitMap as $traitFqcn => $requestHandlerFqcn) {
            if (in_array($traitFqcn, $usedTraits, true)) {
                $this->logger?->debug("Applying handler {$requestHandlerFqcn} for trait {$traitFqcn}");
                $dto = $this->locator->get($requestHandlerFqcn)->handle($request, $dto);
            }
        }

        /** @var RequestHandlerInterface $handler */
        $handler = $this->locator->get($handlerFqcn);
        $dto = $handler->handle($request, $dto);

        if ($dispatchToQueue) {
            $this->logger?->info('Dispatching DTO to queue', ['dto' => get_class($dto)]);
            $this->queueDispatcher->dispatch($dto);
        }

        return $dto;
    }
}
