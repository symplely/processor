<?php

use Async\Processor\Processor;
use Async\Processor\ProcessInterface;

if (! \function_exists('spawn')) {
    /**
     * Create an process by shell command or callable.
	 * 
     * @param shell|callable $somethingToRun
     * @param mixed $processChannel
     * @param int $timeout 
     *
     * @return ProcessInterface
     */
    function spawn($somethingToRun, $processChannel = null, int $timeout = 300): ProcessInterface
    {
		return Processor::create($somethingToRun, $timeout, $processChannel);
	}

    function spawn_run(ProcessInterface $process)
    {
		return $process->run();
    }
}
