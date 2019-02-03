<?php

namespace Async\Processor;

use Async\Loop\ProcessorInterface;

interface ProcessInterface extends ProcessorInterface
{
    public function then(callable $callback);

    public function catch(callable $callback);

    public function timeout(callable $callback);
}
