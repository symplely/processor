<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */ 
namespace Async\Processor;

use Throwable;
use Async\Processor\ProcessorError;
use Async\Processor\SerializableException;
use Async\Processor\ProcessInterface;
use Symfony\Component\Process\Process;
use function Opis\Closure\unserialize;

/**
 * Launcher runs a command/script/application/callable in an independent process.
 */
class Launcher implements ProcessInterface
{
    protected $timeout = null;
    protected $process;
    protected $id;
    protected $pid;

    protected $output;
    protected $errorOutput;

    protected $startTime;

    protected $successCallbacks = [];
    protected $errorCallbacks = [];
    protected $timeoutCallbacks = [];

    private function __construct(Process $process, int $id, int $timeout = 300)
    {
        $this->timeout = $timeout;
        $this->process = $process;
        $this->id = $id;
    }

    public static function create(Process $process, int $id, int $timeout = 300): self
    {
        return new self($process, $id, $timeout);
    }

    public function start(): self
    {
        $this->startTime = \microtime(true);

        $this->process->start();

        $this->pid = $this->process->getPid();

        return $this;
    }
	
    public function restart(): self
    {
        $this->startTime = \microtime(true);

        $this->process = $this->process->restart();

        $this->pid = $this->process->getPid();

        return $this;
    }
	
    public function wait()
    {
        $this->process->run();
		
		if ($this->isTimedOut()) {
			$this->triggerTimeout();
		} elseif ($this->isSuccessful()) {
			return $this->triggerSuccess();
		} elseif ($this->isTerminated()) {
			$this->triggerError();
		}
    }

    public function stop(): self
    {
        $this->process->stop(10, SIGKILL);

        return $this;
    }

    public function isTimedOut(): bool
    {
        if (empty($this->timeout))
            return false;

        return ((\microtime(true) - $this->startTime) > $this->timeout);
    }

    public function isRunning(): bool
    {
        return $this->process->isRunning();
    }

    public function isSuccessful(): bool
    {
        return $this->process->isSuccessful();
    }

    public function isTerminated(): bool
    {
        return $this->process->isTerminated();
    }

    public function getOutput()
    {
        if (! $this->output) {
            $processOutput = $this->process->getOutput();

            $this->output = @unserialize(\base64_decode($processOutput));

            if (! $this->output) {
                $this->errorOutput = $processOutput;
            }
        }

        return $this->output;
    }

    public function getErrorOutput()
    {
        if (! $this->errorOutput) {
            $processOutput = $this->process->getErrorOutput();

            $this->errorOutput = @unserialize(\base64_decode($processOutput));

            if (! $this->errorOutput) {
                $this->errorOutput = $processOutput;
            }
        }

        return $this->errorOutput;
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

    public function then(callable $callback): self
    {
        $this->successCallbacks[] = $callback;

        return $this;
    }

    public function catch(callable $callback): self
    {
        $this->errorCallbacks[] = $callback;

        return $this;
    }

    public function timeout(callable $callback): self
    {
        $this->timeoutCallbacks[] = $callback;

        return $this;
    }

    public function triggerSuccess()
    {
       if ($this->getErrorOutput()) {
            return $this->triggerError();
        }

        $output = $this->getOutput();

        foreach ($this->successCallbacks as $callback) {
            $callback($output);
        }

        return $output;        
    }

    public function triggerError()
    {
        $exception = $this->resolveErrorOutput();

        foreach ($this->errorCallbacks as $callback) { 
            $callback($exception);
        }
        
        if (! $this->errorCallbacks) {
            throw $exception;
        }
    }

    public function triggerTimeout()
    {
        foreach ($this->timeoutCallbacks as $callback) {
            $callback();
        }
    }
    
    protected function resolveErrorOutput(): Throwable
    {
        $exception = $this->getErrorOutput();

        if ($exception instanceof SerializableException) {
            $exception = $exception->asThrowable();
        }

        if (! $exception instanceof Throwable) {
            $exception = ProcessorError::fromException($exception);
        }

        return $exception;
    }
}
