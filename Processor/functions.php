<?php

declare(strict_types=1);

use Async\Processor\Processor;
use Async\Processor\LauncherInterface;

if (!\function_exists('spawn')) {
  /**
   * Create an process by shell command or callable.
   *
   * @param shell|callable $shellCallable
   * @param int $timeout
   * @param mixed $processChannel
   *
   * @return LauncherInterface
   */
  function spawn($shellCallable, int $timeout = 300, $processChannel = null): LauncherInterface
  {
    return Processor::create($shellCallable, $timeout, $processChannel);
  }

  function spawn_run(LauncherInterface $process)
  {
    return $process->run();
  }
}
