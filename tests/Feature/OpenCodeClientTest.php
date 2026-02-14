<?php

use App\Services\OpenCodeClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->client = new OpenCodeClient('127.0.0.1', 4096);
});

it('constructs with default host and port', function () {
    $client = new OpenCodeClient;
    expect($client->baseUrl())->toBe('http://127.0.0.1:4096');
});

it('constructs with custom host and port', function () {
    $client = new OpenCodeClient('localhost', 8080);
    expect($client->baseUrl())->toBe('http://localhost:8080');
});

it('creates from config', function () {
    config(['knowledge.opencode.host' => '10.0.0.1']);
    config(['knowledge.opencode.port' => 9999]);

    $client = OpenCodeClient::fromConfig();
    expect($client->baseUrl())->toBe('http://10.0.0.1:9999');
});

it('creates a session', function () {
    Http::fake([
        '*/session' => Http::response(['id' => 'test-session-123'], 200),
    ]);

    $sessionId = $this->client->createSession();

    expect($sessionId)->toBe('test-session-123');
    expect($this->client->sessionId())->toBe('test-session-123');
});

it('sends a prompt with model selection', function () {
    Http::fake([
        '*/session' => Http::response(['id' => 'sess-1'], 200),
        '*/session/sess-1/message' => Http::response([
            'info' => [
                'id' => 'msg-1',
                'tokens' => ['input' => 10, 'output' => 5],
                'cost' => 0.001,
            ],
            'parts' => [
                ['type' => 'text', 'text' => 'AI response here'],
            ],
        ], 200),
    ]);

    $result = $this->client->prompt('What is Laravel?', 'xai', 'grok-3');

    expect($result['response'])->toBe('AI response here');
    expect($result['content'])->toBe('AI response here');
    expect($result['tokens'])->toHaveKey('input');
    expect($result['cost'])->toBe(0.001);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/message')
            && $request['parts'][0]['type'] === 'text'
            && $request['parts'][0]['text'] === 'What is Laravel?'
            && $request['model']['providerID'] === 'xai'
            && $request['model']['modelID'] === 'grok-3';
    });
});

it('auto-creates session on first prompt', function () {
    Http::fake([
        '*/session' => Http::response(['id' => 'auto-sess'], 200),
        '*/session/auto-sess/message' => Http::response([
            'info' => ['id' => 'msg-1', 'tokens' => [], 'cost' => 0],
            'parts' => [['type' => 'text', 'text' => 'ok']],
        ], 200),
    ]);

    expect($this->client->sessionId())->toBeNull();

    $this->client->prompt('test');

    expect($this->client->sessionId())->toBe('auto-sess');
});

it('fetches available models', function () {
    Http::fake([
        '*/models' => Http::response([
            ['provider' => 'anthropic', 'model' => 'claude-opus-4-6'],
            ['provider' => 'groq', 'model' => 'llama-3.3-70b-versatile'],
        ], 200),
    ]);

    $models = $this->client->models();

    expect($models)->toHaveCount(2);
});

it('fetches providers', function () {
    Http::fake([
        '*/provider' => Http::response([
            'all' => [
                'anthropic' => ['id' => 'anthropic', 'models' => []],
                'openrouter' => ['id' => 'openrouter', 'models' => []],
            ],
        ], 200),
    ]);

    $providers = $this->client->providers();

    expect($providers)->toHaveKey('all');
});

it('reports available when opencode serve is running', function () {
    Http::fake([
        '*/doc' => Http::response(['openapi' => '3.1.0'], 200),
    ]);

    expect($this->client->isAvailable())->toBeTrue();
});

it('reports unavailable when opencode serve is down', function () {
    Http::fake([
        '*/doc' => Http::response(null, 500),
    ]);

    expect($this->client->isAvailable())->toBeFalse();
});

it('throws on failed prompt', function () {
    Http::fake([
        '*/session' => Http::response(['id' => 'sess'], 200),
        '*/session/sess/message' => Http::response('Internal Error', 500),
    ]);

    $this->client->prompt('test');
})->throws(RuntimeException::class);

it('throws on model error in response', function () {
    Http::fake([
        '*/session' => Http::response(['id' => 'sess'], 200),
        '*/session/sess/message' => Http::response([
            'info' => [
                'id' => 'msg-1',
                'error' => [
                    'name' => 'APIError',
                    'data' => ['message' => 'Invalid API Key', 'statusCode' => 401],
                ],
            ],
            'parts' => [],
        ], 200),
    ]);

    $this->client->prompt('test', 'groq', 'llama-3.3-70b-versatile');
})->throws(RuntimeException::class, 'Model error (groq/llama-3.3-70b-versatile): Invalid API Key');

it('throws on failed session creation', function () {
    Http::fake([
        '*/session' => Http::response('Service unavailable', 503),
    ]);

    $this->client->createSession();
})->throws(RuntimeException::class);
