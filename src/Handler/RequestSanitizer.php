<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Handler;

use Elrise\Bundle\AppLayerBundle\Contract\RequestSanitizerInterface;
use Symfony\Component\HttpFoundation\Request;

final class RequestSanitizer implements RequestSanitizerInterface
{
    public function sanitize(Request $request): Request
    {
        return $request;
    }
}
