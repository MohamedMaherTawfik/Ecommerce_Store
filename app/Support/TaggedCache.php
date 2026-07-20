<?php

namespace App\Support;

use Illuminate\Cache\RedisStore;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class TaggedCache
{
    private static bool $fallbackLogged = false;
    private static bool $diagnosticsLogged = false;
    private static bool $runtimeStoreChecked = false;
    private static ?bool $redisAvailable = null;
    private static ?string $initialDefaultDriver = null;

    /**
     * @param  array<int, string>|string  $tags
     */
    public static function tags(array|string $tags): mixed
    {
        self::ensureOperationalStore();

        $normalizedTags = self::normalizeTags($tags);
        $driver = (string) config('cache.default');
        self::$initialDefaultDriver ??= $driver;
        $repository = Cache::store();

        if ($repository->supportsTags() && self::driverIsOperationalForTags($driver)) {
            return $repository->tags($normalizedTags);
        }

        self::logFallback($normalizedTags, $driver);

        return new TaggedCacheFallbackRepository($normalizedTags, (string) config('cache.default'));
    }

    public static function ensureOperationalStore(): void
    {
        if (self::$runtimeStoreChecked) {
            return;
        }

        self::$runtimeStoreChecked = true;

        $driver = (string) config('cache.default');
        self::$initialDefaultDriver ??= $driver;
        $repository = Cache::store();

        if (!self::storeUsesRedis($driver)) {
            return;
        }

        if (self::redisConnectionHealthy()) {
            return;
        }

        $fallbackStore = self::resolveFallbackStore();
        config(['cache.default' => $fallbackStore]);
        Cache::setDefaultDriver($fallbackStore);

        Log::warning('Redis cache is unavailable. Falling back to non-redis cache store.', [
            'configured_default_driver' => $driver,
            'runtime_default_driver' => $fallbackStore,
        ]);
    }

    public static function logDiagnostics(): void
    {
        self::ensureOperationalStore();

        if (self::$diagnosticsLogged) {
            return;
        }

        self::$diagnosticsLogged = true;

        if (!self::shouldLog('cache_diagnostics_v1', now()->addMinutes(30))) {
            return;
        }

        $driver = (string) config('cache.default');
        $repository = Cache::store();
        $store = $repository->getStore();
        $storeClass = get_class($store);
        $supportsTags = $repository->supportsTags();

        $redisConfiguredForStore = self::storeUsesRedis($driver);
        $redisConnection = null;
        $redisPing = null;
        $redisError = null;

        if ($redisConfiguredForStore) {
            $redisConnection = (string) config("cache.stores.{$driver}.connection", 'cache');

            try {
                $redisPing = Redis::connection($redisConnection)->ping();
            } catch (\Throwable $e) {
                $redisError = $e->getMessage();
            }
        }

        Log::info('Cache diagnostics', [
            'configured_default_driver' => self::$initialDefaultDriver ?? $driver,
            'runtime_default_driver' => (string) config('cache.default'),
            'env_cache_store' => env('CACHE_STORE'),
            'env_cache_driver' => env('CACHE_DRIVER'),
            'resolved_store_class' => $storeClass,
            'supports_tags' => $supportsTags,
            'redis_configured_for_store' => $redisConfiguredForStore,
            'redis_connection' => $redisConnection,
            'redis_ping' => $redisPing,
            'redis_ping_ok' => $redisPing !== null && $redisError === null,
            'redis_error' => $redisError,
            'unexpected_fallback_detected' => $redisConfiguredForStore && !($store instanceof RedisStore),
        ]);
    }

    /**
     * @param  array<int, string>  $tags
     */
    private static function logFallback(array $tags, string $driver): void
    {
        if (self::$fallbackLogged) {
            return;
        }

        self::$fallbackLogged = true;

        if (!self::shouldLog('cache_tag_fallback_warning_v1', now()->addMinutes(30))) {
            return;
        }

        $repository = Cache::store();

        Log::warning('Tagged cache compatibility mode is active for the current cache store.', [
            'configured_default_driver' => $driver,
            'resolved_store_class' => get_class($repository->getStore()),
            'tags' => $tags,
        ]);
    }

    /**
     * @param  array<int, string>|string  $tags
     * @return array<int, string>
     */
    private static function normalizeTags(array|string $tags): array
    {
        $values = is_array($tags) ? $tags : [$tags];

        $normalized = [];

        foreach ($values as $tag) {
            $tag = trim((string) $tag);

            if ($tag === '') {
                continue;
            }

            $normalized[] = $tag;
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    private static function storeUsesRedis(string $storeName): bool
    {
        $driver = (string) config("cache.stores.{$storeName}.driver");

        return $driver === 'redis';
    }

    private static function driverIsOperationalForTags(string $storeName): bool
    {
        if (!self::storeUsesRedis($storeName)) {
            return true;
        }

        return self::redisConnectionHealthy();
    }

    private static function redisConnectionHealthy(): bool
    {
        if (self::$redisAvailable !== null) {
            return self::$redisAvailable;
        }

        $defaultStore = (string) config('cache.default');
        self::$redisAvailable = self::redisConnectionHealthyForStore($defaultStore);

        return self::$redisAvailable;
    }

    private static function redisConnectionHealthyForStore(string $storeName): bool
    {
        $connection = (string) config("cache.stores.{$storeName}.connection", 'cache');

        try {
            Redis::connection($connection)->ping();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function resolveFallbackStore(): string
    {
        foreach (['file', 'database', 'array'] as $store) {
            if (config("cache.stores.{$store}") !== null) {
                return $store;
            }
        }

        return 'array';
    }

    /**
     * Log throttle across requests.
     */
    private static function shouldLog(string $key, \DateTimeInterface|\DateInterval|int $ttl): bool
    {
        $store = self::resolveFallbackStore();

        try {
            return Cache::store($store)->add($key, now()->toIso8601String(), $ttl);
        } catch (\Throwable) {
            return true;
        }
    }
}

class TaggedCacheFallbackRepository
{
    /**
     * @param  array<int, string>  $tags
     */
    public function __construct(
        private readonly array $tags,
        private readonly string $storeName
    )
    {
    }

    /**
     * @param  \DateInterval|\DateTimeInterface|int|null  $ttl
     */
    public function remember(string $key, \DateInterval|\DateTimeInterface|int|null $ttl, \Closure $callback): mixed
    {
        return Cache::store($this->storeName)->remember($this->namespacedKey($key), $ttl, $callback);
    }

    public function forget(string $key): bool
    {
        return Cache::store($this->storeName)->forget($this->namespacedKey($key));
    }

    public function flush(): bool
    {
        foreach ($this->tags as $tag) {
            Cache::store($this->storeName)->forever($this->versionKey($tag), (string) Str::uuid());
        }

        return true;
    }

    private function namespacedKey(string $key): string
    {
        $parts = [];

        foreach ($this->tags as $tag) {
            $parts[] = "{$tag}:{$this->tagVersion($tag)}";
        }

        $namespace = sha1(implode('|', $parts));

        return "tagged_fallback:{$namespace}:{$key}";
    }

    private function tagVersion(string $tag): string
    {
        return (string) Cache::store($this->storeName)->get($this->versionKey($tag), 'v1');
    }

    private function versionKey(string $tag): string
    {
        return 'tagged_fallback_version:' . sha1($tag);
    }
}
