<?php

namespace HappyCode\Blueprint;

use ArrayAccess;
use Exception;
use Iterator;
use ReturnTypeWillChange;

class RootCollection implements Iterator, ArrayAccess
{
    private int $position = 0;

    public function __construct(private readonly array $items)
    {}

    public function each(callable $fn): static
    {
        foreach ($this->items as $unitItem) {
            $fn($unitItem);
        }
        return $this;
    }

    public function toArray(): array {
        return $this->items;
    }

    /**
     * Iterator Implementations
     */

    public function rewind(): void
    {
        $this->position = 0;
    }

    #[ReturnTypeWillChange] public function current()
    {
        return $this->items[$this->position];
    }

    public function key(): int
    {
        return $this->position;
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function valid(): bool
    {
        return isset($this->items[$this->position]);
    }

    /**
     * ArrayAccess Implementations
     */

    /**
     * @throws Exception
     */
    public function offsetSet($offset, $value): void
    {
        throw new Exception("Cannot modify immutable collection");
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @throws Exception
     */
    public function offsetUnset($offset): void
    {
        throw new Exception("Cannot modify immutable collection");
    }

    #[ReturnTypeWillChange] public function offsetGet($offset)
    {
        return $this->offsetExists($offset) ? $this->items[$offset] : null;
    }
}