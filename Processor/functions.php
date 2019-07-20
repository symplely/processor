<?php

use Async\Processor\Processor;
use Async\Processor\ProcessInterface;

if (! \function_exists('spawn')) {
    /**
     * Create an process by shell command or callable.
	 * 
     * @param shell|callable $shellCallable
     * @param int $timeout 
     * @param mixed $processChannel
     *
     * @return ProcessInterface
     */
    function spawn($shellCallable, int $timeout = 300, $processChannel = null): ProcessInterface
    {
		return Processor::create($shellCallable, $timeout, $processChannel);
    }

    function await_spawn(ProcessInterface $process)
    {
		return $process->run();
    }
}
