<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class PrefrontalClient
{
    public function __construct(
        private readonly string $baseUrl = 'https://prefrontal-cortex.jordanpartridge.us/api/knowledge',
        private readonly ?string $token = null,
    ) {}

    /**
     * Search knowledge entries.
     *
     * @return array<string, mixed>
     */
    public function search(string $query, int $limit = 10): array
    {
        $response = $this->client()->get('/filter', [
            'search' => $query,
            'limit' => $limit,
        ]);

        if ($response->failed()) {
            return ['entries' => [], 'total' => 0];
        }

        return $response->json();
    }

    /**
     * Get knowledge entries with filters.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function entries(array $filters = []): array
    {
        $response = $this->client()->get('/entries', $filters);

        if ($response->failed()) {
            return ['data' => []];
        }

        return $response->json();
    }

    /**
     * Get full context for a query (entries + relationships + patterns).
     *
     * @return array<string, mixed>
     */
    public function context(string $query): array
    {
        $response = $this->client()->get('/context', [
            'query' => $query,
        ]);

        if ($response->failed()) {
            return ['entries' => [], 'patterns' => [], 'relationships' => []];
        }

        return $response->json();
    }

    /**
     * Store a new knowledge entry.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function store(array $data): array
    {
        $response = $this->client()->post('/sync', $data);

        if ($response->failed()) {
            return ['success' => false, 'error' => $response->body()];
        }

        return $response->json();
    }

    /**
     * Check if prefrontal-cortex is reachable.
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->client()
                ->timeout(3)
                ->get('/dashboard');

            return $response->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    /**
     * Create from config values.
     */
    public static function fromConfig(): self
    {
        return new self(
            baseUrl: (string) config('knowledge.prefrontal.url', 'https://prefrontal-cortex.jordanpartridge.us/api/knowledge'),
            token: config('knowledge.prefrontal.token'),
        );
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl)
            ->withToken($this->token ?? '')
            ->acceptJson()
            ->timeout(10);
    }
}
