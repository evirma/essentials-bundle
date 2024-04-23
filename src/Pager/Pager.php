<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Pager;

use Evirma\Bundle\EssentialsBundle\Pager\Adapter\PagerAdapterInterface;

/**
 * @implements \IteratorAggregate<int, mixed>
 */
class Pager implements \Countable, \IteratorAggregate, \JsonSerializable
{
    private PagerAdapterInterface $adapter;
    private int $perPage = 10;
    private int $page = 1;
    private ?int $count = null;
    private ?iterable $items;

    public function __construct(PagerAdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->items = null;
    }

    public function getAdapter(): PagerAdapterInterface
    {
        return $this->adapter;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setPerPage(int $perPage): Pager
    {
        $this->perPage = $perPage;
        return $this;
    }

    public function getPage(): int
    {
        return $this->page;
    }
    
    public function setPage(int $page = 1): Pager
    {
        $this->page = $page;
        return $this;
    }

    public function count(): int
    {
        if (null === $this->count) {
            $this->count = $this->getAdapter()->count();
        }

        return $this->count;
    }

    public function getItems(): ?iterable
    {
        if (null === $this->items) {
            $this->items = $this->getItemsFromAdapter();
        }

        return $this->items;
    }

    private function getItemsFromAdapter(): iterable
    {
        $offset = ($this->getPage() - 1) * $this->getPerPage();
        $length = $this->getPerPage();

        return $this->adapter->getItems($offset, $length);
    }

    public function getPages(): int
    {
        $pages = (int) ceil($this->count() / $this->getPerPage());

        if (0 === $pages) {
            return 1;
        }

        return $pages;
    }

    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    public function getPreviousPage(): int
    {
        if (!$this->hasPreviousPage()) {
            throw new \LogicException('There is no previous page.');
        }

        return $this->page - 1;
    }

    public function hasNextPage(): bool
    {
        return $this->page < $this->getPages();
    }

    public function getNextPage(): int
    {
        if (!$this->hasNextPage()) {
            throw new \LogicException('There is no next page.');
        }

        return $this->page + 1;
    }

    public function getIterator(): \ArrayIterator|\Iterator
    {
        $results = $this->getItems();

        if ($results instanceof \Iterator) {
            return $results;
        }

        if ($results instanceof \IteratorAggregate) {
            return $results->getIterator();
        }

        return new \ArrayIterator((array)$results);
    }

    public function jsonSerialize(): ?iterable
    {
        $results = $this->getItems();

        if ($results instanceof \Traversable) {
            return iterator_to_array($results);
        }

        return $results;
    }

    /**
     * @deprecated
     * @return int
     */
    public function getNbPages(): int
    {
        return $this->count();
    }

    /**
     * @deprecated
     * @return int
     */
    public function getNbResults(): int
    {
        return $this->count();
    }

    /**
     * @deprecated
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->getPage();
    }
}
