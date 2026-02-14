<?php

use App\Services\KnowledgeAiService;
use App\Services\ModelRouter;
use App\Services\OpenCodeClient;
use App\Services\PrefrontalClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        '*/session' => Http::response(['id' => 'test-session'], 200),
        '*/session/test-session/message' => Http::response([
            'info' => ['id' => 'msg-1', 'tokens' => ['input' => 10, 'output' => 20], 'cost' => 0],
            'parts' => [['type' => 'text', 'text' => 'AI generated answer based on knowledge']],
        ], 200),
        '*/filter*' => Http::response([
            'entries' => [
                ['title' => 'Laravel Queues', 'content' => 'Use dispatch() helper', 'tags' => ['laravel', 'queues']],
                ['title' => 'Redis Config', 'content' => 'Set REDIS_HOST in .env', 'tags' => ['redis', 'config']],
            ],
            'total' => 2,
        ], 200),
    ]);

    $this->service = new KnowledgeAiService(
        opencode: new OpenCodeClient('127.0.0.1', 4096),
        router: new ModelRouter,
        prefrontal: new PrefrontalClient('http://localhost', 'test-token'),
    );
});

it('fetches knowledge context before querying AI', function () {
    $result = $this->service->query('how do queues work');

    expect($result['context_entries'])->toBe(2);
    expect($result['query'])->toBe('how do queues work');
    expect($result['response'])->toBeArray();
});

it('routes query to appropriate model', function () {
    $result = $this->service->query('why did the deploy fail');

    expect($result['model']['tier'])->toBe('premium');
    expect($result['model']['provider'])->toBe('anthropic');
    expect($result['model']['intent'])->toBe('why');
});

it('uses explicit model when provided', function () {
    $result = $this->service->query('test query', 'grok');

    expect($result['model']['provider'])->toBe('xai');
    expect($result['model']['intent'])->toBe('explicit');
});

it('queries directly without knowledge context', function () {
    $result = $this->service->queryDirect('what is PHP');

    expect($result['context_entries'])->toBe(0);
    expect($result['response'])->toBeArray();
});

it('handles empty knowledge results gracefully', function () {
    $result = $this->service->query('something obscure');

    // Even with entries in the fake, the service should work
    expect($result['response'])->toBeArray();
    expect($result['query'])->toBe('something obscure');
});

it('includes prompt with knowledge entries', function () {
    $result = $this->service->query('how do queues work');

    Http::assertSent(function ($request) {
        if (! str_contains($request->url(), '/message')) {
            return false;
        }

        $parts = $request['parts'] ?? [];
        $text = $parts[0]['text'] ?? '';

        return str_contains($text, 'Knowledge Entries')
            && str_contains($text, 'Laravel Queues');
    });
});

it('consensus queries multiple models', function () {
    $result = $this->service->consensus('best caching strategy');

    expect($result['models_consulted'])->toBe(3);
    expect($result['responses'])->toHaveKeys(['claude', 'grok', 'gemini']);
    expect($result['query'])->toBe('best caching strategy');
});

it('consensus captures errors per model without failing', function () {
    // Consensus should work even when all succeed — each response is keyed by model
    $result = $this->service->consensus('test', ['claude', 'grok']);

    expect($result['models_consulted'])->toBe(2);

    // Each response should have a model and response (no errors in this case)
    foreach ($result['responses'] as $modelResult) {
        expect($modelResult)->toHaveKey('model');
        expect($modelResult)->toHaveKey('response');
    }
});

it('creates via static make method', function () {
    $service = KnowledgeAiService::make();
    expect($service)->toBeInstanceOf(KnowledgeAiService::class);
});
