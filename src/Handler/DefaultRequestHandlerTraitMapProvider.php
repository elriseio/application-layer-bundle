<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Handler;

use Elrise\Bundle\AppLayerBundle\Contract\RequestHandlerTraitMapProviderInterface;

final class DefaultRequestHandlerTraitMapProvider implements RequestHandlerTraitMapProviderInterface
{
    public function getTraitMap(): array
    {
        return [
        ];
    }
}
