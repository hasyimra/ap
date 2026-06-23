<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
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
        Blade::if('canWrite', fn () => auth()->check() && auth()->user()->canCreate());
        Blade::if('canApprove', fn () => auth()->check() && auth()->user()->canApprove());
        Blade::if('adminOnly', fn () => auth()->check() && auth()->user()->isAdmin());
        Blade::if('canManageUsers', fn () => auth()->check() && auth()->user()->canManageUsers());
    }
}
