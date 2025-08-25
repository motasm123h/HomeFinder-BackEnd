<?php

namespace App\Providers;

use App\Models\Post;
use App\Policies\PostPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    // /**
    //  * Map models to policies.
    //  */
    // protected $policies = [
    //     Post::class => PostPolicy::class, // optional if using model policies
    // ];

    /**
     * Register any auth services and gates.
     */
    public function boot(): void
    {
        $this->registerPolicies();
        Gate::define('createRequest', [PostPolicy::class, 'createRequest']);
        Gate::define('update', [PostPolicy::class, 'update']);
        Gate::define('delete', [PostPolicy::class, 'delete']);
    }
}
