<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

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
        RateLimiter::for('submissions', fn (Request $request) => Limit::perHour(6)->by('submission:'.($request->user()?->id ?? $request->ip())));
        RateLimiter::for('reviews', fn (Request $request) => Limit::perHour(12)->by('review:'.($request->user()?->id ?? $request->ip())));
    }
}
