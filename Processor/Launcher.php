<?php
/**
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */ 
namespace Async\Processor;

use Throwable;
use Async\Processor\Process;
use Async\Processor\ProcessorError;
use Async\Processor\SerializableException;
use Async\Processor\ProcessInterface;

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
    protected $realOutput;
    protected $realTimeOutput;

    protected $startTime;

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

    public static function create(Process $process, int $id, int $timeout = 300): self
    {
        return new self($process, $id, $timeout);
    }

    public function start(): self
    {
        $this->startTime = \microtime(true);

        $this->process->start(function ($type, $buffer) {
            $this->realTimeOutput .= $buffer;
        });

        $this->pid = $this->process->getPid();

        return $this;
    }
	
    public function restart(): self
    {
        if ($this->isRunning())
            $this->stop();

        $process = clone $this->process;

        $launcher = $this->create($process, $this->id, $this->timeout);

        return $launcher->start();
    }

    public function run()
    {
        $this->start();

        return $this->wait();
    }

    public function wait($waitTimer = 1000)
    {
        while ($this->isRunning()) {
            if ($this->isTimedOut()) {
                $this->stop();
                return $this->triggerTimeout();
            }
            
            $this->triggerOutput( $this->getRealOutput() );
            usleep($waitTimer);
        }

        return $this->checkProcess();
    }

    protected function checkProcess()
    {
        if ($this->isSuccessful()) {
            return $this->triggerSuccess();
        } 

        return $this->triggerError();
    }

    public function stop(): self
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

            $this->output = @\unserialize(\base64_decode($processOutput));

            if (! $this->output) {
                $this->errorOutput = $processOutput;
            }
        }

        return $this->output;
    }

    public function getRealOutput()
    {
        if (! $this->realOutput) {
            $processOutput = $this->realTimeOutput;
            
            $this->realTimeOutput = null;

            $this->realOutput = @\unserialize(\base64_decode($processOutput));

            if (! $this->realOutput) {
                $this->realOutput = $processOutput;
            }
        } elseif ($this->realTimeOutput) {
            $this->realOutput .= @\unserialize(\base64_decode($this->realTimeOutput));
            $this->realTimeOutput = null;
        }

        return $this->realOutput;
    }

    public function getErrorOutput()
    {
        if (! $this->errorOutput) {
            $processOutput = $this->process->getErrorOutput();

            $this->errorOutput = @\unserialize(\base64_decode($processOutput));

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

    public function then(callable $doneCallback, callable $failCallback = null, callable $progressCallback = null): self
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

    public function progress(callable $progressCallback)
    {
        $this->progressCallbacks[] = $progressCallback;
        
        return $this;
    }

    public function done(callable $callback): self
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

    public function triggerOutput($update = null)
    {
        foreach ($this->progressCallbacks as $progressCallback) {
            $progressCallback($update);
        }
    }

    public function triggerSuccess()
    {
        if ($this->getRealOutput() && !$this->getErrorOutput()) {
            $output = $this->realOutput;
            $this->output = $output;
        } elseif ($this->errorOutput) {
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
        
        if (! $this->errorCallbacks) {
            throw $exception;
        }
    }

    public function triggerTimeout()
    {
        foreach ($this->timeoutCallbacks as $callback) 
            $callback();
    }

    public function yieldLiveUpdate($update = null, $type = null)
    {
        foreach ($this->progressCallbacks as $progressCallback) {
            yield $progressCallback($update, $type);
        }
    }

    public function yieldSuccess()
    {
        if ($this->getRealOutput() && !$this->getErrorOutput()) {
            $output = $this->realOutput;
            $this->output = $output;
        } elseif ($this->errorOutput) {
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
        
        if (! $this->errorCallbacks) {
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

        if (! $exception instanceof Throwable) {
            $exception = ProcessorError::fromException($exception);
        }

        return $exception;
    }
}
