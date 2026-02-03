<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Contract;

interface DtoDeserializerInterface
{
    public function denormalize(array $data, string $type): object;
}
