<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Traits;

use Evirma\Bundle\EssentialsBundle\Service\MemcacheService;
use JetBrains\PhpStorm\Pure;

trait CacheTrait
{
    protected MemcacheService $memcache;

    public static function addToPrefetchOnSet(bool $flag = true): bool
    {
        $currentValue = MemcacheService::$addToPrefetchOnSet;
        MemcacheService::$addToPrefetchOnSet = $flag;

        return $currentValue;
    }

    public static function clearPrefetchedData(): void
    {
        MemcacheService::$prefetchCacheData = [];
    }

    protected function getObjectCacheDecodedItem(mixed $object, string $cacheId, mixed $default = null, bool $cached = true): mixed
    {
        $start = 0;
        if (MemcacheService::$profilerEnable) {
            $start = microtime(true);
        }

        if ($result = $this->getCacheDecodedItem($cacheId, $default, $cached)) {
            if (is_array($result)) {
                $result = $object::factory($result);
            } else {
                $result = $default;
            }
        }

        if (MemcacheService::$profilerEnable) {
            @MemcacheService::$profiler['getObjectCacheDecodedItem']['count']++;
            @MemcacheService::$profiler['getObjectCacheDecodedItem']['keys'][$cacheId]++;

            if (!isset(MemcacheService::$profiler['getObjectCacheDecodedItem']['runtime'])) {
                MemcacheService::$profiler['getObjectCacheDecodedItem']['runtime'] = 0;
            }
            $runtime = microtime(true) - $start;
            MemcacheService::$profiler['getObjectCacheDecodedItem']['runtime'] += $runtime;
        }

        return $result;
    }

    protected function getObjectCacheDecodedList(mixed $object, string $cacheId, mixed $default = null, bool $cached = true): mixed
    {
        if ($result = $this->getCacheDecodedItem($cacheId, $default, $cached)) {
            if (is_array($result)) {
                foreach ($result as &$item) {
                    $item = $object::factory($item);
                }

                return $result;
            } else {
                return $default;
            }
        }

        return $result;
    }

    /**
     * @param array<string> $keys
     * @param mixed|null    $default
     * @param bool          $cached
     */
    protected function prefetchDecodedCache(array $keys, mixed $default = null, bool $cached = true): void
    {
        $result = $this->getMemcache()->getMultiple($keys, $default, $cached);

        if ($result && is_array($result)) {
            foreach ($result as $k => $v) {
                if ($v) {
                    MemcacheService::$prefetchCacheData[$k] = @json_decode($v, true);
                }
            }
        }
    }

    protected function getMemcache(): MemcacheService
    {
        return $this->memcache;
    }

    /**
     * @required
     * @param MemcacheService $memcache
     */
    public function setMemcache(MemcacheService $memcache): void
    {
        $this->memcache = $memcache;
    }

    protected function getCacheItem(string $cacheId, mixed $default = null, bool $cached = true): mixed
    {
        if (!$this->isCacheAllowed($cached)) {
            return $default;
        }

        if (MemcacheService::$profilerEnable) {
            @MemcacheService::$profiler['getCacheItem']['count']++;
            @MemcacheService::$profiler['getCacheItem']['keys'][$cacheId]++;
        }

        if (isset(MemcacheService::$prefetchCacheData[$cacheId]) && MemcacheService::$prefetchCacheData[$cacheId]) {
            $result = MemcacheService::$prefetchCacheData[$cacheId];
        } elseif ($result = $this->getMemcache()->get($cacheId, $default)) {
            return $result;
        }

        if (is_null($result)) {
            $result = $default;
        }

        return $result;
    }

    #[Pure] protected function isCacheAllowed(bool $cached = true): bool
    {
        return MemcacheService::isCacheAllowed($cached);
    }

    protected function getCacheMultiple(array $keys, mixed $default = false, bool $cached = true): mixed
    {
        return $this->getMemcache()->getMultiple($keys, $default, $cached);
    }

    protected function getCacheDecodedItem(string $cacheId, mixed $default = null, bool $cached = true): mixed
    {
        $start = 0;
        if (!$this->isCacheAllowed($cached)) {
            return $default;
        }

        if (MemcacheService::$profilerEnable) {
            $start = microtime(true);
        }

        if (isset(MemcacheService::$prefetchCacheData[$cacheId]) && MemcacheService::$prefetchCacheData[$cacheId]) {
            $result = MemcacheService::$prefetchCacheData[$cacheId];
        } elseif (($result = $this->getMemcache()->get($cacheId, $default, $cached)) && ($result != $default)) {
            $result = @json_decode($result, true);
        }

        if (is_null($result)) {
            $result = $default;
        }

        if (MemcacheService::$profilerEnable) {
            @MemcacheService::$profiler['getCacheDecodedItem']['count']++;
            @MemcacheService::$profiler['getCacheDecodedItem']['keys'][$cacheId]++;

            if (!isset(MemcacheService::$profiler['getCacheDecodedItem']['runtime'])) {
                MemcacheService::$profiler['getCacheDecodedItem']['runtime'] = 0;
            }
            $runtime = microtime(true) - $start;
            MemcacheService::$profiler['getCacheDecodedItem']['runtime'] += $runtime;
        }

        return $result;
    }

    /**
     * @param string     $cacheId
     * @param mixed      $data
     * @param string|int $ttl
     * @return mixed
     */
    protected function setCacheItem(string $cacheId, mixed $data, string|int $ttl = 'cache_ttl_middle'): mixed
    {
        if ($ttl == 'cache_ttl_middle') {
            $ttl = $this->getCacheTtlMiddle();
        }
        $this->getMemcache()->set($cacheId, $data, $ttl);

        if (MemcacheService::$addToPrefetchOnSet) {
            MemcacheService::$prefetchCacheData[$cacheId] = $data;
        }

        return $data;
    }

    /**
     * Time to live from 1 to 3 days
     */
    protected function getCacheTtlMiddle(): int
    {
        return mt_rand(86400, 3 * 86400); //
    }

    /**
     * @param array<string, mixed> $values
     */
    protected function setCacheMultiple(array $values, int $ttl = null): bool
    {
        if (is_null($ttl)) {
            $ttl = $this->getCacheTtlMiddle();
        }

        return $this->getMemcache()->setMultiple($values, $ttl);
    }

    /**
     * @param string $cacheId
     * @return bool
     */
    protected function deleteCacheItem(string $cacheId): bool
    {
        return $this->getMemcache()->delete($cacheId);
    }

    /**
     * @param list<string> $keys
     * @return array
     */
    protected function deleteCacheMultiple(array $keys): array
    {
        return $this->getMemcache()->deleteMultiple($keys);
    }

    protected function setCacheEncodedItem(string $cacheId, mixed $data, int $ttl = null): mixed
    {
        if ($ttl === null) {
            $ttl = $this->getCacheTtlMiddle();
        }
        $encodedData = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->getMemcache()->set($cacheId, $encodedData, $ttl);

        if (MemcacheService::$addToPrefetchOnSet) {
            MemcacheService::$prefetchCacheData[$cacheId] = $data;
        }

        return $data;
    }

    /**
     * Time to live from 1 to 3 hours
     */
    protected function getCacheTtlShort(): int
    {
        return mt_rand(3600, 10800);
    }

    /**
     * Time to live from 7 to 21 days
     */
    protected function getCacheTtlLong(): int
    {
        return mt_rand(7 * 86400, 21 * 86400);
    }

    protected function setCacheProfilerEnable(bool $status)
    {
        MemcacheService::$profilerEnable = $status;
    }
}
