<?php

namespace App\Providers;

use Illuminate\Http\Request;
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
        Gate::before(function ($user, $ability) {
            return $user->hasRole('super-admin') ? true : null;
        });

        Request::macro('integer', function ($key, $default = 0) {
            return (int) $this->input($key, $default);
        });

        Request::macro('float', function ($key, $default = 0) {
            return (float) $this->input($key, $default);
        });

        Request::macro('string', function ($key, $default = '') {
            return (string) $this->input($key, $default);
        });
    }
}
