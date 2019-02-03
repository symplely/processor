<?php

namespace Async\Tests;

use function Opis\Closure\serialize;
use Opis\Closure\SerializableClosure;
use Symfony\Component\Process\Process;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    /** @test */
    public function it_can_run()
	{
		if (!defined('_DS'))
			define('_DS', DIRECTORY_SEPARATOR);
        $bootstrap = __DIR__._DS.'..'._DS.'Processor'._DS.'Container.php';

        $autoload = __DIR__._DS.'..'._DS.'vendor'._DS.'autoload.php';

        $serializedClosure = \base64_encode(serialize(new SerializableClosure(function () {
            echo 'child';
        })));
        $process = new Process("php {$bootstrap} {$autoload} {$serializedClosure}");
		
        $process->start();

        $process->wait();

        $this->assertContains('child', $process->getOutput());
    }
}
