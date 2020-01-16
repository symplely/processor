<?php

declare(strict_types=1);

namespace Async\Processor;

interface ProcessInterface
{
    /**
     * Gets PHP's process ID
     *
     * @return int
     */
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
     * Start the process and wait to terminate
     *
     * @param bool $useYield - should we use generator callback functions
     */
    public function run(bool $useYield = false);

    /**
     * Return an generator that can start
     * the process and wait to terminate
     *
     * @return \Generator
     */
    public function yielding();

    /**
     * Waits for all processes to terminate
     *
     * @param int $waitTimer - Halt time in micro seconds
     * @param bool $useYield - should we use generator callback functions
     */
    public function wait($waitTimer = 1000, bool $useYield = false);

    /**
     * Add handlers to be called when the process is successful, erred or progressing in real time
     *
     * @param callable $doneCallback
     * @param callable $failCallback
     * @param callable $progressCallback
     *
     * @return ProcessInterface
     */
    public function then(callable $doneCallback, callable $failCallback = null, callable $progressCallback = null);

    /**
     * Add handlers to be called when the process is successful
     *
     * @param callable $callback
     *
     * @return ProcessInterface
     */
    public function done(callable $callback);

    /**
     * Add handlers to be called when the process progressing output in real time
     *
     * @param callable $progressCallback
     *
     * @return ProcessInterface
     */
    public function progress(callable $progressCallback);

    /**
     * Call the progressCallbacks on the process output in real time
     *
     * @param mixed  $update
     *
     * @return ProcessInterface
     */
    public function triggerOutput($update = null);

    /**
     * Add handlers to be called when the process has errors
     *
     * @param callable $callback
     *
     * @return ProcessInterface
     */
    public function catch(callable $callback);

    /**
     * Add handlers to be called when the process has timed out
     *
     * @param callable $callback
     *
     * @return ProcessInterface
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

    public function yieldSuccess();

    public function yieldError();

    public function yieldTimeout();

    /**
     * Returns the Pid (process identifier), if applicable.
     *
     * @return int|null — The process id if running, null otherwise
     */
    public function getPid(): ?int;

    /**
     * Stops the running process.
     *
     * @return ProcessInterface
     */
    public function stop();

    /**
     * Check if the process has timeout (max. runtime).
     *
     * @return bool
     */
    public function isTimedOut(): bool;

    /**
     * Checks if the process is currently running.
     *
     * @return bool true if the process is currently running, false otherwise
     */
    public function isRunning(): bool;

    /**
     * Checks if the process is terminated.
     *
     * @return bool true if process is terminated, false otherwise
     */
    public function isTerminated(): bool;

    /**
     * Checks if the process ended successfully.
     *
     * @return bool true if the process ended successfully, false otherwise
     */
    public function isSuccessful(): bool;
}
