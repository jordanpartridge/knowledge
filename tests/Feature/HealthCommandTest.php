<?php

use Illuminate\Support\Facades\Http;

it('reports all healthy when services are up', function () {
    Http::fake([
        '*/doc' => Http::response(['openapi' => '3.1.0'], 200),
        '*/dashboard*' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->artisan('health')
        ->assertExitCode(0);
});

it('reports failure when opencode is down', function () {
    Http::fake([
        '*/doc' => Http::response(null, 500),
        '*/dashboard*' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->artisan('health')
        ->assertExitCode(1);
});

it('reports failure when prefrontal is down', function () {
    Http::fake([
        '*/doc' => Http::response(['openapi' => '3.1.0'], 200),
        '*/dashboard*' => Http::response(null, 500),
    ]);

    $this->artisan('health')
        ->assertExitCode(1);
});

it('outputs JSON', function () {
    Http::fake([
        '*/doc' => Http::response(['openapi' => '3.1.0'], 200),
        '*/dashboard*' => Http::response(['status' => 'ok'], 200),
    ]);

    $this->artisan('health', ['--json' => true])
        ->assertExitCode(0);
});
