<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('app_layer.query_handler')]
interface QueryHandlerInterface
{
    /**
     * Returns read-side data without mutating application state.
     *
     * @return mixed A non-null result (DTO, array, scalar, etc.);
     *               null indicates a contract violation.
     */
    public function handle(Request $request, object $query): mixed;
}
