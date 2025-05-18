<?php

namespace App\Providers;

use App\Models\Post;
use App\Policies\PostPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Map models to policies.
     */
    protected $policies = [
        Post::class => PostPolicy::class, // optional if using model policies
    ];

    /**
     * Register any auth services and gates.
     */
    public function boot(): void
    {
        $this->registerPolicies();
         \Log::info('AuthServiceProvider booted'); 
        // If you use array-based authorization like in your example
        Gate::define('createRequest', [PostPolicy::class, 'createRequest']);
        Gate::define('update', [PostPolicy::class, 'update']);
        Gate::define('delete', [PostPolicy::class, 'delete']);
    }
}
