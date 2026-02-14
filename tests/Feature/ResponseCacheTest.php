<?php

use App\Services\ResponseCache;

it('caches and retrieves responses', function () {
    $cache = new ResponseCache(ttl: 60, enabled: true);

    $cache->put('test query', 'claude', ['response' => 'cached answer']);

    $result = $cache->get('test query', 'claude');

    expect($result)->toBe(['response' => 'cached answer']);
});

it('returns null for cache miss', function () {
    $cache = new ResponseCache(ttl: 60, enabled: true);

    expect($cache->get('unknown', 'claude'))->toBeNull();
});

it('returns null when disabled', function () {
    $cache = new ResponseCache(ttl: 60, enabled: false);

    $cache->put('test', 'claude', ['response' => 'data']);

    expect($cache->get('test', 'claude'))->toBeNull();
});

it('does not store when disabled', function () {
    $disabled = new ResponseCache(ttl: 60, enabled: false);
    $disabled->put('test', 'claude', ['response' => 'data']);

    $enabled = new ResponseCache(ttl: 60, enabled: true);
    expect($enabled->get('test', 'claude'))->toBeNull();
});

it('forgets cached entries', function () {
    $cache = new ResponseCache(ttl: 60, enabled: true);

    $cache->put('test', 'claude', ['response' => 'data']);
    $cache->forget('test', 'claude');

    expect($cache->get('test', 'claude'))->toBeNull();
});

it('differentiates by model', function () {
    $cache = new ResponseCache(ttl: 60, enabled: true);

    $cache->put('test', 'claude', ['response' => 'claude answer']);
    $cache->put('test', 'grok', ['response' => 'grok answer']);

    expect($cache->get('test', 'claude')['response'])->toBe('claude answer');
    expect($cache->get('test', 'grok')['response'])->toBe('grok answer');
});

it('creates from config', function () {
    config(['knowledge.cache.ttl' => 7200]);
    config(['knowledge.cache.enabled' => false]);

    $cache = ResponseCache::fromConfig();

    expect($cache->isEnabled())->toBeFalse();
});
