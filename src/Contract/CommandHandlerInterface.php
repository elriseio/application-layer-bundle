<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Contract;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;

#[AutoconfigureTag('app_layer.command_handler')]
interface CommandHandlerInterface
{
    /**
     * Mutates application state and returns a command result
     * (typically an identifier, a domain view, or a presenter DTO).
     *
     * @return mixed a non-null result suitable for the caller;
     *               null indicates a contract violation
     */
    public function handle(Request $request, object $command): mixed;
}
