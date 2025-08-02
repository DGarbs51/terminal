<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Model::unguard();
        Vite::prefetch(concurrency: 5);

        if (! $this->app->environment('production')) {
            Model::preventLazyLoading();
            Model::preventSilentlyDiscardingAttributes();
            Model::preventAccessingMissingAttributes();
        }

    }
}
