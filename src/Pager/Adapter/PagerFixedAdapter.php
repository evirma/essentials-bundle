<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Pager\Adapter;

/**
 * Adapter which returns a fixed data set.
 *
 * Best used when you need to do a custom paging solution and don't want to implement a full adapter for a one-off use case.
 */
class PagerFixedAdapter implements PagerAdapterInterface
{
    public function __construct(private int $count, private iterable $results)
    {
    }

    public function count(): int
    {
        return $this->count;
    }

    public function getItems(int $offset, int $length): iterable
    {
        return $this->results;
    }
}
