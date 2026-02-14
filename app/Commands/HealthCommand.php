<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\OpenCodeClient;
use App\Services\PrefrontalClient;
use LaravelZero\Framework\Commands\Command;

class HealthCommand extends Command
{
    protected $signature = 'health {--json : JSON output}';

    protected $description = 'Check health of all connected services';

    public function handle(OpenCodeClient $opencode, PrefrontalClient $prefrontal): int
    {
        $checks = [
            'opencode' => $opencode->isAvailable(),
            'prefrontal' => $prefrontal->isAvailable(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($checks, JSON_PRETTY_PRINT));

            return $checks['opencode'] && $checks['prefrontal'] ? self::SUCCESS : self::FAILURE;
        }

        $this->newLine();
        $this->components->info('Service Health');
        $this->newLine();

        foreach ($checks as $service => $healthy) {
            $status = $healthy ? '<fg=green>UP</>' : '<fg=red>DOWN</>';
            $this->components->twoColumnDetail($service, $status);
        }

        $this->newLine();

        $allHealthy = ! in_array(false, $checks, true);

        if ($allHealthy) {
            $this->components->info('All services healthy');
        } else {
            $this->components->error('Some services are down');
        }

        return $allHealthy ? self::SUCCESS : self::FAILURE;
    }
}
