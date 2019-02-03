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

    public function restart();
    
    public function wait();

    public function then(callable $callback);

    public function catch(callable $callback);

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
