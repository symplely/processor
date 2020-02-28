<?php

declare(strict_types=1);

use Async\Processor\Channel;
use Async\Processor\Processor;
use Async\Processor\LauncherInterface;

if (!\function_exists('spawn')) {
  /**
   * Create an process by shell command or callable.
   *
   * @param shell|callable $shellCallable
   * @param int $timeout
   * @param Channel|mixed|null $processChannel Set the input content as `stream`, `resource`, `scalar`,
   *  `Traversable`, or `null` for no input
   * - The content will be passed to the underlying process standard input.
   *
   * @return LauncherInterface
   * @throws LogicException In case the process is running
   */
  function spawn($shellCallable, int $timeout = 300, $processChannel = null): LauncherInterface
  {
    return Processor::create($shellCallable, $timeout, $processChannel);
  }

  /**
   * Start the process and wait to terminate, and return results in index array.
   */
  function spawn_run(LauncherInterface $process, bool $displayOutput = false)
  {
    return $displayOutput ? $process->displayOn()->run() : $process->run();
  }
}
