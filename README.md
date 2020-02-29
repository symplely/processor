# Processor

[![Build Status](https://travis-ci.org/symplely/processor.svg?branch=master)](https://travis-ci.org/symplely/processor)[![Build status](https://ci.appveyor.com/api/projects/status/nao2cjdlx1n9ka28/branch/master?svg=true)](https://ci.appveyor.com/project/techno-express/processor-hrjtw/branch/master)[![codecov](https://codecov.io/gh/symplely/processor/branch/master/graph/badge.svg)](https://codecov.io/gh/symplely/processor)[![Codacy Badge](https://api.codacy.com/project/badge/Grade/77f00be68e664239a7dadfd4892c796b)](https://www.codacy.com/app/techno-express/processor?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=symplely/processor&amp;utm_campaign=Badge_Grade)[![Maintainability](https://api.codeclimate.com/v1/badges/a36bf7181cbefb6a0038/maintainability)](https://codeclimate.com/github/symplely/processor/maintainability)

An simply __process manager__ wrapper API for [symfony/process](https://github.com/symfony/process) to _execute_ and _manage_ **sub-processes**.

It's an alternative to pcntl-extension, when not installed. This is part of our [symplely/coroutine](https://github.com/symplely/coroutine) package for handling any **blocking i/o** process not handle by [**Coroutine**](https://github.com/symplely/coroutine) natively.

The library is to provide an easy to use API to control/manage sub processes for windows OS, and other systems, without any additional software extensions installed.

## Installation

```cmd
composer require symplely/processor
```

## Usage

```php
include 'vendor/autoload.php';

use Async\Processor\Processor;

// To set the path to PHP executable for child process
Processor::phpPath('/some/path/version-7.3/bin/php');

$process = \spawn($function, $timeout, $channel)
// Or
$process = Processor::create(function () use ($thing) {
    // Do a thing
    }, $timeout, $channel)
    ->then(function ($output) {
        // Handle success
    })->catch(function (\Throwable $exception) {
        // Handle exception
});

\spawn_run($process);
// Or
$process->run();

// Second option can be used to set to display child output, default is false
\spawn_run($process, true);
// Or
$process->displayOn()->run();
```

## Channel - Transfer messages between a Process

```php
include 'vendor/autoload.php';

use Async\Processor\Channel;
use Async\Processor\ChannelInterface;

$ipc = new Channel();

$process = spawn(function (ChannelInterface $channel) {
    $channel->write('ping'); // same as echo 'ping' or echo fwrite(STDOUT, 'ping')
    echo $channel->read(); // same as echo fgets(STDIN);
    echo $channel->read();
    }, 300, $ipc)
        ->progress(function ($type, $data) use ($ipc) {
            if ('ping' === $data) {
                $ipc->send('pang' . \PHP_EOL);
            } elseif (!$ipc->isClosed()) {
                $ipc->send('pong' . \PHP_EOL);
                    ->close();
            }
        });

$ipc->setup($process)
\spawn_run($process);

echo \spawn_output($process); // pingpangpong
// Or
echo $ipc->receive(); // pingpangpong
```

## Event hooks

When creating asynchronous processes, you'll get an instance of `LauncherInterface` returned.
You can add the following event hooks on a process.

```php
$process = spawn($function, $timeout, $channel)
// Or
$process = Processor::create(function () {
        // The second argument is optional, Defaults 300.
        // it sets The maximum amount of time a process may take to finish in seconds
        // The third is optional input pipe to pass to subprocess
    }, int $timeout = 300 , $input = null)
    ->then(function ($output) {
        // On success, `$output` is returned by the process.
    })
    ->catch(function ($exception) {
        // When an exception is thrown from within a process, it's caught and passed here.
    })
    ->timeout(function () {
        // When an time is reached, it's caught and passed here.
    })
    ->progress(function ($type, $data) {
        // A IPC like gateway: `$type, $data` is returned by the process progressing, it's producing output.
        // This can be use as a IPC handler for real time interaction.
    });
```

There also `->done`, part of `->then()` extended callback method.

```php
->done(function ($result) {
    // On success, `$result` is returned by the process or callable you passed to the queue.
});
->then(function ($resultOutput) {
        //
    }, function ($catchException) {
        //
    }, function ($progressOutput) {
        //
    }
);

// To turn on to display child output.
->displayOn();

// Stop displaying child output.
->displayOff();

// To display child output, only by third party means once turned on.
->display();

// Processes can be retried.
->restart();
->run();
```

## Error handling

If an `Exception` or `Error` is thrown from within a child process, it can be caught per process by specifying a callback in the `->catch()` method.

If there's no error handler added, the error will be thrown in the parent process when calling `spawn_run()` or `$process->run()`.

If the child process would unexpectedly stop without throwing an `Throwable`, the output written to `stderr` will be wrapped and thrown as `Async\Processor\ProcessorError` in the parent process.

## Contributing

Contributions are encouraged and welcome; I am always happy to get feedback or pull requests on Github :) Create [Github Issues](https://github.com/symplely/processor/issues) for bugs and new features and comment on the ones you are interested in.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
