<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\KnowledgeAiService;
use LaravelZero\Framework\Commands\Command;

class QueryCommand extends Command
{
    protected $signature = 'query
        {question : The question to ask}
        {--model= : Explicit model (e.g., grok, claude, gemini, anthropic/claude-opus-4-6)}
        {--consensus : Query multiple models and compare}
        {--no-context : Skip knowledge fetching, pure AI query}
        {--json : Output as JSON}
        {--limit=10 : Max knowledge entries to include as context}';

    protected $description = 'Query your knowledge base with AI enrichment';

    public function handle(KnowledgeAiService $service): int
    {
        $question = $this->argument('question');
        $model = $this->option('model');
        $limit = (int) $this->option('limit');

        if ($this->option('consensus')) {
            return $this->handleConsensus($service, $question);
        }

        $this->output->write("\n");
        $this->components->twoColumnDetail('<fg=cyan>Query</>', $question);

        try {
            if ($this->option('no-context')) {
                $result = $service->queryDirect($question, $model);
            } else {
                $result = $service->query($question, $model, $limit);
            }
        } catch (\RuntimeException $e) {
            $this->components->error($e->getMessage());

            if (str_contains($e->getMessage(), 'Failed to create session') || str_contains($e->getMessage(), 'Connection refused')) {
                $this->components->info('Is opencode serve running? Start it with: opencode serve');
            }

            return self::FAILURE;
        }

        $modelInfo = $result['model'];
        $this->components->twoColumnDetail(
            '<fg=cyan>Model</>',
            "{$modelInfo['model']} ({$modelInfo['provider']}) [{$modelInfo['tier']}]"
        );
        $this->components->twoColumnDetail(
            '<fg=cyan>Context</>',
            "{$result['context_entries']} entries loaded"
        );

        $this->output->write("\n");

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $response = $result['response']['response'] ?? $result['response']['content'] ?? json_encode($result['response']);
            $this->line($response);
        }

        return self::SUCCESS;
    }

    private function handleConsensus(KnowledgeAiService $service, string $question): int
    {
        $this->output->write("\n");
        $this->components->info("Consensus query: {$question}");
        $this->output->write("\n");

        try {
            $result = $service->consensus($question);
        } catch (\RuntimeException $e) {
            $this->components->error($e->getMessage());

            return self::FAILURE;
        }

        foreach ($result['responses'] as $modelName => $modelResult) {
            $route = $modelResult['model'];
            $this->components->twoColumnDetail(
                "<fg=yellow>{$modelName}</>",
                "{$route['model']} ({$route['provider']})"
            );

            if (isset($modelResult['error'])) {
                $this->components->error("  Error: {$modelResult['error']}");
            } else {
                $response = $modelResult['response']['response'] ?? $modelResult['response']['content'] ?? json_encode($modelResult['response']);
                $this->line($response);
            }

            $this->output->write("\n");
        }

        $this->components->twoColumnDetail(
            '<fg=cyan>Models consulted</>',
            (string) $result['models_consulted']
        );

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
