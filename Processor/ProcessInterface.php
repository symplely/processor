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
     * Add handlers to be called when the process is successful, erred or progressing in real time
     *
     * @param callable $doneCallback
     * @param callable $failCallback
     * @param callable $progressCallback
     *
     * @return $this
     */
    public function then(callable $doneCallback, callable $failCallback = null, callable $progressCallback = null);

    /**
     * Add handlers to be called when the process is successful
     *
     * @param callable $callback
     *
     * @return $this
     */
    public function done(callable $callback);

    /**
     * Add handlers to be called when the process progressing output in real time
     *
     * @param callable $progressCallback
     *
     * @return $this
     */
    public function progress(callable $progressCallback);

    /**
     * Call the progressCallbacks on the process output in real time
     *
     * @param mixed  $update
     *
     * @return $this
     */
    public function triggerOutput($update = null);

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
