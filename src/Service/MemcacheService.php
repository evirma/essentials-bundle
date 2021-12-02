<?php

declare(strict_types=1);

namespace Evirma\Bundle\EssentialsBundle\Service;

use ErrorException;
use Exception;
use Memcached;
use Psr\Log\LoggerInterface;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Contracts\Service\Attribute\Required;

class MemcacheService
{
    const MC_DEFAULT = '-1~1982~06~01';

    public static array $prefetchCacheData = [];
    public static bool $addToPrefetchOnSet = false;
    public static bool $profilerEnable = false;

    public static bool $isCacheAllowed = true;
    private bool $transaction = false;
    private array $transactionCachedIds = [];
    private ?Memcached $memcached = null;
    private string $prefix = '';
    private LoggerInterface $logger;
    public static array $profiler = [];

    #[Required]
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function setCacheAllowed(bool $state)
    {
        self::$isCacheAllowed = $state;
    }

    /**
     * @param $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    public function beginTransaction(): static
    {
        $this->transaction = true;

        return $this;
    }

    public function commit(): static
    {
        $this->transaction = false;
        $this->transactionCachedIds = [];

        return $this;
    }

    public function rollBack(): static
    {
        if ($this->transaction) {
            $this->transaction = false;
            if ($this->transactionCachedIds) {
                $this->deleteMultiple($this->transactionCachedIds);
            }
            $this->transactionCachedIds = [];
        }

        return $this;
    }

    public function deleteMultiple(array $keys): array
    {
        try {
            return $this->getMemcachedAdapter()->deleteMulti($keys);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached deleteMultiple failed", ['keys' => $keys, 'e' => $e]);
            return [];
        }
    }

    /**
     * @throws ErrorException
     */
    private function getMemcachedAdapter(bool $renewStoreKey = false): Memcached
    {
        if ($this->memcached && !$renewStoreKey) {
            return $this->memcached;
        }

        $options = [];
        $this->memcached = MemcachedAdapter::createConnection(
            'memcached://memcached-server',
            $options
        );

        if ($this->prefix) {
            $prefixStoreKey = $this->getPrefixStoreKey();
            if (!($prefix = $this->get($prefixStoreKey)) || $renewStoreKey) {
                $prefix = $this->prefix.mt_rand(1, 100000).'_';
                $this->set($prefixStoreKey, $prefix, 365 * 86400);
            }
            $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, $prefix);
        }

        return $this->memcached;
    }

    protected function getPrefixStoreKey(): string
    {
        return $this->prefix.'prefix_store';
    }

    /**
     * @param string $key
     * @param mixed  $default
     * @param bool   $cached
     * @return mixed
     */
    public function get(string $key, mixed $default = false, bool $cached = true): mixed
    {
        if (!$this->isCacheAllowed($cached)) {
            return $default;
        }

        try {
            $result = $this->getMemcachedAdapter()->get($key);
            if ($this->getMemcachedAdapter()->getResultCode() == Memcached::RES_NOTFOUND) {
                return $default;
            }

            return $result;
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached get failed", ['key' => $key, 'e' => $e]);

            return $default;
        }
    }

    /**
     * @param bool $cached
     * @return bool
     */
    public static function isCacheAllowed(bool $cached = true): bool
    {
        return self::$isCacheAllowed && $cached;
    }

    public function set(string $key, mixed $value = null, int $ttl = null): bool
    {
        if ($this->transaction) {
            $this->transactionCachedIds[$key] = $key;
        }

        try {
            return $this->getMemcachedAdapter()->set($key, $value, time() + $ttl);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached set failed", ['key' => $key, 'value' => $value, 'e' => $e]);

            return false;
        }
    }

    public function setMultiple(array $values, int $ttl = null): bool
    {
        try {
            return $this->getMemcachedAdapter()->setMulti($values, time() + $ttl);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached setMultiple failed", ['values' => $values, 'e' => $e]);

            return false;
        }
    }

    public function getMultiple($keys, mixed $default = false, bool $cached = true): mixed
    {
        if (!$this->isCacheAllowed($cached) || !is_array($keys)) {
            return $default;
        }

        $keys = array_values($keys);

        try {
            return $this->getMemcachedAdapter()->getMulti($keys);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached getMultiple failed", ['keys' => $keys, 'default' => $default, 'e' => $e]);

            return $default;
        }
    }

    /**
     * @param             $key
     * @param mixed|false $default
     * @param bool        $cached
     * @return mixed
     */
    public function getDecoded($key, mixed $default = false, bool $cached = true): mixed
    {
        if (!$this->isCacheAllowed($cached)) {
            return $default;
        }

        $result = $this->get($key, self::MC_DEFAULT);

        if ($result != self::MC_DEFAULT && $result) {
            $result = @json_decode($result, true);
        } else {
            $result = $default;
        }

        return $result;
    }

    public function delete(string $key): bool
    {
        try {
            return $this->getMemcachedAdapter()->delete($key);
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Memcached deleteMultiple failed", ['key' => $key, 'e' => $e]);
            return false;
        }
    }

    public function deletePrefix(): bool
    {
        try {
            $this->getMemcachedAdapter(true);

            return true;
        } catch (Exception $e) {
            $this->logger && $this->logger->error("Delete Prefix Key Failed", ['e' => $e]);

            return false;
        }
    }
}