<?php

declare(strict_types=1);

namespace App\Commands;

use App\Services\PrefrontalClient;
use LaravelZero\Framework\Commands\Command;

class RememberCommand extends Command
{
    protected $signature = 'remember
        {content : The knowledge to store}
        {--title= : Title for the entry}
        {--tags=* : Tags for categorization}
        {--json : Output as JSON}';

    protected $description = 'Store a new knowledge entry in the knowledge base';

    public function handle(PrefrontalClient $prefrontal): int
    {
        $content = $this->argument('content');
        $title = $this->option('title') ?? $this->deriveTitle($content);
        $tags = $this->option('tags') ?: [];

        $result = $prefrontal->store([
            'title' => $title,
            'content' => $content,
            'tags' => $tags,
        ]);

        if (isset($result['success']) && $result['success'] === false) {
            $this->components->error($result['error'] ?? 'Failed to store knowledge entry');

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->newLine();
        $this->components->info('Knowledge stored');
        $this->components->twoColumnDetail('Title', $title);

        if (! empty($tags)) {
            $this->components->twoColumnDetail('Tags', implode(', ', $tags));
        }

        return self::SUCCESS;
    }

    private function deriveTitle(string $content): string
    {
        $firstLine = strtok($content, "\n");
        $title = mb_substr($firstLine, 0, 80);

        return mb_strlen($firstLine) > 80 ? $title.'...' : $title;
    }
}
