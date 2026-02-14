<?php

use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Http::fake([
        '*/sync' => Http::response(['id' => 1, 'title' => 'Test'], 200),
    ]);
});

it('stores knowledge entry', function () {
    $this->artisan('remember', ['content' => 'Laravel uses service providers for bootstrapping'])
        ->assertExitCode(0);
});

it('accepts a title', function () {
    $this->artisan('remember', [
        'content' => 'Some important content',
        '--title' => 'Service Providers',
    ])->assertExitCode(0);
});

it('accepts tags', function () {
    $this->artisan('remember', [
        'content' => 'Redis is great for caching',
        '--tags' => ['redis', 'caching'],
    ])->assertExitCode(0);
});

it('outputs JSON', function () {
    $this->artisan('remember', [
        'content' => 'Test content',
        '--json' => true,
    ])->assertExitCode(0);
});

it('derives title from content when not provided', function () {
    $this->artisan('remember', ['content' => 'Short title content'])
        ->assertExitCode(0);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), '/sync')
            && $request['title'] === 'Short title content';
    });
});

it('handles store failure', function () {
    $client = Mockery::mock(\App\Services\PrefrontalClient::class);
    $client->shouldReceive('store')
        ->andReturn(['success' => false, 'error' => 'Storage failed']);

    $this->app->instance(\App\Services\PrefrontalClient::class, $client);

    $this->artisan('remember', ['content' => 'test'])
        ->assertExitCode(1);
});
