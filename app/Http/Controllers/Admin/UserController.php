<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use App\Helpers\LogHelper;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $this->authorize('users.view');

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $query = User::with('roles', 'manager')->latest();

        // --- Search & Filters (Existing logic) ---
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', "%{$searchTerm}%")
                  ->orWhere('email', 'like', "%{$searchTerm}%");
            });
        }
        if ($request->filled('role')) {
            $roleName = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleName) {
                $q->where('name', $roleName);
            });
        }
         if ($request->filled('team_id')) {
             $teamId = $request->input('team_id');
             if ($request->input('role') === 'staff' || !$request->filled('role')) {
                  $query->where('team_id', $teamId);
             }
         }
        // --- End Search & Filters ---

        // --- Scope based on current user's role (existing logic) ---
        if ($currentUser->hasRole('manager') && $currentUser->manages_team_id) {
            $teamId = $currentUser->manages_team_id;
            $query->where(function ($q) use ($teamId) {
                $q->where('team_id', $teamId)
                  ->orWhere(function ($q2) {
                      $q2->whereNull('team_id')->whereHas('roles', fn($r) => $r->where('name', 'staff'));
                  });
            });
        } elseif (!($currentUser->hasRole('admin') || $currentUser->hasRole('super-admin'))) {
             $query->whereRaw('1 = 0');
        }
        // --- End Scope ---

        $users = $query->paginate(15)->withQueryString();

        // --- AJAX Response ---
        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('admin.users._user_table_body', compact('users'))->render(),
                'pagination_html' => $users->links('vendor.pagination.bootstrap-4')->render(),
            ]);
        }

        // --- Normal Response ---
        $filterRoles = Role::where('name', '!=', 'super-admin')->pluck('name', 'name');
        $filterTeams = User::whereNotNull('manages_team_id')
                          ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
                          ->pluck('name', 'manages_team_id');

        return view('admin.users.index', compact('users', 'filterRoles', 'filterTeams'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $this->authorize('users.create');
        $roles = Role::where('name', '!=', 'super-admin')->pluck('name', 'id');
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('users.create');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'team_id' => ['nullable', 'integer', Rule::exists('users', 'manages_team_id')->where(function ($query) {
                // Ensure the team_id belongs to a user with the 'manager' role
                return $query->whereExists(function ($subQuery) {
                    $subQuery->select(DB::raw(1))
                             ->from('model_has_roles')
                             ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                             ->join('model_has_permissions', 'model_has_roles.role_id', '=', 'model_has_permissions.role_id')
                             ->whereColumn('model_has_roles.model_id', 'users.id') // Link to the outer users table
                             ->where('roles.name', 'manager')
                             ->where('model_has_roles.model_type', User::class);
                });
            })],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign roles
        $user->roles()->sync($validated['roles']);

        // Assign team if role is staff and team_id is provided
        $staffRole = Role::where('name', 'staff')->first();
        if ($staffRole && in_array($staffRole->id, $validated['roles']) && !empty($validated['team_id'])) {
             // Check if the selected team_id is valid for assignment based on current user's role
             /** @var \App\Models\User $currentUser */
             $currentUser = Auth::user();
             if ($currentUser->hasRole('manager') && $validated['team_id'] != $currentUser->manages_team_id) {
                 // If current user is a manager, they can only assign to their own team
                 // This scenario might be less common in create form but good to have
                 // Consider disallowing selection in the view instead/as well
                 // For now, just don't assign if invalid
             } else {
                 $user->team_id = $validated['team_id'];
                 $user->save();
             }
        } elseif ($staffRole && in_array($staffRole->id, $validated['roles'])) {
            // If staff role is selected but no team_id, ensure it's null
            $user->team_id = null;
            $user->save();
        }

        LogHelper::log('create_user', $user, null, $user->toArray());
        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        //
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $this->authorize('users.edit');

        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();
        $canAssignTeam = $currentUser->can('teams.assign');

        // Determine if the current user can edit this target user's team
        $canEditTargetTeam = false;
        if ($canAssignTeam) {
            if ($currentUser->hasRole('admin') || $currentUser->hasRole('super-admin')) {
                $canEditTargetTeam = $user->hasRole('staff');
            } elseif ($currentUser->hasRole('manager') && $currentUser->manages_team_id) {
                $canEditTargetTeam = $user->hasRole('staff') &&
                                     ($user->team_id === null || $user->team_id == $currentUser->manages_team_id);
            }
        }

        // Get roles for assignment
        $roles = collect();
        if ($currentUser->hasRole('admin') || $currentUser->hasRole('super-admin')) {
            // Super-admin can assign any role
            if ($currentUser->hasRole('super-admin')) {
                $roles = Role::pluck('name', 'id');
            } else {
                // Regular admin cannot assign super-admin role
                $roles = Role::where('name', '!=', 'super-admin')->pluck('name', 'id');
            }
        }

        // Get assignable leaders (for team assignment dropdown)
        $assignableLeaders = collect();
        if ($canEditTargetTeam) {
             if ($currentUser->hasRole('admin') || $currentUser->hasRole('super-admin')) {
                $assignableLeaders = User::whereNotNull('manages_team_id')
                                        ->whereHas('roles', fn($q) => $q->where('name', 'manager'))
                                        ->pluck('name', 'manages_team_id');
            } elseif ($currentUser->hasRole('manager') && $currentUser->manages_team_id) {
                 $assignableLeaders->put($currentUser->manages_team_id, $currentUser->name . " (Team của bạn)");
            }
        }

        return view('admin.users.edit', compact('user', 'roles', 'assignableLeaders', 'canEditTargetTeam'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->authorize('users.edit');
        /** @var \App\Models\User $currentUser */
        $currentUser = Auth::user();

        $old = $user->toArray();
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => [Rule::requiredIf(function() use ($currentUser) {
                return $currentUser->hasRole('admin') || $currentUser->hasRole('super-admin');
            }), 'array'],
            'roles.*' => ['integer', 'exists:roles,id'],
            'team_id' => ['nullable', 'integer', Rule::exists('users', 'manages_team_id')],
        ]);

        // Update basic details
        $user->name = $request->name;
        $user->email = $request->email;
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Sync Roles (Only if admin or super-admin is editing)
        if (($currentUser->hasRole('admin') || $currentUser->hasRole('super-admin')) && $request->has('roles')) {
             // Prevent removing your own admin role
             if ($user->id === $currentUser->id) {
                 $adminRole = Role::where('name', 'admin')->first()->id;
                 $superAdminRole = Role::where('name', 'super-admin')->first()->id;

                 if ($currentUser->hasRole('super-admin') && !in_array($superAdminRole, $request->input('roles', []))) {
                     return redirect()->back()->with('error', 'Cannot remove your own Super Admin role.')->withInput();
                 } elseif ($currentUser->hasRole('admin') && !in_array($adminRole, $request->input('roles', []))) {
                     return redirect()->back()->with('error', 'Cannot remove your own Admin role.')->withInput();
                 }
             }

             // Regular admin cannot assign super-admin role
             if (!$currentUser->hasRole('super-admin')) {
                 $superAdminRole = Role::where('name', 'super-admin')->first()->id;
                 if (in_array($superAdminRole, $request->input('roles', []))) {
                     return redirect()->back()->with('error', 'You cannot assign Super Admin role.')->withInput();
                 }
             }

             $user->roles()->sync($request->input('roles'));
        }

        // Update Team ID (If allowed and provided)
        if ($currentUser->can('teams.assign') && $request->has('team_id')) {
             $newTeamId = $request->input('team_id');
             if ($currentUser->hasRole('manager') && $newTeamId != $currentUser->manages_team_id) {
                  return redirect()->back()->with('error', 'You can only assign staff to your own team.')->withInput();
             }
             if ($user->hasRole('staff')) {
                 $user->team_id = $newTeamId;
             } elseif ($newTeamId !== null) {
                  return redirect()->back()->with('error', 'You can only assign team to users with Staff role.')->withInput();
             }
        }

        $user->save();

        LogHelper::log('update_user', $user, $old, $user->toArray());
        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified user from storage (soft delete).
     */
    public function destroy(Request $request, User $user)
    {
        $this->authorize('users.delete');

        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            // Return JSON response for AJAX
             if ($request->expectsJson()) {
                 return response()->json(['error' => 'Cannot delete yourself.'], 403);
             }
            return redirect()->route('admin.users.index')->with('error', 'Cannot delete yourself.');
        }

        // Prevent deleting super-admin unless you are super-admin
        if ($user->hasRole('super-admin') && !Auth::user()->hasRole('super-admin')) {
             if ($request->expectsJson()) {
                 return response()->json(['error' => 'Cannot delete Super Admin.'], 403);
             }
             return redirect()->route('admin.users.index')->with('error', 'Cannot delete Super Admin.');
        }

        $old = $user->toArray();
        $userName = $user->name;
        $user->delete(); // Performs soft delete

        LogHelper::log('delete_user', $user, $old, null);

        // Return JSON response for AJAX request
        if ($request->expectsJson()) {
             return response()->json(['message' => "User '{$userName}' soft-deleted successfully."]);
        }

        return redirect()->route('admin.users.index')->with('success', "User '{$userName}' soft-deleted successfully.");
    }

    /**
     * Display a listing of the soft-deleted users.
     */
    public function trashedIndex(): View
    {
        $this->authorize('users.view_trashed');
        $trashedUsers = User::onlyTrashed()->with('roles')->latest()->paginate(15);
        return view('admin.users.trashed', compact('trashedUsers'));
    }

    /**
     * Restore the specified soft-deleted user.
     */
    public function restore($id)
    {
        $this->authorize('users.delete'); // Or a specific 'users.restore' permission
        $user = User::onlyTrashed()->findOrFail($id);
        $userName = $user->name;
        $user->restore();
        return redirect()->route('admin.users.trashed')->with('success', "User '{$userName}' restored successfully.");
    }

    /**
     * Permanently delete the specified user.
     */
    public function forceDelete($id)
    {
        $this->authorize('users.delete'); // Or a specific 'users.force_delete' permission
        $user = User::onlyTrashed()->findOrFail($id);
        $userName = $user->name;
        // Add any cleanup logic if needed (e.g., delete related files)
        $user->forceDelete();
        return redirect()->route('admin.users.trashed')->with('success', "User '{$userName}' permanently deleted.");
    }
}
