<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
use Illuminate\Support\Facades\App;
use App\Models\User;
use App\Observers\UserObserver;

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
        // Share locale information and translations
        Inertia::share([
            'locale' => fn () => App::getLocale(),
            'translations' => function () {
                $locale = App::getLocale();
                $path = resource_path("lang/{$locale}.json");
                if (!file_exists($path)) {
                    $path = resource_path("lang/en.json"); // Fallback to English
                }
                return json_decode(file_get_contents($path), true) ?: [];
            },
            'available_locales' => fn () => [
                'en', 'es', 'it', 'ro', 'ru', 'de', 'bn', 'hi'
            ]
        ]);

        Vite::prefetch(concurrency: 3);

        User::observe(UserObserver::class);
    }
}
