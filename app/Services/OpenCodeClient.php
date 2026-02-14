<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenCodeClient
{
    private ?string $sessionId = null;

    public function __construct(
        private readonly string $host = '127.0.0.1',
        private readonly int $port = 4096,
    ) {}

    /**
     * Send a prompt to opencode serve and get a response.
     */
    public function prompt(string $message, string $providerID = 'anthropic', string $modelID = 'claude-sonnet-4-5-20250929'): array
    {
        $sessionId = $this->ensureSession();

        $response = $this->client()
            ->post("/session/{$sessionId}/prompt", [
                'message' => $message,
                'model' => [
                    'providerID' => $providerID,
                    'modelID' => $modelID,
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException("OpenCode prompt failed: {$response->status()} {$response->body()}");
        }

        return $response->json();
    }

    /**
     * Create a new session.
     */
    public function createSession(): string
    {
        $response = $this->client()->post('/session');

        if ($response->failed()) {
            throw new RuntimeException("Failed to create session: {$response->status()}");
        }

        $this->sessionId = $response->json('id');

        return $this->sessionId;
    }

    /**
     * List available models from opencode.
     */
    public function models(): array
    {
        $response = $this->client()->get('/models');

        if ($response->failed()) {
            throw new RuntimeException("Failed to fetch models: {$response->status()}");
        }

        return $response->json();
    }

    /**
     * Check if opencode serve is running and reachable.
     */
    public function isAvailable(): bool
    {
        try {
            $response = $this->client()
                ->timeout(2)
                ->get('/doc');

            return $response->successful();
        } catch (ConnectionException) {
            return false;
        }
    }

    /**
     * Get the base URL for opencode serve.
     */
    public function baseUrl(): string
    {
        return "http://{$this->host}:{$this->port}";
    }

    /**
     * Get the current session ID.
     */
    public function sessionId(): ?string
    {
        return $this->sessionId;
    }

    /**
     * Create from config values.
     */
    public static function fromConfig(): self
    {
        return new self(
            host: config('knowledge.opencode.host', '127.0.0.1'),
            port: (int) config('knowledge.opencode.port', 4096),
        );
    }

    private function ensureSession(): string
    {
        if ($this->sessionId === null) {
            $this->createSession();
        }

        return $this->sessionId;
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl($this->baseUrl())
            ->acceptJson()
            ->contentType('application/json')
            ->timeout(30);
    }
}
