<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('apply-discount', fn (User $user) => $user->isAdmin());
        Gate::define('manage-admin', fn (User $user) => $user->isAdmin());
    }
}
