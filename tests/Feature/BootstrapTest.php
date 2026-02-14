<?php

it('has the knowledge application name', function () {
    expect(config('app.name'))->toBe('Knowledge');
});

it('has knowledge config', function () {
    expect(config('knowledge.opencode.port'))->toBe(4096);
    expect(config('knowledge.prefrontal.url'))->toContain('prefrontal-cortex');
});

it('has cache config defaults', function () {
    expect(config('knowledge.cache.ttl'))->toBe(3600);
    expect(config('knowledge.cache.enabled'))->toBeTrue();
});
