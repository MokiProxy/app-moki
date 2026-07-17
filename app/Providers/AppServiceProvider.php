<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Gate::define('admin-access', function(User $user) {
            return $user->role_id === 1;
        });

        Gate::define('technician-access', function(User $user) {
            return $user->role_id === 4;
        });

        Gate::define('staff-access', function(User $user) {
            return $user->role_id === 3;
        });
    }
}
