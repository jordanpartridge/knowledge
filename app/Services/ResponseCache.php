<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ResponseCache
{
    public function __construct(
        private readonly int $ttl = 3600,
        private readonly bool $enabled = true,
    ) {}

    public function get(string $query, string $model): ?array
    {
        if (! $this->enabled) {
            return null;
        }

        return Cache::get($this->key($query, $model));
    }

    public function put(string $query, string $model, array $response): void
    {
        if (! $this->enabled) {
            return;
        }

        Cache::put($this->key($query, $model), $response, $this->ttl);
    }

    public function forget(string $query, string $model): bool
    {
        return Cache::forget($this->key($query, $model));
    }

    public function flush(): bool
    {
        return Cache::flush();
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public static function fromConfig(): self
    {
        return new self(
            ttl: (int) config('knowledge.cache.ttl', 3600),
            enabled: (bool) config('knowledge.cache.enabled', true),
        );
    }

    private function key(string $query, string $model): string
    {
        return 'knowledge:'.md5($query.'|'.$model);
    }
}
