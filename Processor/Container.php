<?php

declare(strict_types=1);

use Async\Processor\Processor;
use Async\Processor\SerializableException;

try {
    $autoload = $argv[1] ?? null;
    $serializedClosure = $argv[2] ?? null;

    if (!$autoload) {
        throw new \InvalidArgumentException('No autoload provided in child process.');
    }

    if (!\file_exists($autoload)) {
        throw new \InvalidArgumentException("Could not find autoload in child process: {$autoload}");
    }

    if (!$serializedClosure) {
        throw new \InvalidArgumentException('No valid closure was passed to the child process.');
    }

    require_once $autoload;

    $task = Processor::decodeTask($serializedClosure);

    $output = \call_user_func($task);

    $serializedOutput = \base64_encode(\serialize($output));

    \fwrite(\STDOUT, $serializedOutput);

    exit(0);
} catch (\Throwable $exception) {
    if (!defined('_DS'))
        define('_DS', DIRECTORY_SEPARATOR);
    require_once __DIR__ . _DS . 'SerializableException.php';

    $output = new SerializableException($exception);

    \fwrite(\STDERR, \base64_encode(\serialize($output)));

    exit(1);
}
