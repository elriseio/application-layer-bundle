<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Processor;

use Elrise\Bundle\AppLayerBundle\Contract\DataProcessorInterface;
use Elrise\Bundle\AppLayerBundle\Exception\RequestException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Component\HttpFoundation\Request;

final class DataProcessor
{
    public function __construct(
        #[TaggedLocator('app_layer.data_processor')]
        private ContainerInterface $locator,
    ) {
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function process(Request $request, string $processorFqcn): mixed
    {
        if (!is_subclass_of($processorFqcn, DataProcessorInterface::class)) {
            throw new RequestException(sprintf('Class "%s" must implement interface "%s".', $processorFqcn, DataProcessorInterface::class), ['processor' => $processorFqcn, 'expected_interface' => DataProcessorInterface::class]);
        }

        $processor = $this->locator->get($processorFqcn);

        return $processor->process($request);
    }
}
