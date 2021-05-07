<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Models;

use ArrayAccess;
use ArrayIterator;
use Countable;
use IteratorAggregate;
use OutOfBoundsException;

/**
 * @internal
 *
 * @template TValue
 * @implements \ArrayAccess<string,TValue>
 * @implements \IteratorAggregate<string,TValue>
 */
class Map implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @var array<string,TValue>
     */
    private array $data;

    /**
     * @param array<string,TValue> $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->data);
    }

    /**
     * @throws \OutOfBoundsException When element $name does not exist
     *
     * @return TValue
     */
    public function get(string $name)
    {
        if (!isset($this->data[$name])) {
            throw new OutOfBoundsException(sprintf('The element with name "%s" does not exist', $name));
        }

        return $this->data[$name];
    }

    /**
     * @return TValue|null
     */
    public function first()
    {
        return reset($this->data) ?: null;
    }

    /**
     * @param TValue $value
     *
     * @return $this
     */
    public function set(string $name, $value): self
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * @return $this
     */
    public function remove(string $name): self
    {
        unset($this->data[$name]);

        return $this;
    }

    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * @return array<string,TValue>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    public function offsetExists($offset): bool
    {
        return $this->has($offset);
    }

    /**
     * @param string $offset
     *
     * @return TValue
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * @param string $offset
     * @param TValue $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->remove($offset);
    }

    /**
     * @return ArrayIterator<string,TValue>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }

    public function count(): int
    {
        return \count($this->data);
    }
}
