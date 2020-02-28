<?php

declare(strict_types=1);

namespace Async\Processor;

use Closure;
use Async\Processor\Launcher;
use Async\Processor\Process;
use Async\Processor\LauncherInterface;
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

    /** @var string */
    protected static $executable = 'php';

    /**
     * @codeCoverageIgnore
     */
    private function __construct()
    { }

    public static function init(string $autoload = null)
    {
        if (!$autoload) {
            $existingAutoloadFiles = \array_filter([
                __DIR__ . \_DS . '..' . \_DS . '..' . \_DS . '..' . \_DS . '..' . \_DS . 'autoload.php',
                __DIR__ . \_DS . '..' . \_DS . '..' . \_DS . '..' . \_DS . 'autoload.php',
                __DIR__ . \_DS . '..' . \_DS . '..' . \_DS . 'vendor' . \_DS . 'autoload.php',
                __DIR__ . \_DS . '..' . \_DS . 'vendor' . \_DS . 'autoload.php',
                __DIR__ . \_DS . 'vendor' . \_DS . 'autoload.php',
                __DIR__ . \_DS . '..' . \_DS . '..' . \_DS . '..' . \_DS . 'vendor' . \_DS . 'autoload.php',
            ], function (string $path) {
                return \file_exists($path);
            });

            $autoload = \reset($existingAutoloadFiles);
        }

        self::$autoload = $autoload;
        self::$containerScript = __DIR__ . \_DS . 'Container.php';

        self::$isInitialized = true;
    }

    /**
     * Create a sub process for callable, cmd script, or any binary application.
     *
     * @param mixed $task The command to run and its arguments
     * @param int|float|null $timeout The timeout in seconds or null to disable
     * @param mixed|null $input Set the input content as `stream`, `resource`, `scalar`, `Traversable`, or `null` for no input
     * - The content will be passed to the underlying process standard input.
     *
     * @return LauncherInterface
     * @throws LogicException In case the process is running
     */
    public static function create($task, int $timeout = 300, $input = null): LauncherInterface
    {
        if (!self::$isInitialized) {
            self::init();
        }

        if (\is_callable($task) && !\is_string($task) && !\is_array($task)) {
            $process = new Process([
                self::$executable,
                self::$containerScript,
                self::$autoload,
                self::encodeTask($task),
            ], null, null, $input, $timeout);
        } elseif (\is_string($task)) {
            $process = Process::fromShellCommandline($task, null, null, $input, $timeout);
        } else {
            // @codeCoverageIgnoreStart
            $process = new Process($task, null, null, $input, $timeout);
            // @codeCoverageIgnoreEnd
        }

        return Launcher::create($process, (int) self::getId(), $timeout);
    }

    /**
     * @param string $executable
     */
    public static function phpPath(string $executable): void
    {
        self::$executable = $executable;
    }

    /**
     * Daemon a process to run in the background.
     *
     * @param string $task daemon
     *
     * @return LauncherInterface
     *
     * @codeCoverageIgnore
     */
    public static function daemon($task, $channel = null): LauncherInterface
    {
        if (\is_string($task)) {
            $shadow = (('\\' === \DIRECTORY_SEPARATOR) ? 'start /b ' : 'nohup ') . $task;
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


    /**
     * @codeCoverageIgnore
     */
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

        return (string) self::$currentId . (string) self::$myPid;
    }
}
