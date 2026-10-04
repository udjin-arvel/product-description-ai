<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Yii\Bridge;

use Psr\SimpleCache\CacheInterface;
use yii\caching\CacheInterface as YiiCacheInterface;

final class YiiCacheAdapter implements CacheInterface
{
    public function __construct(private readonly YiiCacheInterface $cache)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->cache->get($key);

        return $value === false ? $default : $value;
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $seconds = $ttl instanceof \DateInterval
            ? (new \DateTime())->add($ttl)->getTimestamp() - \time()
            : ($ttl ?? 0);

        return $this->cache->set($key, $value, (int) $seconds);
    }

    public function delete(string $key): bool
    {
        return $this->cache->delete($key);
    }

    public function clear(): bool
    {
        return $this->cache->flush();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        $ok = true;
        foreach ($values as $key => $value) {
            $ok = $this->set((string) $key, $value, $ttl) && $ok;
        }

        return $ok;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $ok = true;
        foreach ($keys as $key) {
            $ok = $this->delete((string) $key) && $ok;
        }

        return $ok;
    }

    public function has(string $key): bool
    {
        return $this->cache->exists($key);
    }
}
