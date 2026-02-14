<?php

it('displays model routing table', function () {
    $this->artisan('models')
        ->assertExitCode(0);
});

it('filters by provider', function () {
    $this->artisan('models', ['--provider' => 'anthropic'])
        ->assertExitCode(0);
});

it('filters by tier', function () {
    $this->artisan('models', ['--tier' => 'fast'])
        ->assertExitCode(0);
});

it('outputs JSON', function () {
    $this->artisan('models', ['--json' => true])
        ->assertExitCode(0);
});
