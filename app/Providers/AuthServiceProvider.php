<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Http\Controllers\UserController;
use App\User;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define('administrador', function ($user) {
            return $user->grupoID == 1;
        });

        Gate::define('cadernoFatura', function ($user) {
            return $user->getCaderno();
        });

        Gate::define('publicador', function ($user) {
            return $user->grupoID == 4;
        });

        Gate::define('faturas', function ($user) {
            return $user->grupoID == 3;
        });

    }
}
