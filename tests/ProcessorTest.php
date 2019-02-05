<?php

namespace Async\Tests;

use InvalidArgumentException;
use Async\Processor\Processor;
use Async\Processor\ProcessorError;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{	
    /** @test */
    public function it_can_handle_success()
    {
        $counter = 0;

        $process = Processor::create(function () {
            return 2;
        })->then(function (int $output) use (&$counter) {
            $counter = $output;
        });
	
        $process->run();
        $this->assertTrue($process->isSuccessful());

        $this->assertEquals(2, $counter);
    }

    /** @test */
    public function it_can_handle_timeout()
    {
        $counter = 0;

        $process = Processor::create(function () {
            sleep(1000);
        }, 1)->timeout(function () use (&$counter) {
            $counter += 1;
        });

        $process->run();
        $this->assertTrue($process->isTimedOut());

        $this->assertEquals(1, $counter);
    }

    public function testStart()
    {
        $process = Processor::create(function () {
            usleep(1000);
        });
        $this->assertFalse($process->isRunning());
        $this->assertFalse($process->isTerminated());
        $process->start();
        $this->assertTrue($process->isRunning());
        $this->assertFalse($process->isTerminated());
        $process->wait();
        $this->assertFalse($process->isRunning());
        $this->assertTrue($process->isTerminated());
    }

    public function testGetOutputShell()
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            // see http://stackoverflow.com/questions/7105433/windows-batch-echo-without-new-line
            $p = Processor::create('echo | set /p dummyName=1');
        } else {
            $p = Processor::create('printf 1');
        }

        $p->run();
        $this->assertSame('1', $p->getErrorOutput());
    }

    public function testGetOutput()
    {
        $p = Processor::create(function () {
			$n = 0; 
			while ($n < 3) { 
				echo "foo"; 
				$n++; 
			}
		});

        $p->run();
        $this->assertEquals(3, preg_match_all('/foo/', $p->getErrorOutput(), $matches));
    }

    public function testGetErrorOutput()
    {
        $p = Processor::create(function () {
			$n = 0; 
			while ($n < 3) { 
				file_put_contents('php://stderr', 'ERROR'); 
				$n++; 
			}
		})->catch(function (ProcessorError $error) {
            $this->assertEquals(3, preg_match_all('/ERROR/', $error->getMessage(), $matches));
        });

        $p->run();
    }

    public function testRestart()
    {
        $process1 = Processor::create(function () {
			return getmypid();
		});
        $process1->run();
        $process2 = $process1->restart();

        $process2->wait(); // wait for output

        // Ensure that both processed finished and the output is numeric
        $this->assertFalse($process1->isRunning());
        $this->assertFalse($process2->isRunning());

        // Ensure that restart returned a new process by check that the output is different
        $this->assertNotEquals($process1->getOutput(), $process2->getOutput());
    }
	
    public function testWaitReturnAfterRunCMD()
    {
        $process = Processor::create('echo foo');
        $process->run();
        $this->assertStringContainsString('foo', $process->getErrorOutput());
    }

    public function testStop()
    {
        $process = Processor::create(function () {
            sleep(1000);
        })->start();
        $this->assertTrue($process->isRunning());
        $process->stop();
        $this->assertFalse($process->isRunning());
    }

    public function testIsSuccessfulCMD()
    {
        $process = Processor::create('echo foo');
        $process->run();
        $this->assertTrue($process->isSuccessful());
    }
	
    public function testGetPid()
    {
        $process = Processor::create(function () {
            sleep(1000);
        })->start();
        $this->assertGreaterThan(0, $process->getPid());
        $process->stop();
    }
}
