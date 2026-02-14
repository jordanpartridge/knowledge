<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\ModelRouter;
use LaravelZero\Framework\Commands\Command;

class ModelsCommand extends Command
{
    protected $signature = 'models
        {--provider= : Filter by provider}
        {--tier= : Filter by tier (fast, balanced, premium)}
        {--routes : Show current model routing table}
        {--json : JSON output}';

    protected $description = 'List available AI models and routing configuration';

    public function handle(ModelRouter $router): int
    {
        $routes = $router->routes();
        $rows = [];

        foreach ($routes as $intent => $route) {
            if ($this->option('provider') && $route['provider'] !== $this->option('provider')) {
                continue;
            }
            if ($this->option('tier') && $route['tier'] !== $this->option('tier')) {
                continue;
            }

            $rows[] = [$intent, $route['provider'], $route['model'], $route['tier']];
        }

        if ($this->option('json')) {
            $this->line(json_encode($routes, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Model Routing Table');
        $this->newLine();
        $this->table(['Intent', 'Provider', 'Model', 'Tier'], $rows);
        $this->newLine();
        $this->components->twoColumnDetail('Total routes', (string) count($rows));

        return self::SUCCESS;
    }
}
