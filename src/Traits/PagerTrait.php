<?php

declare(strict_types=1);

namespace Evirma\Bundle\CoreBundle\Traits;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Evirma\Bundle\EssentialsBundle\Pager\Adapter\PagerDoctrineORMAdapter;
use Evirma\Bundle\EssentialsBundle\Pager\Adapter\PagerFixedAdapter;
use Evirma\Bundle\EssentialsBundle\Pager\Pager;

trait PagerTrait
{
    public function createArrayPager(int|null $page, int $perPage, array $items, int $itemsCount): Pager
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? $perPage : 100;

        $itemsCount = min($itemsCount, $perPage * 100);

        return (new Pager(new PagerFixedAdapter($itemsCount, $items)))
            ->setPerPage($perPage)
            ->setPage($page);
    }

    public function createNoLimitArrayPager(int|null $page, int $perPage, array $items, int $itemsCount): Pager
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? $perPage : 100;

        return (new Pager(new PagerFixedAdapter($itemsCount, $items)))
            ->setPerPage($perPage)
            ->setPage($page);
    }

    public function createQueryPager(Query|QueryBuilder $query, int|null $page, int $perPage = 30): Pager
    {
        $page = $page > 0 ? (int)$page : 1;
        $perPage = $perPage > 0 ? $perPage : 100;

        return (new Pager(new PagerDoctrineORMAdapter($query)))
            ->setPerPage($perPage)
            ->setPage($page);
    }
}
