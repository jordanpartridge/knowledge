<?php

declare(strict_types=1);

namespace App\Services;

class KnowledgeAiService
{
    public function __construct(
        private readonly OpenCodeClient $opencode,
        private readonly ModelRouter $router,
        private readonly PrefrontalClient $prefrontal,
        private readonly ?ResponseCache $cache = null,
    ) {}

    /**
     * Query knowledge with AI enrichment.
     *
     * @return array{query: string, response: mixed, model: array, context_entries: int}
     */
    public function query(string $query, ?string $model = null, int $contextLimit = 10): array
    {
        $route = $this->router->resolve($query, $model);

        $cached = $this->cache?->get($query, $route['model']);
        if ($cached !== null) {
            return $cached;
        }

        $context = $this->prefrontal->search($query, $contextLimit);
        $prompt = $this->buildPrompt($query, $context);

        $response = $this->opencode->prompt($prompt, $route['provider'], $route['model']);

        $result = [
            'query' => $query,
            'response' => $response,
            'model' => $route,
            'context_entries' => count($context['entries'] ?? []),
        ];

        $this->cache?->put($query, $route['model'], $result);

        return $result;
    }

    /**
     * Query without knowledge context (pure AI).
     *
     * @return array{query: string, response: mixed, model: array, context_entries: int}
     */
    public function queryDirect(string $query, ?string $model = null): array
    {
        $route = $this->router->resolve($query, $model);
        $response = $this->opencode->prompt($query, $route['provider'], $route['model']);

        return [
            'query' => $query,
            'response' => $response,
            'model' => $route,
            'context_entries' => 0,
        ];
    }

    /**
     * Multi-model consensus query.
     *
     * @param  array<int, string>  $models
     * @return array{query: string, responses: array, models_consulted: int}
     */
    public function consensus(string $query, array $models = []): array
    {
        $models = $models ?: ['claude', 'grok', 'gemini'];
        $context = $this->prefrontal->search($query);
        $prompt = $this->buildPrompt($query, $context);

        $responses = [];
        foreach ($models as $modelName) {
            $route = $this->router->resolve($query, $modelName);

            try {
                $responses[$modelName] = [
                    'model' => $route,
                    'response' => $this->opencode->prompt($prompt, $route['provider'], $route['model']),
                ];
            } catch (\RuntimeException $e) {
                $responses[$modelName] = [
                    'model' => $route,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'query' => $query,
            'responses' => $responses,
            'models_consulted' => count($responses),
            'context_entries' => count($context['entries'] ?? []),
        ];
    }

    /**
     * Build enriched prompt with knowledge context.
     *
     * @param  array<string, mixed>  $context
     */
    private function buildPrompt(string $query, array $context): string
    {
        $entries = $context['entries'] ?? [];

        if (empty($entries)) {
            return "You are a knowledge assistant.\n\nNo relevant knowledge entries were found in the knowledge base.\n\n## Query\n{$query}\n\nAnswer based on your general knowledge. Note that the knowledge base had no relevant entries.";
        }

        $formattedEntries = '';
        foreach ($entries as $i => $entry) {
            $num = $i + 1;
            $title = $entry['title'] ?? 'Untitled';
            $content = $entry['content'] ?? $entry['body'] ?? '';
            $tags = implode(', ', $entry['tags'] ?? []);
            $formattedEntries .= "### Entry #{$num}: {$title}\n{$content}\nTags: {$tags}\n\n";
        }

        return <<<PROMPT
        You are a knowledge assistant with access to my personal knowledge base.

        ## Relevant Knowledge Entries
        {$formattedEntries}
        ## Query
        {$query}

        ## Instructions
        Analyze the knowledge entries above and answer the query.
        Reference specific entries when relevant.
        If the knowledge base doesn't contain relevant information, say so clearly.
        PROMPT;
    }

    /**
     * Create from config/container.
     */
    public static function make(): self
    {
        return new self(
            opencode: OpenCodeClient::fromConfig(),
            router: new ModelRouter,
            prefrontal: PrefrontalClient::fromConfig(),
            cache: ResponseCache::fromConfig(),
        );
    }
}
