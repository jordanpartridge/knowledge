<?php

use App\Services\PrefrontalClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new PrefrontalClient('https://example.com/api/knowledge', 'test-token');
});

it('searches knowledge entries', function () {
    Http::fake([
        '*/filter*' => Http::response([
            'entries' => [
                ['title' => 'Laravel Queues', 'content' => 'Queue workers...'],
            ],
            'total' => 1,
        ], 200),
    ]);

    $result = $this->client->search('queues', 5);

    expect($result['entries'])->toHaveCount(1);
    expect($result['total'])->toBe(1);
});

it('returns empty on search failure', function () {
    Http::fake([
        '*/filter*' => Http::response('Error', 500),
    ]);

    $result = $this->client->search('test');

    expect($result['entries'])->toBeEmpty();
    expect($result['total'])->toBe(0);
});

it('fetches entries with filters', function () {
    Http::fake([
        '*/entries*' => Http::response([
            'data' => [
                ['id' => 1, 'title' => 'Entry 1'],
                ['id' => 2, 'title' => 'Entry 2'],
            ],
        ], 200),
    ]);

    $result = $this->client->entries(['category' => 'debugging']);

    expect($result['data'])->toHaveCount(2);
});

it('fetches context for a query', function () {
    Http::fake([
        '*/context*' => Http::response([
            'entries' => [['title' => 'Related']],
            'patterns' => ['pattern1'],
            'relationships' => [],
        ], 200),
    ]);

    $result = $this->client->context('deployment failures');

    expect($result)->toHaveKeys(['entries', 'patterns', 'relationships']);
});

it('stores a knowledge entry', function () {
    Http::fake([
        '*/sync' => Http::response(['success' => true, 'id' => 'abc-123'], 200),
    ]);

    $result = $this->client->store([
        'title' => 'New insight',
        'content' => 'Something learned',
        'tags' => ['learning'],
    ]);

    expect($result['success'])->toBeTrue();
});

it('reports available when reachable', function () {
    Http::fake([
        '*/dashboard' => Http::response(['status' => 'ok'], 200),
    ]);

    expect($this->client->isAvailable())->toBeTrue();
});

it('reports unavailable when unreachable', function () {
    Http::fake([
        '*/dashboard' => Http::response('Error', 500),
    ]);

    expect($this->client->isAvailable())->toBeFalse();
});

it('creates from config', function () {
    config(['knowledge.prefrontal.url' => 'https://custom.test/api']);
    config(['knowledge.prefrontal.token' => 'custom-token']);

    $client = PrefrontalClient::fromConfig();

    // Verify it constructs without errors
    expect($client)->toBeInstanceOf(PrefrontalClient::class);
});
