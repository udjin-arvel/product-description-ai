<?php

declare(strict_types=1);

namespace ProductDescriptionAI\Laravel\Cache;

use Illuminate\Cache\Repository;
use Psr\SimpleCache\CacheInterface;

final class LaravelCacheAdapter implements CacheInterface
{
    public function __construct(private readonly Repository $cache)
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->get($key, $default);
    }

    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool
    {
        $seconds = $ttl instanceof \DateInterval
            ? (new \DateTime())->add($ttl)->getTimestamp() - time()
            : $ttl;

        return $this->cache->put($key, $value, $seconds ?? null);
    }

    public function delete(string $key): bool
    {
        return $this->cache->forget($key);
    }

    public function clear(): bool
    {
        return $this->cache->flush();
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get((string) $key, $default);
        }

        return $result;
    }

    /**
     * @param iterable<string, mixed> $values
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set((string) $key, $value, $ttl);
        }

        return true;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete((string) $key);
        }

        return true;
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }
}
