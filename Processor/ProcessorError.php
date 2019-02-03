<?php

namespace Async\Processor;

use Exception;

class ProcessorError extends Exception
{
    public static function fromException($exception): self
    {
        return new self($exception);
    }

    public static function outputTooLarge(int $bytes): self
    {
        return new self("The output returned by this child process is too large. The serialized output may only be $bytes bytes long.");
    }
}
