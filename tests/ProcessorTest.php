<?php

namespace Async\Tests;

use InvalidArgumentException;
use Async\Parallel\Parallel;
use PHPUnit\Framework\TestCase;

class ParallelTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
    }
	
    /** @test */
    public function it_can_handle_success()
    {
        $counter = 0;

        $process = Processor::create(function () {
            return 2;
        })->then(function (int $output) use (&$counter) {
            $counter = $output;
        });
	
        $process->wait();
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

        $process->wait();
        $this->assertTrue($process->isTimedOut());

        $this->assertEquals(1, $counter);
    }

    public function testStart()
    {
        $process = Processor::create(function () {
            usleep(100000);
        });
        $this->assertFalse($process->isRunning());
        $this->assertFalse($process->isTimedOut());
        $this->assertFalse($process->isTerminated());
        $process->start();
        $this->assertTrue($process->isRunning());
        $this->assertFalse($process->isTimedOut());
        $this->assertFalse($process->isTerminated());
        $process->wait();
        $this->assertFalse($process->isRunning());
        $this->assertFalse($process->isTimedOut());
        $this->assertTrue($process->isTerminated());
    }

    public function testGetOutputShell()
    {
        if ('\\' === \DIRECTORY_SEPARATOR) {
            // see http://stackoverflow.com/questions/7105433/windows-batch-echo-without-new-line
            $p = Processor::make('echo | set /p dummyName=0');
        } else {
            $p = Processor::make('printf 0');
        }

        $p->wait();
        $this->assertSame('0', $p->getOutput());
    }

    public function testGetOutput()
    {
        $p = Processor::create(function () {
			$n = 0; 
			while ($n < 3) { 
				echo " foo "; 
				$n++; 
			}
		});

        $p->wait();
        $this->assertEquals(3, preg_match_all('/foo/', $p->getOutput(), $matches));
    }

    public function testGetErrorOutput()
    {
        $p = Processor::create(function () {
			$n = 0; 
			while ($n < 3) { 
				file_put_contents('php://stderr', 'ERROR'); 
				$n++; 
			}
		});

        $p->wait();
        $this->assertEquals(3, preg_match_all('/ERROR/', $p->getErrorOutput(), $matches));
    }

    public function testRestart()
    {
        $process1 = Processor::create(function () {
			echo getmypid();
		});
        $process1->wait();
        $process2 = $process1->restart();

        $process2->wait(); // wait for output

        // Ensure that both processed finished and the output is numeric
        $this->assertFalse($process1->isRunning());
        $this->assertFalse($process2->isRunning());
        $this->assertInternalType('numeric', $process1->getOutput());
        $this->assertInternalType('numeric', $process2->getOutput());

        // Ensure that restart returned a new process by check that the output is different
        $this->assertNotEquals($process1->getOutput(), $process2->getOutput());
    }
	
    public function testWaitReturnAfterRunCMD()
    {
        $process = Processor::make('echo foo');
        $result = $process->wait();
        $this->assertEquals('foo', $result);
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
        $process = Processor::make('echo foo');
        $process->wait();
        $this->assertTrue($process->isSuccessful());
    }
	
    public function testGetPid()
    {
        $process = Processor::create(function () {
            sleep(1000);
        })->start();
        $this->assertGreaterThan(0, $process->getPid());
        $process->stop(0);
    }
}
