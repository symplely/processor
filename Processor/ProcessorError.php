<?php

namespace Async\Processor;

use Exception;

class ProcessorError extends Exception
{
    public static function fromException($exception): self
    {
        return new self($exception);
    }
}
