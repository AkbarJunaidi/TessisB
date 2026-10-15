<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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
        // Nama bulan/hari dan diffForHumans() mengikuti bahasa aplikasi (id).
        Carbon::setLocale(config('app.locale'));

        // Deteksi N+1 query di luar produksi: hanya dicatat ke log, halaman tidak diputus.
        if (! $this->app->isProduction()) {
            Model::preventLazyLoading();
            Model::handleLazyLoadingViolationUsing(function ($model, string $relation): void {
                Log::warning('N+1 lazy load: ' . $model::class . '::' . $relation);
            });
        }
    }
}
