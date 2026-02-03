<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('app_layer.dto_request_handler')]
interface RequestHandlerInterface
{
    public function handle(Request $request, object $dto): object;
}
