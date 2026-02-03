<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Contract;

interface RequestHandlerTraitMapProviderInterface
{
    public function getTraitMap(): array;
}
