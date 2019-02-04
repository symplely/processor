<?php

namespace Async\Tests;

use Error;
use ParseError;
use Async\Processor\Processor;
use Async\Processor\ProcessorError;
use PHPUnit\Framework\TestCase;

class ErrorHandlingTest extends TestCase
{
    /** @test */
    public function it_can_handle_exceptions_via_catch_callback()
    {
        $process = Processor::create(function () {
                throw new \Exception('test');
            })->catch(function (ProcessorError $e) {
                $this->assertRegExp('/test/', $e->getMessage());
            });

        $process->wait();
        $this->assertTrue($process->isTerminated());
    }
 
    /** @test */
    public function it_handles_stderr_as_processor_error()
    {
        $process = Processor::create(function () {
            fwrite(STDERR, 'test');
        })->catch(function (ProcessorError $error) {
            $this->assertContains('test2', $error->getMessage());
        });

        $process->wait();
    }
}
