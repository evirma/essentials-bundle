<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Pager\Adapter;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Exception;
use JetBrains\PhpStorm\Pure;
use function count;

class PagerDoctrineORMAdapter implements PagerAdapterInterface
{
    private Paginator $paginator;

    /**
     * @param Query|QueryBuilder $query
     * @param bool               $fetchJoinCollection Whether the query joins a collection (true by default)
     * @param bool|null          $useOutputWalkers    Flag indicating whether output walkers are used in the paginator
     */
    public function __construct(Query|QueryBuilder $query, bool $fetchJoinCollection = true, bool $useOutputWalkers = null)
    {
        $this->paginator = new Paginator($query, $fetchJoinCollection);
        $this->paginator->setUseOutputWalkers($useOutputWalkers);
    }

    #[Pure] public function getQuery(): Query
    {
        return $this->paginator->getQuery();
    }

    /**
     * Returns whether the query joins a collection.
     *
     * @return bool
     */
    #[Pure] public function getFetchJoinCollection(): bool
    {
        return $this->paginator->getFetchJoinCollection();
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->paginator);
    }

    /**
     * @param int $offset
     * @param int $length
     * @return iterable
     * @throws Exception
     */
    public function getItems(int $offset, int $length): iterable
    {
        $this->paginator->getQuery()
            ->setFirstResult($offset)
            ->setMaxResults($length);

        return $this->paginator->getIterator();
    }
}
