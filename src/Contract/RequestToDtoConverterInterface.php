<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Contract;

use Symfony\Component\HttpFoundation\Request;

interface RequestToDtoConverterInterface
{
    public function convert(Request $request, string $dtoFqcn): object;
}
