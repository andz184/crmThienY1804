<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Helpers\LogHelper;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('roles.view');
        $query = Role::with('permissions')->latest();

        // --- Search by Name --- 
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where('name', 'like', "%{$searchTerm}%");
        }

        $roles = $query->paginate(10)->withQueryString();

        // --- AJAX Response --- 
        if ($request->ajax()) {
            return response()->json([
                'table_html' => view('admin.roles._role_table_body', compact('roles'))->render(),
                'pagination_html' => $roles->links('vendor.pagination.bootstrap-4')->render(),
            ]);
        }

        // --- Normal Response --- 
        return view('admin.roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('roles.create');
        $permissions = Permission::all()->pluck('name', 'id');
        return view('admin.roles.create', compact('permissions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('roles.create');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'integer|exists:permissions,id',
        ]);

        $role = Role::create(['name' => $validated['name']]);

        // Lấy danh sách các đối tượng Permission từ ID đã validate
        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        } else {
            // Nếu không có permission nào được chọn, xóa hết permission cũ (nếu có)
            $role->syncPermissions([]);
        }

        LogHelper::log('create_role', $role, null, $role->toArray());
        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        $this->authorize('roles.view');
        $role->load('permissions');
        return view('admin.roles.show', compact('role'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        $this->authorize('roles.edit');
        $permissions = Permission::all()->pluck('name', 'id');
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        $this->authorize('roles.edit');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles')->ignore($role->id)],
            'permissions' => 'array',
            'permissions.*' => ['integer', 'exists:permissions,id'],
        ]);

        // Protect system roles
        if (in_array($role->name, ['super-admin', 'admin']) && $role->name != $validated['name']) {
            return redirect()->route('admin.roles.edit', $role)
                ->with('error', 'Cannot rename system roles.');
        }

        $old = $role->toArray();
        $role->update(['name' => $validated['name']]);

        // Lấy danh sách các đối tượng Permission từ ID đã validate
        if (!empty($validated['permissions'])) {
            $permissions = Permission::whereIn('id', $validated['permissions'])->get();
            $role->syncPermissions($permissions);
        } else {
             // Nếu không có permission nào được chọn, xóa hết permission cũ
            $role->syncPermissions([]);
        }

        LogHelper::log('update_role', $role, $old, $role->toArray());
        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        $this->authorize('roles.delete');
        if (in_array($role->name, ['super-admin', 'admin', 'manager', 'staff'])) {
             return redirect()->route('admin.roles.index')->with('error', 'Cannot delete default system roles.');
        }
        $old = $role->toArray();
        $role->delete();
        LogHelper::log('delete_role', $role, $old, null);
        return redirect()->route('admin.roles.index')->with('success', 'Role deleted successfully.');
    }
}
