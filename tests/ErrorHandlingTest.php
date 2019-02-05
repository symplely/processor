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
            })->catch(function (\Exception $e) {
                $this->assertRegExp('/test/', $e->getMessage());
            });

        $process->run();
        $this->assertFalse($process->isSuccessful());
        $this->assertTrue($process->isTerminated());
    }
 
    /** @test */
    public function it_handles_stderr_as_processor_error()
    {
        $process = Processor::create(function () {
            fwrite(STDERR, 'test');
        })->catch(function (ProcessorError $error) {
           $this->assertStringContainsString('test', $error->getMessage());
        });

        $process->run();
        $this->assertTrue($process->isSuccessful());
        $this->assertEquals('test', $process->getErrorOutput());
        $this->assertNull($process->getOutput());
    }

    /** @test */
    public function it_throws_the_exception_if_no_catch_callback()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessageRegExp('/test/');

        $process = Processor::create(function () {
            throw new \Exception('test');
        });

        $process->run();
    }

    /** @test */
    public function it_throws_fatal_errors()
    {
        $this->expectException(\Error::class);
        $this->expectExceptionMessageRegExp('/test/');

        $process = Processor::create(function () {
            throw new \Error('test');
        });

        $process->run();
    }

    /** @test */
    public function it_keeps_the_original_trace()
    {
        $process = Processor::create(function () {
            $error = new ProcessorError();
            throw $error->fromException('test');
        })->catch(function (ProcessorError $exception) {
            $this->assertStringContainsString('Async\Processor\ProcessorError::fromException(\'test\')', $exception->getMessage());
        });

        $process->run();
    }
}
