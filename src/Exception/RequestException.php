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
        $this->details = $details;
    }

    public function withDetails(array $details): self
    {
        $new = new self($this->getMessage(), $details, $this->getCode(), $this->getPrevious());
        $new->file = $this->file;
        $new->line = $this->line;

        return $new;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}