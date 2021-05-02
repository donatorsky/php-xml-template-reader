<?php
declare(strict_types=1);

namespace Donatorsky\XmlTemplate\Reader\Models;

use SplDoublyLinkedList;

/**
 * @internal
 *
 * @template TValue
 * @extends \SplDoublyLinkedList<TValue>
 */
class Collection extends SplDoublyLinkedList
{
    /**
     * @param array<TValue> $data
     */
    public function __construct(array $data = [])
    {
        foreach ($data as $item) {
            $this->push($item);
        }
    }

    /**
     * @return array<TValue>
     */
    public function toArray(): array
    {
        return \iterator_to_array($this);
    }
}
