<?php

declare(strict_types=1);

namespace App\Services;

class ModelRouter
{
    /** @var array<string, array{provider: string, model: string, tier: string}> */
    private array $routes;

    public function __construct()
    {
        $this->routes = [
            'what' => ['provider' => 'groq', 'model' => 'llama-3.3-70b-versatile', 'tier' => 'fast'],
            'how' => ['provider' => 'xai', 'model' => 'grok-3', 'tier' => 'balanced'],
            'why' => ['provider' => 'anthropic', 'model' => 'claude-sonnet-4-5-20250929', 'tier' => 'premium'],
            'analyze' => ['provider' => 'anthropic', 'model' => 'claude-opus-4-6', 'tier' => 'premium'],
            'code' => ['provider' => 'openrouter', 'model' => 'google/gemini-2.5-pro', 'tier' => 'balanced'],
            'search' => ['provider' => 'groq', 'model' => 'llama-3.3-70b-versatile', 'tier' => 'fast'],
            'default' => ['provider' => 'xai', 'model' => 'grok-3', 'tier' => 'balanced'],
        ];
    }

    /**
     * Resolve the best model for a query.
     * Explicit model override takes priority.
     *
     * @return array{provider: string, model: string, tier: string, intent: string}
     */
    public function resolve(string $query, ?string $explicitModel = null): array
    {
        if ($explicitModel !== null) {
            return $this->parseExplicitModel($explicitModel);
        }

        $intent = $this->classify($query);
        $route = $this->routes[$intent] ?? $this->routes['default'];

        return [...$route, 'intent' => $intent];
    }

    /**
     * Classify query intent from natural language.
     */
    public function classify(string $query): string
    {
        $query = strtolower($query);

        // Order matters: more specific intents checked first
        $patterns = [
            'analyze' => '/\b(analyze|compare|evaluate|assess|review|diff)\b/i',
            'why' => '/\b(why|reason|cause|root cause|because)\b/i',
            'code' => '/\b(code|implement|write|refactor|fix|debug|function|class|method)\b/i',
            'how' => '/\b(how|explain|describe|walk me through)\b/i',
            'search' => '/\b(search|where|locate|find in)\b/i',
            'what' => '/\b(what|list|show|find|get|which)\b/i',
        ];

        foreach ($patterns as $intent => $pattern) {
            if (preg_match($pattern, $query)) {
                return $intent;
            }
        }

        return 'default';
    }

    /**
     * Get all routes.
     *
     * @return array<string, array{provider: string, model: string, tier: string}>
     */
    public function routes(): array
    {
        return $this->routes;
    }

    /**
     * Override a route at runtime.
     */
    public function setRoute(string $intent, string $provider, string $model, string $tier = 'balanced'): void
    {
        $this->routes[$intent] = [
            'provider' => $provider,
            'model' => $model,
            'tier' => $tier,
        ];
    }

    /**
     * Parse explicit model string like "anthropic/claude-opus-4-6" or shorthand "grok".
     *
     * @return array{provider: string, model: string, tier: string, intent: string}
     */
    private function parseExplicitModel(string $model): array
    {
        $shortcuts = [
            'claude' => ['provider' => 'anthropic', 'model' => 'claude-sonnet-4-5-20250929', 'tier' => 'premium'],
            'opus' => ['provider' => 'anthropic', 'model' => 'claude-opus-4-6', 'tier' => 'premium'],
            'grok' => ['provider' => 'xai', 'model' => 'grok-3', 'tier' => 'balanced'],
            'llama' => ['provider' => 'groq', 'model' => 'llama-3.3-70b-versatile', 'tier' => 'fast'],
            'gemini' => ['provider' => 'openrouter', 'model' => 'google/gemini-2.5-pro', 'tier' => 'balanced'],
        ];

        if (isset($shortcuts[$model])) {
            return [...$shortcuts[$model], 'intent' => 'explicit'];
        }

        if (str_contains($model, '/')) {
            [$provider, $modelId] = explode('/', $model, 2);

            return ['provider' => $provider, 'model' => $modelId, 'tier' => 'custom', 'intent' => 'explicit'];
        }

        return ['provider' => 'openrouter', 'model' => $model, 'tier' => 'custom', 'intent' => 'explicit'];
    }
}
