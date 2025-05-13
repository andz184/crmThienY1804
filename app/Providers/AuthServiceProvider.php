<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

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
        // Define gates for admin access
        Gate::define('admin_access', function (User $user) {
            return $user->hasRole(['super-admin', 'admin']);
        });

        // Define gate for team management
        Gate::define('manage_team', function (User $user) {
            return $user->hasRole('manager');
        });

        // Define gate for staff access
        Gate::define('staff_access', function (User $user) {
            return $user->hasRole('staff');
        });

        // Define gates for customer management
        Gate::define('customers.view', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff']);
        });

        Gate::define('customers.create', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff']);
        });

        Gate::define('customers.edit', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff']);
        });

        Gate::define('customers.delete', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin']);
        });

        Gate::define('customers.sync', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager']);
        });

        Gate::define('customers.view_trashed', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin']);
        });

        Gate::define('customers.restore', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin']);
        });

        Gate::define('customers.force_delete', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin']);
        });

        // Định nghĩa quyền xem logs
        Gate::define('view-logs', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff']);
        });

        // Định nghĩa quyền xem tất cả logs (bao gồm logs của user khác)
        Gate::define('view-all-logs', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager']);
        });

        // Staff chỉ có thể xem logs của chính mình
        Gate::define('view-own-logs', function (User $user) {
            return $user->hasRole('staff');
        });
    }
}
