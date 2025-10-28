<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\InterfaxPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Register InterFAX policies
        Gate::define('manage-interfax', [InterfaxPolicy::class, 'manageInterfax']);
        Gate::define('view-interfax-config', [InterfaxPolicy::class, 'viewInterfaxConfig']);
    }
}
