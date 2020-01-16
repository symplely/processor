<?php

namespace Async\Tests;

use Opis\Closure\SerializableClosure;
use Async\Processor\Process;
use PHPUnit\Framework\TestCase;

class ContainerTest extends TestCase
{
    public function testIt_can_run()
	{
		if (!defined('_DS'))
			define('_DS', DIRECTORY_SEPARATOR);
        $bootstrap = __DIR__._DS.'..'._DS.'Processor'._DS.'Container.php';

        $autoload = __DIR__._DS.'..'._DS.'vendor'._DS.'autoload.php';

        $serializedClosure = \base64_encode(\Opis\Closure\serialize(new SerializableClosure(function () {
            echo 'child';
        })));
        $process = new Process(explode(" ", "php {$bootstrap} {$autoload} {$serializedClosure}"));

        $process->start();

        $process->wait();

        $this->assertStringContainsString('child', $process->getOutput());
    }
}
