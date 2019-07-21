<?php

namespace Async\Processor;

use Closure;
use Async\Processor\Launcher;
use Async\Processor\Process;
use Async\Processor\ProcessInterface;
use Opis\Closure\SerializableClosure;

class Processor
{
    /** @var bool */
    protected static $isInitialized = false;

    /** @var string */
    protected static $autoload;

    /** @var string */
    protected static $containerScript;

    protected static $currentId = 0;

    protected static $myPid = null;

    private function __construct()
    {
    }

    public static function init(string $autoload = null)
    {            
        if (! $autoload) {
            if (!defined('_DS'))
                define('_DS', DIRECTORY_SEPARATOR);

            $existingAutoloadFiles = \array_filter([
                __DIR__._DS.'..'._DS.'..'._DS.'..'._DS.'..'._DS.'autoload.php',
                __DIR__._DS.'..'._DS.'..'._DS.'..'._DS.'autoload.php',
                __DIR__._DS.'..'._DS.'..'._DS.'vendor'._DS.'autoload.php',
                __DIR__._DS.'..'._DS.'vendor'._DS.'autoload.php',
                __DIR__._DS.'vendor'._DS.'autoload.php',
                __DIR__._DS.'..'._DS.'..'._DS.'..'._DS.'vendor'._DS.'autoload.php',
            ], function (string $path) {
                return \file_exists($path);
            });

            $autoload = \reset($existingAutoloadFiles);
        }

        self::$autoload = $autoload;
        self::$containerScript = __DIR__.DIRECTORY_SEPARATOR.'Container.php';

        self::$isInitialized = true;
    }

    /**
     * Create a sub process for callable, cmd script, or any binary application.
     *
     * @param mixed $task
     *
     * @return ProcessInterface
     */
    public static function create($task, int $timeout = 300, $input = null): ProcessInterface
    {
        if (! self::$isInitialized) {
            self::init();
        }

        if (\is_callable($task) && !\is_string($task) && !\is_array($task)) {  
            $process = new Process([
                'php',
                self::$containerScript,
                self::$autoload,
                self::encodeTask($task),
            ], null, null, $input, $timeout);
        } elseif (\is_string($task)) {
            $process = Process::fromShellCommandline($task, null, null, $input, $timeout);
        } else {
            $process = new Process($task, null, null, $input, $timeout);
        }

        return Launcher::create($process, self::getId(), $timeout);
	}
	
    /**
     * Daemon a process to run in the background.
     *
     * @param string $task daemon
     *
     * @return ProcessInterface
     */
    public static function daemon($task, $channel = null): ProcessInterface
    {
        if (\is_string($task)) {
			$shadow = (('\\' === \DIRECTORY_SEPARATOR) ? 'start /b ' : 'nohup ').$task;
        } else {
			$shadow[] = ('\\' === \DIRECTORY_SEPARATOR) ? 'start /b' : 'nohup';
			$shadow[] = $task;
        }
				
        return Processor::create($shadow, 0, $channel);
    }
	
    /**
     * @param callable $task
     *
     * @return string
     */
    public static function encodeTask($task): string
    {
        if ($task instanceof Closure) {
            $task = new SerializableClosure($task);
        }

        return \base64_encode(\Opis\Closure\serialize($task));
    }

    public static function decodeTask(string $task)
    {
        return \Opis\Closure\unserialize(\base64_decode($task));
    }

    protected static function getId(): string
    {
        if (self::$myPid === null) {
            self::$myPid = \getmypid();
        }

        self::$currentId += 1;

        return (string) self::$currentId.(string) self::$myPid;
    }
}
