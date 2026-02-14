<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\KnowledgeAiService;
use LaravelZero\Framework\Commands\Command;

class ConsensusCommand extends Command
{
    protected $signature = 'consensus
        {question : The question to ask multiple models}
        {--models=* : Specific models to query (default: claude, grok, gemini)}
        {--json : Output as JSON}';

    protected $description = 'Query multiple AI models and compare their responses';

    public function handle(KnowledgeAiService $service): int
    {
        $question = $this->argument('question');
        $models = $this->option('models') ?: [];

        $this->newLine();
        $this->components->info("Consensus: {$question}");
        $this->newLine();

        try {
            $result = $service->consensus($question, $models);
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

            $this->newLine();
        }

        $this->components->twoColumnDetail(
            '<fg=cyan>Models consulted</>',
            (string) $result['models_consulted']
        );

        if ($this->option('json')) {
            $this->newLine();
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }
}
