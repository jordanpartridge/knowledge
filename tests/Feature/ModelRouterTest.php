<?php

use App\Services\ModelRouter;

beforeEach(function () {
    $this->router = new ModelRouter;
});

it('classifies "what is X" queries as what intent', function () {
    expect($this->router->classify('what is dependency injection'))->toBe('what');
    expect($this->router->classify('list all models'))->toBe('what');
    expect($this->router->classify('show me the config'))->toBe('what');
});

it('classifies "how to X" as how intent', function () {
    expect($this->router->classify('how do I set up queues'))->toBe('how');
    expect($this->router->classify('explain event sourcing'))->toBe('how');
});

it('classifies "why did X fail" as why intent', function () {
    expect($this->router->classify('why did the deployment fail'))->toBe('why');
    expect($this->router->classify('what is the root cause'))->toBe('why');
});

it('classifies analysis queries as analyze intent', function () {
    expect($this->router->classify('analyze my git activity'))->toBe('analyze');
    expect($this->router->classify('compare Redis vs Memcached'))->toBe('analyze');
});

it('classifies code queries as code intent', function () {
    expect($this->router->classify('write a function to parse JSON'))->toBe('code');
    expect($this->router->classify('refactor this class'))->toBe('code');
    expect($this->router->classify('debug the authentication'))->toBe('code');
});

it('falls back to default for unclassified queries', function () {
    expect($this->router->classify('hello world'))->toBe('default');
    expect($this->router->classify('thanks'))->toBe('default');
});

it('resolves what queries to fast tier', function () {
    $result = $this->router->resolve('what is Laravel');
    expect($result['tier'])->toBe('fast');
    expect($result['provider'])->toBe('groq');
    expect($result['intent'])->toBe('what');
});

it('resolves why queries to premium tier', function () {
    $result = $this->router->resolve('why is this failing');
    expect($result['tier'])->toBe('premium');
    expect($result['provider'])->toBe('anthropic');
});

it('uses explicit model override when provided', function () {
    $result = $this->router->resolve('anything', 'anthropic/claude-opus-4-6');
    expect($result['provider'])->toBe('anthropic');
    expect($result['model'])->toBe('claude-opus-4-6');
    expect($result['intent'])->toBe('explicit');
});

it('supports model shortcuts', function () {
    $result = $this->router->resolve('test', 'grok');
    expect($result['provider'])->toBe('xai');
    expect($result['model'])->toBe('grok-3');

    $result = $this->router->resolve('test', 'opus');
    expect($result['provider'])->toBe('anthropic');
    expect($result['model'])->toBe('claude-opus-4-6');

    $result = $this->router->resolve('test', 'gemini');
    expect($result['provider'])->toBe('openrouter');
});

it('allows runtime route overrides', function () {
    $this->router->setRoute('what', 'openai', 'gpt-4o');
    $result = $this->router->resolve('what is X');
    expect($result['provider'])->toBe('openai');
    expect($result['model'])->toBe('gpt-4o');
});

it('returns all routes', function () {
    $routes = $this->router->routes();
    expect($routes)->toHaveKeys(['what', 'how', 'why', 'analyze', 'code', 'search', 'default']);
});

it('handles unknown explicit model as openrouter', function () {
    $result = $this->router->resolve('test', 'some-random-model');
    expect($result['provider'])->toBe('openrouter');
    expect($result['model'])->toBe('some-random-model');
    expect($result['tier'])->toBe('custom');
});
