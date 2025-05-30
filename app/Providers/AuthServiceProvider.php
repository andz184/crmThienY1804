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
        $this->registerPolicies();

        // Implicitly grant "Super Admin" role all permissions
        Gate::before(function ($user, $ability) {
            return $user->hasRole('Super Admin') ? true : null;
        });

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

        // Gate for viewing orders
        Gate::define('orders.view', function (User $user, $order = null) {
            if ($user->hasAnyRole(['super-admin', 'admin', 'manager'])) {
                return true;
            }
            if ($user->hasRole('staff')) {
                // If an order instance is passed, check if it belongs to the staff
                if ($order instanceof \App\Models\Order) {
                    return $order->user_id === $user->id;
                }
                // If no order instance, staff can generally view the order list (filtered by controller)
                return true;
            }
            return false;
        });

        // Gate for creating orders
        Gate::define('orders.create', function (User $user) {
            return $user->hasAnyRole(['super-admin', 'admin', 'manager', 'staff']);
        });

        // Gate for editing/updating orders (and by extension, pushing to Pancake)
        Gate::define('orders.update', function (User $user, \App\Models\Order $order) {
            if ($user->hasAnyRole(['super-admin', 'admin'])) {
                return true;
            }
            if ($user->hasRole('manager')) {
                // Manager can update orders of their team members or orders not assigned to any specific staff in their team (if applicable)
                // This might require more complex logic depending on your team structure
                // For now, let's say manager can update any order (same as admin for simplicity here)
                // OR: return $order->user?->team_id === $user->manages_team_id;
                return true; // Simplified for now
            }
            if ($user->hasRole('staff')) {
                return $order->user_id === $user->id;
            }
            return false;
        });

        // Gate for deleting orders
        Gate::define('orders.delete', function (User $user) {
            // Typically only admins or super-admins
            return $user->hasAnyRole(['super-admin', 'admin']);
        });

        // Gate for assigning orders (if different from general update)
        Gate::define('teams.assign', function(User $user){
             return $user->hasAnyRole(['super-admin', 'admin', 'manager']);
        });

        // Gate for call management
        Gate::define('calls.manage', function(User $user, $order = null) {
             if ($user->hasAnyRole(['super-admin', 'admin', 'manager'])) {
                return true;
            }
            if ($user->hasRole('staff')) {
                if ($order instanceof \App\Models\Order) {
                    return $order->user_id === $user->id;
                }
                return true; // Staff can generally manage calls, specific order check is fine
            }
            return false;
        });

        // Gate for Pancake sync functions (general sync, not specific order push)
        Gate::define('sync-pancake', function(User $user){
            return $user->hasAnyRole(['super-admin', 'admin', 'manager']);
        });

        // Gate for pushing individual orders to Pancake
        Gate::define('orders.push_to_pancake', function(User $user, $order = null) {
            if ($user->hasAnyRole(['super-admin', 'admin'])) {
                return true;
            }
            if ($user->hasRole('manager')) {
                return true; // Managers can push any order
            }
            if ($user->hasRole('staff')) {
                // Staff can only push their own orders
                if ($order instanceof \App\Models\Order) {
                    return $order->user_id === $user->id;
                }
                return true; // Allow staff to see the button, actual permission check happens with specific order
            }
            return false;
        });
    }
}
