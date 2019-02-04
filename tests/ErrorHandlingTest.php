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

        $process->run();
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
    }

    /** @test */
    public function it_throws_the_exception_if_no_catch_callback()
    {
        //$this->expectException(\Exception::class);
        //$this->expectExceptionMessageRegExp('/test/');

        $process = Processor::create(function () {
            throw new MyException('test');
        });

        $process->run();
    }

    /** @test */
    public function it_throws_fatal_errors()
    {
        //$this->expectException(\Error::class);
        //$this->expectExceptionMessageRegExp('/test/');

        $process = Processor::create(function () {
            throw new Error('test');
        });

        $process->run();
    }

    /** @test */
    public function it_keeps_the_original_trace()
    {
        $parallel = new Parallel();

        $parallel->add(function () {
            $myClass = new MyClass();

            $myClass->throwException();
        })->catch(function (ParallelError $exception) {
            $this->assertStringContainsString('Async\Tests\MyClass->throwException()', $exception->getMessage());
        });

        $parallel->wait();
    }

    /** @test */
    public function it_handles_stderr_as_parallel_error()
    {
        $parallel = new Parallel();

        $parallel->add(function () {
            fwrite(STDERR, 'test');
        })->catch(function (ParallelError $error) {
            $this->assertStringContainsString('test', $error->getMessage());
        });

        $parallel->wait();
    }
}
