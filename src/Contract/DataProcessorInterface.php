<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('app_layer.data_processor')]
interface DataProcessorInterface
{
    public function process(Request $request): mixed;
}
