<?php

declare(strict_types=1);

namespace Async\Processor;

use Async\Processor\Process;

interface LauncherInterface
{
    /**
     * Gets PHP's process ID.
     *
     * @return int
     */
    public function getId(): int;

    /**
     * Start the process.
     *
     * @return LauncherInterface
     */
    public function start(): LauncherInterface;

    /**
     * Restart the process.
     *
     * @return LauncherInterface
     */
    public function restart(): LauncherInterface;

    /**
     * Start the process and wait to terminate.
     *
     * @param bool $useYield - should we use generator callback functions
     */
    public function run(bool $useYield = false);

    /**
     * Return an generator that can start the process and wait to terminate.
     *
     * @return \Generator
     */
    public function yielding();

    /**
     * Waits for all processes to terminate.
     *
     * @param int $waitTimer - Halt time in micro seconds
     * @param bool $useYield - should we use generator callback functions
     */
    public function wait($waitTimer = 1000, bool $useYield = false);

    /**
     * Add handlers to be called when the process is successful, erred or progressing in real time.
     *
     * @param callable $doneCallback
     * @param callable $failCallback
     * @param callable $progressCallback
     *
     * @return LauncherInterface
     */
    public function then(callable $doneCallback, callable $failCallback = null, callable $progressCallback = null): LauncherInterface;

    /**
     * Add handlers to be called when the process is successful.
     *
     * @param callable $callback
     *
     * @return LauncherInterface
     */
    public function done(callable $callback): LauncherInterface;

    /**
     * Add handlers to be called when the process progressing, it's producing output.
     * This can be use as a IPC handler for real time interaction.
     *
     * The callback will receive **output type** either(`out` or `err`),
     * and **the output** in real-time.
     *
     * Use: __Channel__ `send()` to write to the standard input of the process.
     *
     * @param callable $progressCallback
     *
     * @return LauncherInterface
     */
    public function progress(callable $progressCallback): LauncherInterface;

    /**
     * Add handlers to be called when the process has errors.
     *
     * @param callable $callback
     *
     * @return LauncherInterface
     */
    public function catch(callable $callback): LauncherInterface;

    /**
     * Add handlers to be called when the process has timed out.
     *
     * @param callable $callback
     *
     * @return LauncherInterface
     */
    public function timeout(callable $callback): LauncherInterface;

    /**
     * Remove `Tjs=` if present from the output.
     *
     * @param mixed $output
     *
     * @return mixed
     */
    public function cleanUp($output = null);

    /**
     * Return the last/final output `piece` of the process.
     *
     * @return mixed
     */
    public function getResult();

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
     * @return LauncherInterface
     */
    public function stop(): LauncherInterface;

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

    /**
     * Set process to display output of child process.
     *
     * @return LauncherInterface
     */
    public function displayOn(): LauncherInterface;


    /**
     * Stop displaying output of child process.
     *
     * @return LauncherInterface
     */
    public function displayOff(): LauncherInterface;

    /**
     * Display child process output, if set.
     */
    public function display();


    /**
     * A handle for the PHP process.
     *
     * @return Process
     */
    public function getProcess(): Process;
}
