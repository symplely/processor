<?php

declare(strict_types=1);

namespace Async\Processor;

use Async\Processor\Process;

/**
 * A channel is used to transfer messages between a `Process` as a IPC pipe.
 *
 * Provides a way to continuously write to the input of a Process until the channel is closed.
 *
 * Send and receive operations are (async) blocking by default, they can be used
 * to synchronize tasks.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class Channel implements \IteratorAggregate
{
    /**
     * @var callable|null
     */
    private $whenDrained = null;
    private $input = [];
    private $open = true;

    /**
     * Sets a callback that is called when the channel write buffer becomes drained.
     */
    public function then(callable $whenDrained = null)
    {
        $this->whenDrained = $whenDrained;
    }

    /**
     * Close the channel.
     */
    public function close(): void
    {
        $this->open = false;
    }

    /**
     * Check if the channel has been closed yet.
     */
    public function isClosed(): bool
    {
        return !$this->open;
    }

    /**
     * Send a message into the IPC channel.
     *
     * @param resource|string|int|float|bool|\Traversable|null $message The input message
     * @throws \RuntimeException When attempting to send a message into a closed channel.
     */
    public function send($message)
    {
        if (null === $message) {
            return;
        }

        if ($this->isClosed()) {
            // @codeCoverageIgnoreStart
            throw new \RuntimeException(\sprintf('%s is closed', static::class));
            // @codeCoverageIgnoreEnd
        }

        $this->input[] = self::validateInput(__METHOD__, $message);
    }

    /**
     * Wait to receive a message from the channel `STDIN`.
     *
     * @param int $length will read to `EOL` if not set.
     *
     * @codeCoverageIgnore
     */
    public function receive(int $length = 0)
    {
        if ($length === 0)
            return \trim(\fgets(\STDIN));

        return \fread(\STDIN, $length);
    }

    /**
     * Write a message to the channel `STDOUT`.
     *
     * @param mixed $message
     *
     * @codeCoverageIgnore
     */
    public function write($message)
    {
        return \fwrite(\STDOUT, $message);
    }

    /**
     * @return \Traversable
     */
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
    public static function validateInput(string $caller, $input)
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
    }
}
