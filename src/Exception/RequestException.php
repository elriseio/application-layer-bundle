<?php

declare(strict_types=1);

namespace Elrise\Bundle\AppLayerBundle\Exception;

use Exception;

class RequestException extends Exception
{
    protected array $details = [];

    public function __construct(string $msg = '', array $details = [], int $code = 0, ?Exception $prev = null)
    {
        parent::__construct($msg, $code, $prev);
        $this->setDetails($details);
    }

    public function setDetails(array $details): self
    {
        $this->details = $details;

        return $this;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
