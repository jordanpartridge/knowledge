<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        '*/session' => Http::response(['id' => 'test-session'], 200),
        '*/session/test-session/message' => Http::response([
            'info' => ['id' => 'msg-1', 'tokens' => ['input' => 10, 'output' => 20], 'cost' => 0],
            'parts' => [['type' => 'text', 'text' => 'This is the AI response about your knowledge']],
        ], 200),
        '*/filter*' => Http::response([
            'entries' => [
                ['title' => 'Test Entry', 'content' => 'Some knowledge', 'tags' => ['test']],
            ],
            'total' => 1,
        ], 200),
    ]);
});

it('queries knowledge with default routing', function () {
    $this->artisan('query', ['question' => 'what is Laravel'])
        ->assertExitCode(0);
});

it('accepts explicit model override', function () {
    $this->artisan('query', ['question' => 'test question', '--model' => 'grok'])
        ->assertExitCode(0);
});

it('runs consensus mode', function () {
    $this->artisan('query', ['question' => 'best caching strategy', '--consensus' => true])
        ->assertExitCode(0);
});

it('skips knowledge context with --no-context', function () {
    $this->artisan('query', ['question' => 'generic question', '--no-context' => true])
        ->assertExitCode(0);
});

it('outputs JSON when flag is set', function () {
    $this->artisan('query', ['question' => 'test', '--json' => true])
        ->assertExitCode(0);
});

it('respects limit option', function () {
    $this->artisan('query', ['question' => 'test', '--limit' => '5'])
        ->assertExitCode(0);
});

it('shows error when opencode is not running', function () {
    $service = Mockery::mock(\App\Services\KnowledgeAiService::class);
    $service->shouldReceive('query')
        ->andThrow(new \RuntimeException('Failed to create session: 503'));

    $this->app->instance(\App\Services\KnowledgeAiService::class, $service);

    $this->artisan('query', ['question' => 'test'])
        ->assertExitCode(1);
});
