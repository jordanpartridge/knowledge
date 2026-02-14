<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        '*/session' => Http::response(['id' => 'test-session'], 200),
        '*/session/test-session/message' => Http::response([
            'info' => ['id' => 'msg-1', 'tokens' => ['input' => 10, 'output' => 20], 'cost' => 0],
            'parts' => [['type' => 'text', 'text' => 'Consensus response']],
        ], 200),
        '*/filter*' => Http::response([
            'entries' => [
                ['title' => 'Test', 'content' => 'Content', 'tags' => ['test']],
            ],
            'total' => 1,
        ], 200),
    ]);
});

it('queries multiple models', function () {
    $this->artisan('consensus', ['question' => 'best caching strategy'])
        ->assertExitCode(0);
});

it('accepts specific models', function () {
    $this->artisan('consensus', [
        'question' => 'test question',
        '--models' => ['claude', 'grok'],
    ])->assertExitCode(0);
});

it('outputs JSON', function () {
    $this->artisan('consensus', [
        'question' => 'test',
        '--json' => true,
    ])->assertExitCode(0);
});
