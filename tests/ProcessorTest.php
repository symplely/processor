<?php

namespace Async\Tests;

use InvalidArgumentException;
use Async\Processor\Processor;
use Async\Processor\ProcessorError;
use PHPUnit\Framework\TestCase;

class ProcessorTest extends TestCase
{
    public function testIt_can_handle_success()
    {
        $counter = 0;

        $process = Processor::create(function () {
            return 2;
        })->then(function (int $output) use (&$counter) {
            $counter = $output;
        });

        await_spawn($process);
        $this->assertTrue($process->isSuccessful());

        $this->assertEquals(2, $counter);
    }

    public function testIt_can_handle_success_yield()
    {
        $counter = 0;

        $process = spawn(function () {
            return 2;
        })->then(function (int $output) use (&$counter) {
            $counter = $output;
        });

        $pause = $process->yielding();
        $this->assertEquals(0, $counter);

        $this->assertTrue($pause instanceof \Generator);
        $this->assertFalse($process->isSuccessful());

        $this->assertNull($pause->current());
        $this->assertTrue($process->isSuccessful());

        $this->assertEquals(2, $counter);
    }

    public function testIt_can_handle_timeout()
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

    public function testIt_can_handle_timeout_yield()
    {
        $counter = 0;

        $process = Processor::create(function () {
            sleep(1000);
        }, 1)->timeout(function () use (&$counter) {
            $counter += 1;
        });

        $pause = $process->yielding();
        $this->assertFalse($process->isTimedOut());

        $this->assertNull($pause->current());
        $this->assertTrue($process->isTimedOut());
        $this->assertEquals(1, $counter);
    }

    public function testStart()
    {
        $process = Processor::create(function () {
            usleep(1000);
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
        $this->assertSame('1', $p->getOutput());
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
        $this->assertEquals(3, preg_match_all('/foo/', $p->getOutput(), $matches));
    }

    public function testGetErrorOutput()
    {
        $p = spawn(function () {
            $n = 0;
            while ($n < 3) {
                file_put_contents('php://stderr', 'ERROR');
                $n++;
            }
        })->catch(function (ProcessorError $error) {
            $this->assertEquals(3, preg_match_all('/ERROR/', $error->getMessage(), $matches));
        });

        await_spawn($p);
    }

    public function testGetErrorOutputYield()
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

        $pause = $p->yielding();
        $this->assertNull($pause->current());
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
        $this->assertStringContainsString('foo', $process->getOutput());
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

    public function testPhpPathExecutable()
    {
        $executable = '/opt/path/that/can/never/exist/for/testing/bin/php';
        $notFoundError = '';
        $result = null;

        // test with custom executable
        Processor::phpPath($executable);
        $process = Processor::create(function () {
            return true;
        })->then(function ($_result) use (&$result) {
            $result = $_result;
        })->catch(function ($error) use (&$result, &$notFoundError) {
            $result = false;
            $notFoundError = $error->getMessage();
        });

        if ('\\' === \DIRECTORY_SEPARATOR) {
            $pathCheck = 'The system cannot find the path specified.';
        } else {
            $pathCheck = $executable;
        }

        $process->run();
        $this->assertEquals(false, $result);
        $this->assertRegExp("%{$pathCheck}%", $notFoundError);

        // test with default executable (reset for further tests)
        Processor::phpPath('php');
        $process = Processor::create(function () {
            return 'reset';
        })->then(function ($_result) use (&$result) {
            $result = $_result;
        });

        $process->run();
        $this->assertEquals('reset', $result);

        // test with default executable
        $process = Processor::create(function () {
            return 'default';
        })->then(function ($_result) use (&$result) {
            $result = $_result;
        });

        $process->run();
        $this->assertEquals('default', $result);
    }
}
