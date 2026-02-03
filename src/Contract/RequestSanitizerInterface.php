<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Contract;

use Symfony\Component\HttpFoundation\Request;

interface RequestSanitizerInterface
{
    public function sanitize(Request $request): Request;
}
