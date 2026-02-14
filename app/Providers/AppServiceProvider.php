<?php

namespace App\Providers;

use App\Services\KnowledgeAiService;
use App\Services\ModelRouter;
use App\Services\OpenCodeClient;
use App\Services\PrefrontalClient;
use App\Services\ResponseCache;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(OpenCodeClient::class, fn () => OpenCodeClient::fromConfig());

        $this->app->singleton(ModelRouter::class, fn () => new ModelRouter);

        $this->app->singleton(PrefrontalClient::class, fn () => PrefrontalClient::fromConfig());

        $this->app->singleton(ResponseCache::class, fn () => ResponseCache::fromConfig());

        $this->app->singleton(KnowledgeAiService::class, fn ($app) => new KnowledgeAiService(
            opencode: $app->make(OpenCodeClient::class),
            router: $app->make(ModelRouter::class),
            prefrontal: $app->make(PrefrontalClient::class),
            cache: $app->make(ResponseCache::class),
        ));
    }
}
