<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Pager\Adapter;

interface PagerAdapterInterface
{
    public function count(): int;
    public function getItems(int $offset, int $length): iterable;
}
