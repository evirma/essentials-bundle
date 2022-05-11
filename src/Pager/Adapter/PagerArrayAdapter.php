<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Pager\Adapter;

use function array_slice;
use function count;

class PagerArrayAdapter implements PagerAdapterInterface
{
    public function __construct(private readonly array $array)
    {
    }

    public function getArray(): array
    {
        return $this->array;
    }

    public function count(): int
    {
        return count($this->array);
    }

    public function getItems(int $offset, int $length): iterable
    {
        return array_slice($this->array, $offset, $length);
    }
}
