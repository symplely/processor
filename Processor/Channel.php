<?php

declare(strict_types=1);

namespace Async\Processor;

use Async\Processor\Process;
use Async\Processor\ChannelInterface;

/**
 * A channel is used to transfer messages between a `Process` as a IPC pipe.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class Channel implements ChannelInterface
{
    /**
     * @var callable|null
     */
    private $whenDrained = null;
    private $input = [];
    private $open = true;

    /**
     * IPC handle
     *
     * @var Object
     */
    protected $channel = null;

    public function setup(Object $handle): ChannelInterface
    {
        $this->channel = $handle;

        return $this;
    }

    public function then(callable $whenDrained = null): ChannelInterface
    {
        $this->whenDrained = $whenDrained;

        return $this;
    }

    public function close(): ChannelInterface
    {
        $this->open = false;

        return $this;
    }

    public function isClosed(): bool
    {
        return !$this->open;
    }

    public function send($message): ChannelInterface
    {
        if (null === $message) {
            return $this;
        }

        if ($this->isClosed()) {
            throw new \RuntimeException(\sprintf('%s is closed', static::class));
        }

        $this->input[] = self::validateInput(__METHOD__, $message);

        return $this;
    }

    public function receive()
    {
        return $this->channel->getOutput();
    }

    /**
     * @codeCoverageIgnore
     */
    public function read(int $length = 0): string
    {
        if ($length === 0)
            return \trim(\fgets(\STDIN));

        return \fread(\STDIN, $length);
    }

    /**
     * @codeCoverageIgnore
     */
    public function write($message): int
    {
        return \fwrite(\STDOUT, (string) $message);
    }

    /**
     * @codeCoverageIgnore
     */
    public function error($message): int
    {
        return \fwrite(\STDERR, (string) $message);
    }

    /**
     * @codeCoverageIgnore
     */
    public function passthru(): int
    {
        return \stream_copy_to_stream(\STDIN, \STDOUT);
    }

    public function getIterator()
    {
        $this->open = true;

        while ($this->open || $this->input) {
            if (!$this->input) {
                yield '';
                continue;
            }

            $current = \array_shift($this->input);
            if ($current instanceof \Iterator) {
                yield from $current;
            } else {
                yield $current;
            }

            $whenDrained = $this->whenDrained;
            if (!$this->input && $this->open && (null !== $whenDrained)) {
                $this->send($whenDrained($this));
            }
        }
    }

    /**
     * Validates and normalizes a Process input.
     *
     * @param string $caller The name of method call that validates the input
     * @param mixed  $input  The input to validate
     *
     * @return mixed The validated input
     *
     * @throws \InvalidArgumentException In case the input is not valid
     */
    protected static function validateInput(string $caller, $input)
    {
        if (null !== $input) {
            if (\is_resource($input)) {
                return $input;
            }

            if (\is_string($input)) {
                return $input;
            }

            if (\is_scalar($input)) {
                return (string) $input;
            }

            // @codeCoverageIgnoreStart
            if ($input instanceof Process) {
                return $input->getIterator($input::ITER_SKIP_ERR);
            }

            if ($input instanceof \Iterator) {
                return $input;
            }
            if ($input instanceof \Traversable) {
                return new \IteratorIterator($input);
            }

            throw new \InvalidArgumentException(\sprintf('%s only accepts strings, Traversable objects or stream resources.', $caller));
        }

        return $input;
        // @codeCoverageIgnoreEnd
    }
}
