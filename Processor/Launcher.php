<?php

declare(strict_types=1);

/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Async\Processor;

use Throwable;
use Async\Processor\Process;
use Async\Processor\ProcessorError;
use Async\Processor\SerializableException;
use Async\Processor\LauncherInterface;

/**
 * Launcher runs a command/script/application/callable in an independent process.
 */
class Launcher implements LauncherInterface
{
    protected $timeout = null;
    protected $process;
    protected $id;
    protected $pid;

    protected $output;
    protected $errorOutput;
    protected $rawLastResult;
    protected $lastResult;

    protected $startTime;
    protected $showOutput = false;

    protected $successCallbacks = [];
    protected $errorCallbacks = [];
    protected $timeoutCallbacks = [];
    protected $progressCallbacks = [];

    private function __construct(Process $process, int $id, int $timeout = 300)
    {
        $this->timeout = $timeout;
        $this->process = $process;
        $this->id = $id;
    }

    public static function create(Process $process, int $id, int $timeout = 300): LauncherInterface
    {
        return new self($process, $id, $timeout);
    }

    public function start(): LauncherInterface
    {
        $this->startTime = \microtime(true);

        $this->process->start(function ($type, $buffer) {
            $this->lastResult = $buffer;
            $this->display($buffer);
            $this->triggerProgress($type, $buffer);
        });

        $this->pid = $this->process->getPid();

        return $this;
    }

    public function restart(): LauncherInterface
    {
        if ($this->isRunning())
            $this->stop();

        $process = clone $this->process;

        $launcher = $this->create($process, $this->id, $this->timeout);

        return $launcher->start();
    }

    public function run(bool $useYield = false)
    {
        $this->start();

        if ($useYield)
            return $this->wait(1000, true);

        return $this->wait();
    }

    public function yielding()
    {
        return yield from $this->run(true);
    }

    public function display($buffer = null)
    {
        if ($this->showOutput) {
            \printf('%s', $this->realTime($buffer));
        }
    }

    public function wait($waitTimer = 1000, bool $useYield = false)
    {
        while ($this->isRunning()) {
            if ($this->isTimedOut()) {
                $this->stop();
                if ($useYield)
                    return $this->yieldTimeout();

                return $this->triggerTimeout();
            }

            \usleep($waitTimer);
        }

        return $this->checkProcess($useYield);
    }

    protected function checkProcess(bool $useYield = false)
    {
        if ($this->isSuccessful()) {
            if ($useYield)
                return $this->yieldSuccess();

            return $this->triggerSuccess();
        }

        if ($useYield)
            return $this->yieldError();

        return $this->triggerError();
    }

    public function stop(): LauncherInterface
    {
        $this->process->stop();

        return $this;
    }

    public function isTimedOut(): bool
    {
        if (empty($this->timeout) || !$this->process->isStarted()) {
            return false;
        }

        return ((\microtime(true) - $this->startTime) > $this->timeout);
    }

    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }

    public function displayOn(): LauncherInterface
    {
        $this->showOutput = true;

        return $this;
    }

    public function displayOff(): LauncherInterface
    {
        $this->showOutput = false;

        return $this;
    }

    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    public function isTerminated(): bool
    {
        return $this->process->isTerminated();
    }

    public function cleanUp($output = null)
    {
        return \is_string($output)
                ? \str_replace('Tjs=', '', $output)
                : $output;
    }

    protected function decode($output, $errorSet = false)
    {
        $realOutput = @\unserialize(\base64_decode((string) $output));
        if (!$realOutput) {
            $realOutput = $output;
            if ($errorSet) {
                $this->errorOutput = $realOutput;
            }
        }

        return $realOutput;
    }

    public function getOutput()
    {
        if (!$this->output) {
            $processOutput = $this->process->getOutput();
            $this->output = $this->cleanUp($this->decode($processOutput, true));

            $cleaned = $this->output;
            $replaceWith = $this->getResult();
            if (\strpos((string) $cleaned, $this->rawLastResult) !== false) {
                $this->output = \str_replace($this->rawLastResult, $replaceWith, $cleaned);
            }

        }

        return $this->output;
    }

    protected function realTime($buffer = null)
    {
        if (!empty($buffer)) {
            return $this->cleanUp($this->decode($buffer));
        }
    }

    public function getErrorOutput()
    {
        if (!$this->errorOutput) {
            $processOutput = $this->process->getErrorOutput();

            $this->errorOutput = $this->decode($processOutput);
        }

        return $this->errorOutput;
    }

    public function getResult()
    {
        if (!$this->rawLastResult) {
            $this->rawLastResult = $this->lastResult;
        }

        $this->lastResult = $this->cleanUp($this->decode($this->rawLastResult));
        return $this->lastResult;
    }

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPid(): ?int
    {
        return $this->pid;
    }

    public function then(callable $doneCallback, callable $failCallback = null, callable $progressCallback = null): LauncherInterface
    {
        $this->done($doneCallback);

        if ($failCallback !== null) {
            $this->catch($failCallback);
        }

        if ($progressCallback !== null) {
            $this->progress($progressCallback);
        }

        return $this;
    }

    public function progress(callable $progressCallback): LauncherInterface
    {
        $this->progressCallbacks[] = $progressCallback;

        return $this;
    }

    public function done(callable $callback): LauncherInterface
    {
        $this->successCallbacks[] = $callback;

        return $this;
    }

    public function catch(callable $callback): LauncherInterface
    {
        $this->errorCallbacks[] = $callback;

        return $this;
    }

    public function timeout(callable $callback): LauncherInterface
    {
        $this->timeoutCallbacks[] = $callback;

        return $this;
    }

    /**
     * Call the progressCallbacks on the process output in real time.
     */
    public function triggerProgress(string $type, string $buffer)
    {
        if (\count($this->progressCallbacks) > 0) {
            $liveOutput = $this->realTime($buffer);
            foreach ($this->progressCallbacks as $progressCallback) {
                $progressCallback($type, $liveOutput);
            }
        }
    }

    public function triggerSuccess()
    {
        if ($this->getResult() && !$this->getErrorOutput()) {
            $output = $this->lastResult;
        } elseif ($this->getErrorOutput()) {
            return $this->triggerError();
        } else {
            $output = $this->getOutput();
        }

        foreach ($this->successCallbacks as $callback)
            $callback($output);

        return $output;
    }

    public function triggerError()
    {
        $exception = $this->resolveErrorOutput();

        foreach ($this->errorCallbacks as $callback)
            $callback($exception);

        if (!$this->errorCallbacks) {
            throw $exception;
        }
    }

    public function triggerTimeout()
    {
        foreach ($this->timeoutCallbacks as $callback)
            $callback();
    }

    public function yieldSuccess()
    {
        if ($this->getResult() && !$this->getErrorOutput()) {
            $output = $this->lastResult;
        } elseif ($this->getErrorOutput()) {
            return $this->yieldError();
        } else {
            $output = $this->getOutput();
        }

        foreach ($this->successCallbacks as $callback) {
            yield $callback($output);
        }

        return $output;
    }

    public function yieldError()
    {
        $exception = $this->resolveErrorOutput();

        foreach ($this->errorCallbacks as $callback) {
            yield $callback($exception);
        }

        if (!$this->errorCallbacks) {
            throw $exception;
        }
    }

    public function yieldTimeout()
    {
        foreach ($this->timeoutCallbacks as $callback) {
            yield $callback();
        }
    }

    protected function resolveErrorOutput(): Throwable
    {
        $exception = $this->getErrorOutput();

        if ($exception instanceof SerializableException) {
            $exception = $exception->asThrowable();
        }

        if (!$exception instanceof Throwable) {
            $exception = ProcessorError::fromException($exception);
        }

        return $exception;
    }
}
