<?php

namespace Async\Processor;

use Async\Loop\ProcessorInterface;

interface ProcessInterface extends ProcessorInterface
{	
    public function getId(): int;

    /**
     * Start the process
     * 
     * @return ProcessInterface
     */
    public function start();

    /**
     * Restart the process
     * 
     * @return ProcessInterface
     */
    public function restart();

    /**
     * Waits for all processes to terminate
     *
     * @param int $waitTimer    Halt time in micro seconds
     */
    public function wait($waitTimer = 1000);

    /**
     * Add handlers to be called when the process is successful
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function then(callable $callback);

    /**
     * Add handlers to be called when the process has errors
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function catch(callable $callback);

    /**
     * Add handlers to be called when the process has timed out
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function timeout(callable $callback);

    /**
     * Returns the current output of the process (STDOUT).
     *
     * @return string The process output
     */
    public function getOutput();
		
    /**
     * Returns the current error output of the process (STDERR).
     *
     * @return string The process error output
     */   
    public function getErrorOutput();
}
